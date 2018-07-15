<?php
/**
 * Usage: php sampleDocProcess.php [-overwrite] [-quiet]
 *
 * -overwrite  = overwrite all files even if they already exists
 * -quiet = don't output anything
 *
 * - grab all sample documents (text files, pdfs, docs, zips, etc.) from our s3 cloud
 * - make an image out of the pdf and add it to the Mediawiki structure
 * - add data to our sample doc viewer table
 */

global $IP;

require_once __DIR__ . '/../commandLine.inc';
require_once "$IP/extensions/wikihow/common/copyscape_functions.php";
require_once "$IP/extensions/wikihow/common/S3.php";

class SampleDocProcess {
	const PHOTO_USER = 'Wikiphoto';
	const AWS_BUCKET = 'sampledocs';
	const DEFAULT_DOC_DIR = '/sampledocs/';
	const DEFAULT_DOC_URIBASE = '/images';
	const TEMP_DIR = '/sampledocs/temp';
	const ERROR_LOG = '/usr/local/wikihow/log/sampledocprocess.log';
	const SAMPLE_STATUS_DB = 'dv_sampledocs_status';
	const SAMPLE_PREFIX_DOC = 'display_name.csv';

	static $docExts = array('doc', 'docx', 'pdf', 'txt', 'html', 'zip', 'xls', 'xlsx');
	static $overwrite = false;
	static $quiet = false;

	private function listS3Docs() {
		$s3 = new S3(WH_AWS_WIKIVISUAL_ACCESS_KEY, WH_AWS_WIKIVISUAL_SECRET_KEY);
		$bucket_name = self::AWS_BUCKET;
		$prefix = null;
		$marker = null;
		$maxKeys = null;
		$delimiter = null;
		$returnCommonPrefixes = false;

		$buckets = $s3->getBucket($bucket_name,$prefix,$marker,$maxKeys,$delimiter,$returnCommonPrefixes);

		if (!self::$quiet) print "number of buckets: ". count($buckets) ."\n";

		foreach ($buckets as $path => $details) {
			// match string: doc_folder/doc_file.ending
			if (!preg_match('@^(.*)/(.*)\.(.*)$@i', $path, $m)) {
				continue;
			}

			list(, $doc_folder, $doc_file, $ending) = $m;

			//validate extension
			if (!in_array($ending,self::$docExts)) continue;

			$prefix = $doc_folder . '/' . $doc_file;
			$files = array($ending);

			list($err, $stageDir) = self::pullFiles($s3, $doc_folder, $doc_file, $ending);
		}

		//now process the display names
		self::processDisplayNames($s3);
	}


