<?php

global $IP;
session_start();

require_once "$IP/extensions/wikihow/common/copyscape_functions.php";

class SampleProcess {

	const DEFAULT_DOC_DIR = '/sampledocs/';
	const DEFAULT_DOC_URIBASE = '/images';
	const SAMPLE_STATUS_DB = 'dv_sampledocs_status';

	static $check_for_plagiarism = false;
	static $client;

	/**
	 * Build and returns a Drive service object authorized with the service accounts.
	 *
	 * @return Google_DriveService service object.
	 */
	public static function buildService() {
		$key = file_get_contents(WH_GOOGLE_DOCS_P12_PATH);
		$cred = new Google_Auth_AssertionCredentials(
			WH_GOOGLE_SERVICE_APP_EMAIL,
			array( 'https://www.googleapis.com/auth/drive', 'https://spreadsheets.google.com/feeds'),
			//array( 'https://www.googleapis.com/auth/drive'),
			$key
		);

		$client = new Google_Client();
		$client->setAssertionCredentials($cred);

		// optional way to get the access token if you need it for other purposes
		// we don't specifically need it at the moment btu it's used for other google drive api calls
		if ($client->getAuth()->isAccessTokenExpired()) {
			  $client->getAuth()->refreshTokenWithAssertion();
		}
		$service = new Google_Service_Drive($client);

		self::$client = $client;

		return $service;
	}

	private static function downloadFiles($service, $fileId) {
		try {
			$file = $service->files->get($fileId);

			$downloadUrls = $file->getExportLinks();
			if (!count($downloadUrls)) return array('sample' => $fileId, 'formats' => 'Error: no exportable links');

			$title = trim($file->getTitle());
			$title = preg_replace('@^/@','',$title); //can't start with a slash
			$title = preg_replace('@\.$@', '', $title); //no period at the end
			$title = strip_tags($title); //let's "use the big hammer" as Reuben says

			if ($title) {
				//process these bad boys
				$result = self::getAllFormats($title,$downloadUrls,$file->getMimeType());

				//clear the memcache for this sample
				global $wgMemc;
				$file_name = preg_replace('@/| @', '-', $title);
				$memkey = wfMemcKey('sample', $file_name);
				$wgMemc->delete($memkey);

				return array('sample' => $title, 'formats' => $result);
			}
		} catch (Exception $e) {
			return array('sample' => $fileId, 'formats' => "An error occurred: " . $e->getMessage());
		}
		return array('sample' => $fileId, 'formats' => 'File not downloaded');
	}