	/**
	 * Download files from S3
	 */
	private static function pullFiles(&$s3, $doc_folder, $doc_file, $ending) {
		global $wgUploadDirectory;
		$err = '';
		$is_new = false;

		$file_name = $doc_folder;

		//create a custom dir based on md5
		$local_dir = substr(md5($file_name), 0, 1);
		$local_dir = self::DEFAULT_DOC_DIR . $local_dir;
		$dir = $wgUploadDirectory . $local_dir;

		$file_name = preg_replace('@^/@','',$file_name);
		$file_name = preg_replace('@/| @', '-', $file_name);

		if (!is_dir($dir)) {
			//Not a directory already? Make it so.
			$ret = mkdir($dir);

			if (!$ret) {
				self::error_log($file_name,'Unable to create dir: '.$dir);
				return false;
			}
		}

		$aws_file = $doc_folder.'/'.$doc_file.'.'.$ending;
		$local_file = $dir.'/'.$file_name.'.'.$ending;

		//since we unzip to html and delete the zip file,
		//gotta check the resulting html file date
		if ($ending == 'zip') {
			$check_file = str_replace('.zip','.html',$local_file);
		}
		else {
			$check_file = $local_file;
		}

		//let's check to see if we already have it
		if (!self::$overwrite && file_exists($check_file)) {
			//is it newer?
			$info = $s3->getObjectInfo(self::AWS_BUCKET, $aws_file);
			if ($info['time'] <= filemtime($check_file)) {
				//old. skip.
				if (!self::$quiet) print 'exists: '.$check_file."\n";
				return true;
			}
			else {
				//updated. clear the memcache key
				global $wgMemc;
				$memkey = wfMemcKey('sample_'.$file_name);
				$wgMemc->delete($memkey);
			}
		}
		else {
			$is_new = true;
		}

		if (!self::$quiet) print 'grabbing: '.$local_file."\n";

		$ret = $s3->getObject(self::AWS_BUCKET, $aws_file, $local_file);
		if (!$ret || $ret->error) {
			self::error_log($file_name,"Problem retrieving file from S3: s3://" . self::AWS_BUCKET . "/$aws_file");
			return;
		}

		//let's do this
		if ($ending == 'zip') {
			list($err, $filez) = self::unzip($dir,$file_name.'.'.$ending);
			if (!$err) {
				//loop through, rename, and process each
				foreach ($filez as $file) {
					$html_name = str_replace('.zip','.html',$local_file);
					if (!rename($file,$html_name)) {
						self::error_log($file_name,'Temporary file not renamed: '.$file);
						continue;
					}
					self::processDoc($html_name, $local_dir, $file_name, 'html', $is_new);
				}
			}
			else {
				self::error_log($file_name,'Zip error: '.$err);
			}
		}
		else {
			self::processDoc($local_file, $local_dir, $file_name, $ending, $is_new);
		}

		return true;
	}

	private static function processDoc($local_file, $local_dir, $doc_file, $ending, $is_new) {

		//first, let's make sure it's not infected
		if (self::is_infected($local_file, $sample)) {
			return false;
		}

		//now let's run some format-related functions
		if ($ending == 'pdf') {
			if (!self::makeThumbs($local_file, $doc_file)) {
				self::error_log($doc_file,'Thumbs not made.');
				return false;
			}
		}
		else if ($ending == 'html') {
			if (!self::formatHtml($local_file, $doc_file)) {
				self::error_log($doc_file,'HTML file not formatted');
				return false;
			}
		}
		else {

			//let's see if it's been plagiarized
			//only checking TXT and DOC files
			//only for NEW files (because people will copy these and create false positives)
			if ($is_new && (($ending == 'doc') || ($ending == 'docx') || ($ending == 'txt'))) {
				//just check and note copyvio hits, but we'll still want to import
				self::is_plagiarized($local_file, $doc_file);
			}
		}

		//log it to our table
		$res = self::saveDbDocData(self::DEFAULT_DOC_URIBASE.$local_dir, $doc_file, $ending);

		//all good? update our status db
		if ($res) self::db_log($doc_file, $ending);
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
		if ($count > 0) return true;

		//do the insert
		$res = $dbw->insert('dv_sampledocs', $doc_array, __METHOD__);

		if (!$res) self::error_log($doc_file,'error inserting into the db: '.$doc_folder.$doc_file.'.'.$ending);

		return $res;
	}

	private static function makeThumbs($local_file, $sample) {
		global $wgImageMagickConvertCommand, $wgUseOptiPng, $wgOptiPngCommand;

		$comment = 'Auto-uploaded for the Samples page.';
		$license = 'cc-by-sa-nc-3.0-self';

		//PNG the file name
		$img_file = preg_replace('@.pdf@','_sample.png',$local_file);

		//make an image out of a pdf (add an [0] so we only get the first page)
		$cmd = $wgImageMagickConvertCommand . ' ' . escapeshellarg($local_file) . '[0] ' . escapeshellarg($img_file);
		exec($cmd);

		//optimize that bad boy
		if ($wgUseOptiPng) {
			$cmd = $wgOptiPngCommand . ' ' . escapeshellarg($img_file);
			exec($cmd);
		}

		//put the new image into the system
		$title = Title::makeTitleSafe( NS_IMAGE, wfBaseName($img_file) );

		if( !is_object( $title ) ) {
			self::error_log($sample, 'Image could not be imported; a valid title cannot be produced.' );
			return false;
		}

		$image = wfLocalFile( $title );

		$archive = $image->publish( $img_file );

		//remove the temp image file
		@unlink($img_file);

		if( WikiError::isError( $archive ) || !$archive->isGood() ) {
			self::error_log($sample, 'Image archive publish failed.');
			return false;
		}

		if ( $image->recordUpload( $archive->value, $comment, $license ) ) {
			//yay!
			return true;
		}
		else {
			self::error_log($sample, 'Image record upload failed.' );
			return false;
		}
	}

	private static function formatHtml($local_file, $sample) {

		//get the delicious insides
		$html = file_get_contents($local_file);

		//don't need the custom Google formatting
		$html = preg_replace('/@import url.*?;/','#sample_html ',$html);
		//now make all the styles unique to this
		$html = preg_replace('/}/','} #sample_html ',$html);
		//disable any links that may have cropped up
		$html = preg_replace('/<a.*?>/','',$html);
		$html = preg_replace('/<\/a>/','',$html);
		//drop that title tag
		$html = preg_replace('/<title>[^<]*<\/title>/','',$html);

		//make the changes to the file
		$fh = fopen($local_file,'w');
		if (!fwrite($fh,$html)) self::error_log($sample, 'HTML not formatted.');
		fclose($fh);

		return true;
	}

	/**
	 * Unzip a file into a temp directory
	 */
	private static function unzip($dir, $zip) {
		global $wgUploadDirectory;
		$err = '';
		$files = array();

		system('unzip -j -o -qq '.escapeshellarg($dir.'/'.$zip).' -d '.$wgUploadDirectory.self::TEMP_DIR, $ret);
		if ($ret != 0) {
			$err = "error in unzipping $dir/$zip";
		}
		if (!$err) {
			if (!unlink($dir . '/' . $zip)) {
				$err = "error removing zip file $dir/$zip";
			}
		}
		if (!$err) {
			$upcase = array_map('strtoupper', self::$docExts);
			$exts = array_merge($upcase, self::$docExts);
			$ret = glob($wgUploadDirectory.self::TEMP_DIR . '/*.{' . join(',', $exts) . '}', GLOB_BRACE);
			if (false === $ret) {
				$err = 'no files unzipped';
			} else {
				$files = $ret;
			}
		}
		return array($err, $files);
	}

	/**
	 * virus scan each document with ClamAV
	 * return true if there's an issue
	 */
	private static function is_infected($doc, $sample) {

		system('clamscan --quiet '.escapeshellarg($doc), $ret);

		if ($res == '') {
			//no output for uninfected files
			$is_infected = false;
		}
		else {
			//any output means trouble
			$is_infected = true;
			self::error_log($sample, 'INFECTED WITH A SCARY VIRUS!' );
		}

		return $is_infected;
	}


	/**
	 * check for plagiarism with copyscape
	 * return true if there's an issue
	 */
	private static function is_plagiarized($doc, $sample) {
		$threshold = 0.25;

		$text = file_get_contents($doc);
		$res = copyscape_api_text_search_internet($text, 'ISO-8859-1', 2);

		if ($res['count']) {
			$words = $res['querywords'];
			foreach($res['result'] as $r) {
				if (!preg_match("@^http://[a-z0-9]*.(wikihow|whstatic|youtube).com@i", $r['url'])) {
					if ($r['minwordsmatched'] / $words > $threshold) {
						//we got one!
						$is_plagiarized = true;
						self::error_log($sample, 'Possibly plagiarized from here: '.$r['url'] );
					}
				}
			}

		}
		else {
			$is_plagiarized = false;
		}

		return $is_plagiarized;
	}