	private static function getAllFormats($title,$urls,$mimeType) {
		$result = array();

		$default_formats = array('text/html','application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document','text/plain');
		$xl_formats = array('application/pdf','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		$formats = ($mimeType == 'application/vnd.google-apps.spreadsheet') ? $xl_formats : $default_formats;

		//is this a drawing?
		$bOnlyPDF = ($urls['image/png']) ? true : false;

		foreach ($formats as $format) {
			if ($urls[$format]) $result[] = self::saveFile($title,$urls[$format],$format,$bOnlyPDF);
		}

		return $result;
	}

	private static function saveFile($title, $file, $mimeType, $bOnlyPDF) {
		global $wgUploadDirectory;

		$err = '';

		//create a custom dir based on md5
		$md5_dir = substr(md5($title), 0, 1);
		$local_dir = self::DEFAULT_DOC_DIR . $md5_dir;
		$dir = $wgUploadDirectory . $local_dir;

		if (!is_dir($dir)) {
			//Not a directory already? Make it so.
			$ret = mkdir($dir);

			if (!$ret) return 'Unable to create dir: '.$dir;
		}

		$file_name = preg_replace('@/| @', '-', $title);
		$ending = substr($file,(strrpos($file,'exportFormat=')+13));
		$local_file = $dir.'/'.$file_name.'.'.$ending;

		$contents = file_get_contents($file);

		if ($ending == 'html' && preg_match('@<img@im',$contents)) {
			//there's an image file in it, it'll be hosted on Google
			//we do not want such a file...
			self::displayAsPDF($file_name);
			return 'HTML file has images. Defaulting to PDF.';
		}

		$fh = fopen($local_file,'w');
		if (!fwrite($fh,$contents)) return '';
		fclose($fh);

		//virus?
		//if (self::is_infected($local_file)) $err .= $local_file.' was infected and removed';

		if ($ending == 'pdf') {

			//make images
			$thumbs = self::makeThumbs($local_file);
			if ($thumbs) $err .= 'Image creation fail for reasons. '.$thumbs;

			//force it to display as a PDF if this is the only file
			if ($bOnlyPDF) self::displayAsPDF($file_name);
		}
		elseif (self::$check_for_plagiarism && (($ending == 'doc') || ($ending == 'docx') || ($ending == 'txt'))) {
			//plagiarized?
			$ripped_off_from = self::is_plagiarized($local_file);
			if ($ripped_off_from) $err .= 'PLAGIARIZED! Possibly from: '.$ripped_off_from;
		}

		self::updateS3($local_file, $md5_dir, $file_name.'.'.$ending, $mimeType);

		//done. log it to the db
		$res = self::saveDbDocData(self::DEFAULT_DOC_URIBASE.$local_dir, $file_name, $ending);
		if ($res) $err .= 'DB err: '.$res;

		//now update Qbert
		$res = self::qbertLog($file_name,$ending,$err);
		if ($res) $err .= 'Qbert insertion failed: '.$res;

		//did we error out? darn the luck.
		if ($err) return $err;

		return $file_name.'.'.$ending;
	}

	private static function saveDbDocData($doc_folder, $doc_file, $ending) {
		$dbw = wfGetDB(DB_MASTER);

		$doc_array = array('dvs_doc_folder' => $doc_folder,
							'dvs_doc' => $doc_file,
							'dvs_doc_ext' => $ending);

		//make sure it's not in there already
		$count = $dbw->selectField('dv_sampledocs', 'count(*) as count',
					$doc_array, __METHOD__);

		//already there; just return successful
		if ($count > 0) return '';

		//do the insert
		$res = $dbw->insert('dv_sampledocs', $doc_array, __METHOD__);

		if (!$res) return 'error inserting into the db: '.$doc_folder.$doc_file.'.'.$ending;

		return '';
	}

	private static function qbertLog($sample, $format, $error = '') {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$res = $dbr->selectField(self::SAMPLE_STATUS_DB, 'sample', array('sample' => $sample),__METHOD__);

		if (!$res) {
			//if it's new, no logic needed.  shove it in.
			$insert_opts = array(
				'sample' => $sample,
				'sample_simple' => str_replace('-',' ',$sample),
				'processed' => $dbw->timestamp(),
				'formats' => $format,
				'articles' => '',
				'errors' => $error
			);

			$res = $dbw->insert(self::SAMPLE_STATUS_DB, $insert_opts,__METHOD__);
			return ($res) ? '' : 'error creating new QBert entry';
		}

		//a little magic for the file format
		$formats = $dbr->selectField(self::SAMPLE_STATUS_DB, 'formats', array('sample' => $sample),__METHOD__);
		$formats_array = explode(',',$formats);
		if ($format && !in_array($format,$formats_array)) {
			$formats_array[] = $format;
		}
		$formats = implode(',',$formats_array);

		//already in there. let's add info
		$update_opts = array(
			'processed' => $dbw->timestamp(),
			'reviewed' => 0,
			'formats' => $formats,
			'errors = concat(errors,'.$dbw->addQuotes('  '.$error).')'
		);

		$res = $dbw->update(self::SAMPLE_STATUS_DB, $update_opts, array('sample' => $sample),__METHOD__);
		return ($res) ? '' : 'error updating QBert entry';
	}

	//update our config storage with the new title
	private static function displayAsPDF($file_name) {
		$sample_pdfs = ConfigStorage::dbGetConfig('sample_pdfs');
		$sample_pdfs .= "\n".$file_name;
		$isArticleList = false;
		$err = '';
		ConfigStorage::dbStoreConfig('sample_pdfs', $sample_pdfs, $isArticleList, $err);
		return;
	}

	private static function makeThumbs($local_file) {
		global $wgImageMagickConvertCommand, $wgUseOptiPng, $wgOptiPngCommand, $wgUser;

		//save user
		$tempUser = $wgUser;
		//swap user
		$wgUser = User::newFromName('SampleBot');

		$comment = 'Auto-uploaded for the Samples page.';
		$license = 'cc-by-sa-nc-3.0-self';

		//PNG the file name
		$img_file = preg_replace('@.pdf@','_sample.png',$local_file);

		//switched to Ghostscript (instead of ImageMagick) because some PDFs were having whitespace added (only on spare1, though)
		// //make an image out of a pdf (add an [0] so we only get the first page)
		//$cmd = $wgImageMagickConvertCommand . ' ' . escapeshellarg($local_file) . '[0] ' . escapeshellarg($img_file);
		$cmd = 'gs -sDEVICE=png16m -dFirstPage=1 -dLastPage=1 -o ' . escapeshellarg($img_file) . ' ' . escapeshellarg($local_file);
		exec($cmd);

		//optimize that bad boy
		if ($wgUseOptiPng) {
			$cmd = $wgOptiPngCommand . ' ' . escapeshellarg($img_file);
			exec($cmd);
		}

		//put the new image into the system
		$title = Title::makeTitleSafe( NS_IMAGE, wfBaseName($img_file) );

		if ( !is_object( $title ) ) {
			//swap user back
			$wgUser = $tempUser;
			return 'Image could not be imported; a valid title cannot be produced.';
		}

		$image = wfLocalFile( $title );

		$archive = $image->publish( $img_file );

		//remove the temp image file
		@unlink($img_file);

		if ( WikiError::isError( $archive ) || !$archive->isGood() ) {
			return 'Image archive publish failed.';
		}

		if ( $image->recordUpload( $archive->value, $comment, $license ) ) {
			//yay!
			//swap user back
			$wgUser = $tempUser;
			return '';
		}
		else {
			//swap user back
			$wgUser = $tempUser;
			return 'Image record upload failed.';
		}
	}

	/**
	 * check for plagiarism with copyscape
	 * return plagiarized urls if there's an issue
	 */
	private static function is_plagiarized($doc) {
		$threshold = 0.25;

		$text = file_get_contents($doc);
		$res = copyscape_api_text_search_internet($text, 'ISO-8859-1', 2);

		$result = '';
		if ($res['count']) {
			$words = $res['querywords'];
			foreach ($res['result'] as $r) {
				if (!preg_match("@^http://[a-z0-9]*.(wikihow|whstatic|youtube).com@i", $r['url'])) {
					if ($r['minwordsmatched'] / $words > $threshold) {
						//we got one!
						$result .= '<br />'. $r['url'];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * virus scan each document with ClamAV
	 * return true if there's an issue
	 */
	private static function is_infected($doc) {

		system('clamscan --quiet '.escapeshellarg($doc), $ret);

		if ($res == '') {
			//no output for uninfected files
			$is_infected = false;
		}
		else {
			//any output means trouble
			$is_infected = true;
			unlink($doc);
		}

		return $is_infected;
	}

	private static function updateS3($local_file, $md5_dir, $sample, $mimeType) {
		global $wgUploadPath;
		$s3_path = $wgUploadPath . self::DEFAULT_DOC_DIR . $md5_dir . '/';

		self::purgePath($s3_path.$sample);
		self::purgeS3($s3_path.$sample);
		self::uploadS3($local_file, $s3_path.$sample, $mimeType);
	}

	private static function purgePath($path) {
		global $wgUseSquid, $wgCanonicalServer;
		if ($wgUseSquid) {
			$urls = array();
			$urls[] = $wgCanonicalServer . $path;
			$u = new SquidUpdate($urls);
			$u->doUpdate();
			wfDebug("SampleProcess: Purging path of " . print_r($urls, true) . "\n");
		}
		return true;
	}

	// Aggressive caching is causing bugs. Remove the S3 copy of the image.
	private static function purgeS3($s3_path) {
		// remove the S3 file so the front end caching systems don't refetch from there
		AwsFiles::deleteFile($s3_path);
	}

	private static function uploadS3($local_path, $s3_path, $mimeType) {
		AwsFiles::uploadFile($local_path, $s3_path, $mimeType);
	}

	public static function importSamples($ids, $check_for_plagiarism) {
		self::$check_for_plagiarism = $check_for_plagiarism;

		$service = self::buildService();
		if (!isset($service)) return array('sample' => 'cannot get to the Google', 'formats' => '');

		$result = '';
		foreach ($ids as $id) {
			$result[] = self::downloadFiles($service, $id);
		}

		return $result;
	}
}