	/**
	 * run through all our docs in the db and remove ones that aren't on our server
	 */
	private static function removeOldDocs() {
		//NEED TO REWRITE BEFORE USING AGAIN!!!
		return;
		global $IP;
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select('dv_sampledocs', '*', '', __METHOD__);

		foreach ($res as $row) {
			$full_path = $IP.$row->dvs_doc_folder.'/'.$row->dvs_doc.'.'.$row->dvs_doc_ext;
			if (!file_exists($full_path)) {
				//it's gone! remove from the db
				$res = $dbw->delete('dv_sampledocs',
					array('dvs_doc_folder' => $row->dvs_doc_folder, 'dvs_doc' => $row->dvs_doc, 'dvs_doc_ext' => $row->dvs_doc_ext),
					__METHOD__);

				if (!$res) self::error_log($row->dvs_doc, $row->dvs_doc.'.'.$row->dvs_doc_ext.' not deleted from the database.' );
			}
		}
	}

	private static function error_log($sample, $txt) {
		$logfile = self::ERROR_LOG;

		//good. let's log some errors, shall we?
		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);

		//log the error
		self::db_log($sample, '', $txt);

		//output the error if we're not very quiet
		if (!self::$quiet) print $txt."\n";
	}

	private static function resetErrorLog() {
		//wipe error log
		$fh = fopen(self::ERROR_LOG, 'w');
		fwrite( $fh, '' );
		fclose($fh);

		//remove errors from status DB
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(self::SAMPLE_STATUS_DB, array('errors' => ''), array('reviewed' => 1),__METHOD__);
	}

	private static function db_log($sample, $format, $error = '') {
		$dbr = wfGetDB(DB_SLAVE);
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
			return $res;
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
		return $res;
	}

	public static function processDisplayNames($s3) {
		global $wgUploadDirectory, $wgMemc;
		$dbw = wfGetDB(DB_MASTER);

		$aws_file = self::SAMPLE_PREFIX_DOC;
		$local_file = $wgUploadDirectory . self::DEFAULT_DOC_DIR . self::SAMPLE_PREFIX_DOC;

		if (!self::$quiet) print 'grabbing: '.self::SAMPLE_PREFIX_DOC."\n";

		$ret = $s3->getObject(self::AWS_BUCKET, $aws_file, $local_file);
		if (!$ret || $ret->error) {
			self::error_log($file_name,"Problem retrieving file from S3: s3://" . self::AWS_BUCKET . "/$aws_file");
			return;
		}

		//got it. let's process
		$display_array = array();
		$row = 0;
		if (($handle = fopen($local_file, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 10000000, ",", '"')) !== FALSE) {
				if ($row > 0) {
					list($sample, $display_name) = $data;

					$sample = str_replace(' ','-',$sample);

					//put it into our table
					$sql = 'INSERT INTO dv_display_names (dvdn_doc, dvdn_display_name)
							VALUES ('. $dbw->addQuotes($sample) .','. $dbw->addQuotes($display_name) .')
							ON DUPLICATE KEY UPDATE dvdn_display_name = '. $dbw->addQuotes($display_name) .';';
					$dbw->query($sql);

					//updated. clear the memcache key
					$memkey = wfMemcKey('sample_display_name_'.$file_name);
					$wgMemc->delete($memkey);
				}
				$row++;
			}
		}



	}

	/**
	 * Entry point for main processing loop
	 */
	public static function main($overwrite,$quiet) {
		global $wgUser, $wgIsToolsServer;

		if (!$wgIsToolsServer) {
			print "error: this script " . __FILE__ . " should be run on the tools1 server\n";
			print "  since that server is the one where the AWS upload keys are available\n";
			exit;
		}

		//save user
		$tempUser = $wgUser;
		//swap user
		$wgUser = User::newFromName('SampleBot');

		self::$quiet = (bool)$quiet;
		self::$overwrite = (bool)$overwrite;

		//wipe out the errors to start anew
		self::resetErrorLog();

		//grab all the docs...
		self::listS3Docs();

		//did we lose any?
		//self::removeOldDocs();

		//swap user back
		$wgUser = $tempUser;
	}

}

SampleDocProcess::main($options['overwrite'],$options['quiet']);
