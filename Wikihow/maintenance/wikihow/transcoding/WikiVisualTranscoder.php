<?php
/*
This is the main class for doing transcoding of videos and placing
images and videos in the mediawiki db and into the wikitext

==Quick steps for dev testing a single article
1. Verify input data is in the test folder s3://wikivisual-upload-test/aaron/
   * How do I do this?
   Run this s3 command to view the contents of the folder in this bucket (yes, it starts with a $)
   $s3cmd_wikivisual ls s3://wikivisual-upload-test/aaron/

   You should see a .zip file with the ID of your article like this:
   $s3cmd_wikivisual ls s3://wikivisual-upload-test/aaron/
   2016-01-20 18:15  10136878   s3://wikivisual-upload-test/aaron/109659.zip

If your assets are there, then you can move on to step 2 to run the transcoder.

If your assets are not there, you have to copy them from the production folder.
   * How do I do this?
   The zipped assets are in the path s3://wikivisual-upload which is viewable with:
   $s3cmd_wikivisual ls s3://wikivisual-upload/
   however they are within folders named after the contractor that uploaded them
   so it can be very hard to find unless you know the contractor name. you can ask
   the wikivisual team to help you with that. or you can look up in the db to see
   if that article already has had an upload attempt and get the name there, for example:
   select creator from wikivisual_article_status where article_id = 9728;
   will print out the creator of those images.

   to copy the zip file from one folder to another do this but with the correct paths:
   $s3cmd_wikivisual sync s3://wikivisual-upload/todd/4831.zip s3://wikivisual-upload-test/aaron/


2. Run the transcoder on that article ID with the -f argument to run on a single article
- if you run on the dev server it will use the wikivisual-upload-test bucket by default
- if you are testing a file with video assets, this command will get the zip file and submit a job
for the videos to be transcoded, then you will have to run the script a second time once the transcoding job
	is done in order for the videos to be added to mediawiki and the article to be edited.
	the second time you run this it is nice to add the -t flag which will only look at transcoded
	articles and not submit the videos for transcoding a second time

/opt/wikihow/scripts/whrun --user=apache -- php ~/wikihow/prod/maintenance/wikihow/transcoding/WikiVisualTranscoder.php -f 6918

Note: you can also use the -v flag  to get verbose output

data schema for reference:
    CREATE TABLE wikivisual_article_status ( 
		article_id INT(10) UNSIGNED NOT NULL, 
		status INT UNSIGNED not null default 0, 
		creator VARCHAR(32) NOT NULL default '', 
		reviewed TINYINT(3) UNSIGNED NOT NULL default 0, 
		processed VARCHAR(14) NOT NULL default '', 
		vid_processed VARCHAR(14) NOT NULL default '', 
		gif_processed VARCHAR(14) NOT NULL default '',
		gif_processed_error VARCHAR(14) NOT NULL default '',
		photo_processed VARCHAR(14) NOT NULL default '', 
		warning TEXT not null, 
		error TEXT not null, 
		article_url VARCHAR(255) NOT NULL default '', 
		retry TINYINT(3) UNSIGNED NOT NULL default 0, 
		vid_cnt INT(10) UNSIGNED NOT NULL default 0, 
		photo_cnt INT UNSIGNED NOT NULL default 0, 
		replaced INT(10) UNSIGNED NOT NULL default 0, 
		steps INT(10) UNSIGNED NOT NULL default 0,
		staging_dir VARCHAR(255) NOT NULL default '',
		incubation TINYINT(3) UNSIGNED NOT NULL default 0,
		leave_old_media TINYINT(3) UNSIGNED NOT NULL default 0,
		PRIMARY KEY (article_id) 
	) ENGINE=InnoDB DEFAULT CHARSET=latin1; 
		
	CREATE TABLE wikivisual_vid_names ( 
		filename VARCHAR(255) NOT NULL, 
		wikiname VARCHAR(255) NOT NULL 
	) ENGINE=InnoDB DEFAULT CHARSET=latin1; 
	
	CREATE TABLE wikivisual_photo_names ( 
		filename VARCHAR(255) NOT NULL, 
		wikiname VARCHAR(255) NOT NULL 
	) ENGINE=InnoDB DEFAULT CHARSET=latin1; 
	
	CREATE TABLE wikivisual_vid_transcoding_status ( 
		article_id INT(10) UNSIGNED NOT NULL, 
		aws_job_id VARCHAR(32) NOT NULL default '', 
		aws_uri_in TEXT, 
		aws_uri_out TEXT, 
		aws_thumb_uri TEXT, 
		processed VARCHAR(14) NOT NULL default '', 
		status VARCHAR(32) NOT NULL default '', 
		status_msg TEXT NOT NULL, 
		PRIMARY KEY (aws_job_id), 
		KEY article_id (article_id) 
	) ENGINE=InnoDB DEFAULT CHARSET=latin1

*/

require_once __DIR__ . '/../../commandLine.inc';

global $IP;

require_once "$IP/extensions/wikihow/common/S3.php";
require_once "$IP/extensions/wikihow/DatabaseHelper.class.php";
require_once __DIR__ . "/Utils.php";
require_once __DIR__ . "/Mp4Transcoder.php";
require_once __DIR__ . "/ImageTranscoder.php";

use Aws\Common\Aws;
use Guzzle\Http\EntityBody;

/**
* Setup steps (these have already been done but are required for dev or live)
*  1. Create AWS_BUCKET - already exists
*  2. Create MEDIA_USER - already exists
*  3. Create schema by running above mentioned DDL - already exists on dev server
* for running on dev server the following exist:
*  4. Create DEFAULT_STAGING_DIR - already exists on dev server
*  5. Use AWS_BUCKET='wikivisual-upload-test' - this is handled by $wgIsDevServer so don't need to alter anything
*  6. In WHVid.body.php::getVidUrl use  $domain = self::S3_DOMAIN_DEV; -this is only for viewing the created videos and this code has changed..TODO update the js to handle the dev domain
*  7. In WikiVideo.class.php use const AWS_PROD_BUCKET = 'wikivideo-prod-test'; - now handled by wgIsDevServer so no need to do anything
*
* to run this on dev server:
*   1. cd ${REPOPATH}/wikihow/prod/maintenance/wikihow/transcoding
*   2. if you want to pass a specific ID into the script you can run it like this:
*      whrun --user=apache -- php WikiVisualTranscoder.php -f 1089411
*      otherwise run it like this:
*      whrun --user=apache -- php WikiVisualTranscoder.php
*
*  there are two amazon s3 servers. One contains the raw uploaded assets, which is viewed
*  from the command line with this command:
*  $s3cmd_wikivisual ls;
*  the important buckets are:
*  s3://wikivisual-upload
*  s3://wikivisual-upload-test
*  which you can see defined at the top of this file and contain the assets to be processed
*
*  if you want to copy data into the test bucket from the main one for testing then execute:
*  $s3cmd_wikivisual sync s3://wikivisual-upload/todd/4831.zip s3://wikivisual-upload-test/aaron/
*  which will copy the upload for article 4831 from the live bucket to the test bucket
*  the folder structure is organized by the name of the contractor on the live bucket, but
*  on the test one we just have a few names of engineers so pick one that already exists (like aaron)
*
*  The article you are testing on must exist on the aws buckets you defined in AWS_BUCKET or else
*  when you run the transcoder no assets will be found (esp important on dev)
*
*  The other relevant bucket is the one which has the transcoded files and the final files we
*  will eventually be serving up.  this is viewed with another command:
*  $s3cmd ls
*
*  Note that this script will do transcoding of videos in one run, mark the article
*  in the db with a status code, then in subsequent runs it will look to see if the
*  transcoding has completed.
*  TODO you can just fake the transcoding part if you already have the converted files (how?)
*/

class WVFileException extends Exception { }

class WikiVisualTranscoder {

	var $mAwsBucket = '';
	var $mAwsBucketPrefix = null;
	
	const DEFAULT_STAGING_DIR = '/data/wikivisual';
	const MEDIA_USER = 'wikivisual'; 
	const AWS_BACKUP_BUCKET = 'wikivisual-backup'; 

	const AWS_TRANSCODING_IN_BUCKET = 'wikivideo-transcoding-in';
	const AWS_TRANSCODING_OUT_BUCKET = 'wikivideo-transcoding-out';
	
	// Prod Pipeline
	const AWS_PIPELINE_ID = '1373317258162-6npnrl';

	const VIDEO_WARNING_MIN_WIDTH = 500;
	const VIDEO_EXTENSION = '.360p.mp4';
	const DEFAULT_VIDEO_WIDTH = '500px';
	const DEFAULT_VIDEO_HEIGHT = '375px';
	
	// wikivideo - Generic 360p 16:9
	const TRANSCODER_360P_16x9_PRESET = '1373409713520-t0nqq0';
	const TRANSCODER_360P_16x9_PRESET_AUDIO = '1503961241607-9swq73';
	
	const ERROR_MIN_WIDTH = 3200;
	const ERROR_MIN_WIDTH_VIDEO_COMPANION = 1920;
	const ERROR_MAX_IMG_DIMEN = 10000;
	
	const PHOTO_LICENSE = 'WikiVisual';
	const SCREENSHOT_LICENSE = 'Screenshot';

	// Fri Feb  1 14:19:26 PST 2013
	const REPROCESS_EPOCH = 1359757162;
	
	const STATUS_ERROR = 10;
	const STATUS_PROCESSING_UPLOADS = 20;
	const STATUS_TRANSCODING = 30;
	const STATUS_COMPLETE = 40;
	
	const FINALSTR = 'final';
	const FINALSTRLBL = 'Final';
	const FINISHED = 'Finished';
	
	const ADMIN_CONFIG_INCUBATION_LIST = 'wikivisual-incubation-list';
	
	static $DEBUG = false;
    static $exitAfterNumArticles = 1;
	static $stagingDir = '';
	static $debugArticleID = '';
	static $videoExts = array( 'mp4', 'avi', 'flv', 'aac', 'mov' );
	static $assocVideoExts;
	static $imgExts = array( 'jpg', 'jpeg', 'gif' );
	static $assocImgExts;
	static $excludeArticles = array();
	static $excludeUsers = array( 'old', 'backup' );
	static $aws = null;

	private $mp4Transcoder;
	private $imageTranscoder;
	
	// prints a log message optionally with a value
	public static function log( $msg, $debugOverride = false, $msgType = "DEBUG", $val = null, $depth = 3 ) {
		if ( (self::$DEBUG == false && $debugOverride != true) || empty( $msg ) ) {
			return;
		}

		$prefix = " ".wfGetCaller( $depth ).": ";
		$date = date( 'Y/M/d H:i' );
		$msg = $msgType. " " . $date . $prefix . $msg;

		// use decho to print the debug message if we have passed a value in to pring (like an array)
		if ( $val != null ) {
			// print with no br line ending and no prefix
			decho( $msg, $val, false, false );
		} else {
			echo "$msg\n";
		}
	}

	// a shortcut to call into the d method with a value
	public static function d( $msg, $val = null ) {
	    self::log( $msg, false, "DEBUG", $val );
	}
	
	public static function i( $msg, $val = null ) {
		self::log( $msg, true, "INFO", $val );
	}
	
	public static function e( $msg, $val = null ) {
		self::log( $msg, true, "ERROR", $val );
	}
	
	private static function timeDiffStr( $t1, $t2 ) {
		$diff = abs( $t1 - $t2 );
		if ( $diff >= 2 * 24 * 60 * 60 ) {
			$days = floor( $diff / ( 24 * 60 * 60 ) );
			return "$days days";
		} else {
			$hours = $diff / ( 60 * 60 );
			return sprintf( '%.2f hours', $hours );
		}
	}

	public static function getAws() {
		global $IP;
		if (is_null(self::$aws)) {
			// Create a service builder using a configuration file
			self::$aws = Aws::factory(array(
					'key'    => WH_AWS_IMAGES_ACCESS_KEY,
					'secret' => WH_AWS_IMAGES_SECRET_KEY,
					'region' => 'us-east-1'
			));
		}
		return self::$aws;
	}
	
	public static function getS3Service() {
		$aws = self::getAws();
		return $aws->get('S3');
	}

	public static function getStagingDir() {
		return self::$stagingDir;
	}

    public static function downloadImagePreview(&$image) {
        $svc = self::getS3Service();
        $downloadPath = self::$stagingDir . "/prev-" . Misc::genRandomString();
        
        $svc->getObject(array(
          'Bucket' => self::AWS_TRANSCODING_OUT_BUCKET,
          'Key'    => $image['aws_thumb_uri'],
          'command.response_body' => EntityBody::factory(fopen("$downloadPath", 'w+'))));
        $image['filename'] = $downloadPath;
    }

	public static function getGifStagingPath( $pageId ) {
		$path = WikiVisualTranscoder::dbGetStagingDir( $pageId );
		$path .= "/transcoded";
		return $path;
	}

	public static function downloadTranscodedVideos( $pageId, $videos ) {
		$path = self::getGifStagingPath( $pageId );
		if ( file_exists( $path ) ) {
			self::safeCleanupDir( $path );
		}

		// make directory recursively in case the parent doesn't exist
		$ret = mkdir( $path, 0777, true );
		if (!$ret) {
			$err = 'unable to create dir: ' . $path;
			return $err;
		}

		$svc = self::getS3Service();

		foreach( $videos as $video ) {
			if ( !isset($video['mediawikiName'] ) || !$video['mediawikiName'] ) {
				self::d("no mediawikiName for video", $video);
				continue;
			}
			$filePath = $path . "/". str_replace( " ", "-", $video['mediawikiName'] );
			$svc->getObject(array(
				'Bucket' => self::AWS_TRANSCODING_OUT_BUCKET,
				'Key'    => $video['aws_uri_out'],
				'command.response_body' => EntityBody::factory(fopen("$filePath", 'w+'))));
			$video['localPath'] = $filePath;
		}
	}

	/**
	 * Get database handle for reading or writing
	 */
	// TODO remove this function it is not needed just use wfGetDB every time you need the db
	public static function getDB($type) {
		static $dbw = null;
		static $dbr = null;
		if ('read' == $type) {
			if (!$dbr) $dbr = wfGetDB(DB_REPLICA);
			return $dbr;
		} elseif ('write' == $type) {
			if (!$dbw) $dbw = wfGetDB(DB_MASTER);
			return $dbw;
		} else {
			throw new Exception('bad db type');
		}
	}

    public static function isStillTranscoding($aid) {
        $dbr = WikiVisualTranscoder::getDB('read');
        return 0 < $dbr->selectField('wikivisual_vid_transcoding_status', 'count(*)', array('article_id' => $aid, "status != 'Complete'"));
    }

	/**
	 * Grab the status of all article by id
	 */
	private function dbGetArticlesUpdatedById( $pageId ) {
		$articles = array();
		$dbr = self::getDB('read');
		$table = 'wikivisual_article_status';
		$vars = array('article_id', 'processed', 'vid_processed', 'photo_processed', 'error', 'retry', 'titlechange');
		$conds = array( 'article_id' => $pageId );

		$res = $dbr->select( $table, $vars, $conds, __METHOD__ );

		foreach ($res as $row) {
			// convert MW timestamp to unix timestamp
			$row->processed = wfTimestamp(TS_UNIX, $row->processed);
			$row->vid_processed = wfTimestamp(TS_UNIX, $row->vid_processed);
			$row->photo_processed = wfTimestamp(TS_UNIX, $row->photo_processed);
			$articles[ $row->article_id ] = (array)$row;
		}
		return $articles;
	}
	/**
	 * Grab the status of all articles processed.
	 */
	private function dbGetArticlesUpdatedAll() {
		$articles = array();
		$dbr = self::getDB('read');
	
		$res = DatabaseHelper::batchSelect('wikivisual_article_status',
				array('article_id', 'processed', 'vid_processed', 'photo_processed', 'error', 'retry', 'titlechange'),
				'',
				__METHOD__,
				array(),
				DatabaseHelper::DEFAULT_BATCH_SIZE,
				$dbr);
	
		foreach ($res as $row) {
			// convert MW timestamp to unix timestamp
			$row->processed = wfTimestamp(TS_UNIX, $row->processed);
			$row->vid_processed = wfTimestamp(TS_UNIX, $row->vid_processed);
			$row->photo_processed = wfTimestamp(TS_UNIX, $row->photo_processed);
			$articles[ $row->article_id ] = (array)$row;
		}
		return $articles;
	}
	

	/**
	 * Set an article as processed in the database
	 */
	public static function dbSetArticleProcessed( $pageId, $creator, $error, $warning = "", $vidCnt = 0, $photoCnt = 0, $status = self::STATUS_ERROR, $stagingDir = "" ) {
		$dbw = self::getDB( 'write' );

		$incubation = self::isIncubated($creator) !== false ? 1 : 0;
		if ( !$warning ) {
			$warning = '';
		}
		if ( !$error ) {
			$error = '';
		}

		$processed = wfTimestampNow(TS_MW);
		$sql = 'REPLACE INTO wikivisual_article_status SET
		article_id=' . $dbw->addQuotes($pageId) . ',
			replaced=0,
			retry=0,
			error=concat(error, ' . ' ' . $dbw->addQuotes($error) . '),
			processed=' . $dbw->addQuotes($processed) . ',
			warning=concat(warning,' . ' ' .$dbw->addQuotes($warning) . '),
			article_url="",
			vid_cnt=' . $dbw->addQuotes($vidCnt) . ',
			photo_cnt=' . $dbw->addQuotes($photoCnt) . ',
			creator=' . $dbw->addQuotes($creator) . ',
			status=' . $dbw->addQuotes($status) . ',
			reviewed=0,
			staging_dir=' . $dbw->addQuotes($stagingDir) . ',
			incubation=' . $dbw->addQuotes($incubation) . ',
			steps=0';

		$dbw->query($sql, __METHOD__);
	}
	
	private function updateArticleStatus( $values, $condition ) {
		$dbw = self::getDB( 'write' );
		return $dbw->update( 'wikivisual_article_status', $values, $condition, __METHOD__ );
	}
	
	private function updateArticleStatusMediaProcessed($mediaTypeCol, $articleId) {
		$values = array(
			$mediaTypeCol => wfTimestampNow(TS_MW)
		);
		$conditions = array(
			'article_id' => $articleId
		);
		$this->updateArticleStatus($values, $conditions);
	}
	
	private function appendErrNWarning($articleId, &$err, &$warning) {
		$article = $this->dbGetArticle($articleId);
		if (count($article) == 0) {
			return null;
		}
		$a = $article[0];
		
		if ($a['error']) $err = $a['error']. ' ' .$err; 
		if ($a['warning']) $warning = $a['warning']. ' ' .$warning; 
	}
	
	private function updateArticleStatusPhotoProcessed( $articleId, $err, $warning, $url, $photoCnt, $replaced, $leaveOldMedia, $titleChange ) {
		$ts = wfTimestampNow( TS_MW );

		// set title change to either 0 or 2. 0 indicates that
		// there was no title change on this run, 2 indicates there was one
		// a value of 1 would indicate to reprocesses this again (which we do not want)
		if ( $titleChange ) {
			$titleChange = 2;
		} else {
			$titleChange = 0;
		}

		$values = array(
			'photo_processed' => $ts,
			'processed' => $ts,
			'article_url' => $url,
			'photo_cnt' => $photoCnt,
			'replaced' => $replaced,
			'leave_old_media' => $leaveOldMedia,
			'titlechange' => $titleChange
		);
		
		if ($err) {
			$values['status'] = self::STATUS_ERROR;
		} else {
			$values['status'] = self::STATUS_COMPLETE;
		}

		if( !empty( $warning ) || !empty( $err ) ) {
			$this->appendErrNWarning( $articleId, $err, $warning );
			if( !empty( $warning ) ) {
				$values['warning'] = $warning;
			}
			if( !empty( $err ) ) {
				$values['error'] = $err;
			}
		}
		
		$conditions = array( 'article_id' => $articleId );
		$this->updateArticleStatus( $values, $conditions );
	}
	
	private function updateArticleStatusHybridMediaProcessed( $pageId, $err, $warning, $photoCnt, $videoCnt, $replaced, $gifsAdded, $updateProcessed = FALSE ) {
		$title = Title::newFromID( $pageId );
		$url = $title->getFullURL();
		$ts = wfTimestampNow(TS_MW);
		$values = array(
			'photo_processed' => $photoCnt && $photoCnt > 0 ? $ts : '',
			'vid_processed' => $videoCnt && $videoCnt > 0 ? $ts : '',
			'gif_processed' => $gifsAdded ? $ts : '',
			'processed' => $ts,
// 			'warning' => $warning,
// 			'error' => $err,
			'article_url' => $url,
			'photo_cnt' => $photoCnt,
			'vid_cnt' => $videoCnt,
			'replaced' => $replaced
		);
		
		if ($err) {
			$values['status'] = self::STATUS_ERROR;
		} else {
			$values['status'] = self::STATUS_COMPLETE;
		}
		
		if(!empty($warning) || !empty($err)) {
			$this->appendErrNWarning($pageId, $err, $warning);
			if(!empty($warning)) $values['warning'] = $warning;
			if(!empty($err)) $values['error'] = $err;
		}
		
// 		if ($updateProcessed) $values['processed'] = $ts;
		
		$conditions = array(
			'article_id' => $pageId
		);
		$this->updateArticleStatus($values, $conditions);
	}
	
	/*
	 * update the article status table to mark what we are doing here
	 * make sure to always reset titlechange to 0 here or else we will keep
	 * reprocessing this title in the future
	 */
	private function updateArticleStatusVideoTranscoding( $articleId, $err, $warning, $url, $status, $leaveOldMedia, $titleChange ) {
		// set title change to either 0 or 2. 0 indicates that
		// there was no title change on this run, 2 indicates there was one
		// a value of 1 would indicate to reprocesses this again (which we do not want)
		if ( $titleChange ) {
			$titleChange = 2;
		} else {
			$titleChange = 0;
		}

		$values = array(
			'warning' => $warning,
			'error' => $err,
			'article_url' => $url,
			'status' => $status,
			'leave_old_media' => $leaveOldMedia,
			'titlechange' => $titleChange
		);

		$conditions = array(
			'article_id' => $articleId
		);
		if ( !$err ) {
			self::i( "setting article $url to status: $status" );
		}
		$this->updateArticleStatus( $values, $conditions );
	}
	
	private function dbGetTranscodingArticles() {
		$articles = array();
		$dbr = self::getDB('read');
		$rows = $dbr->select('wikivisual_article_status', array('*'), array('status' => self::STATUS_TRANSCODING), __METHOD__);
		foreach ($rows as $row) {
			$articles[] = get_object_vars($row);
		}
		return $articles;
	}
	
	private function dbGetArticle($articleId) {
		$articles = array();
		$dbr = self::getDB('read');
		$rows = $dbr->select('wikivisual_article_status', array('*'), array('article_id' => $articleId), __METHOD__);
		foreach ($rows as $row) {
			$articles[] = get_object_vars($row);
		}
		return $articles;
	}
	
	private static function dbGetStagingDir($articleId) {
		$dbr = self::getDB('read');
		$rows = $dbr->select('wikivisual_article_status', array('*'), array('article_id' => $articleId), __METHOD__);
		foreach ($rows as $row) {
			$rowAssocArr = get_object_vars($row);
			return $rowAssocArr['staging_dir'];
		}
		return null;
	}
	
	/**
	 * List articles on S3
	 */
	private function getS3Articles(&$s3, $bucket, $prefix = null) {
		$list = $s3->getBucket($bucket, $prefix);
	
		if ($prefix) {
			$prefix = $prefix . '/';
		}
		// compile all the articles into a list of files/zips from s3
		$articles = array();
		foreach ($list as $path => $details) {
			// remove prefix from path
			if ( $prefix && substr( $path, 0, strlen( $prefix ) ) == $prefix ) {
				$path = substr( $path, strlen( $prefix ) );
			} else if ( $prefix ) {
				// if we have a prefix but no prefix path then skip
				continue;
			}

			$leaveOldMedia = false;
			// match string: username/1234.zip
			if (!preg_match('@^([a-z][-._0-9a-z]{0,30})/([0-9]+)\.zip$@i', $path, $m)) {
				// match string: username/replace/1234.zip
				if (preg_match('@^([a-z][-._0-9a-z]{0,30})/replace/([0-9]+)\.zip$@i', $path, $m)) {
					$leaveOldMedia = true;
				} else {
					continue;
				}
			}
	
			list(, $user, $id) = $m;
			$id = intval($id);
			if (!$id) continue;

			if (in_array($user, self::$excludeUsers) 	// don't process anything in excluded people
				|| preg_match('@^[0-9]+$@', $user)) {	// don't allow usernames that are all digits
				continue;
			}
	
			if ( $user === 'replace' ) {
				$leaveOldMedia = true;
			}

			if ( $prefix ) {
				$user =  $prefix . $user;
			}

			// process the list of media files into a list of articles
			if ( !isset( $articles[$id] ) ) {
				$articles[$id] = array(
						'user' => $user,
						'time' => $details['time'],
						'files' => array(),  
						'zip' => 1,
						'leave_old_media' => $leaveOldMedia,
				);
			}
	
			if ( $user != $articles[$id]['user'] ) {
				$diffStr = self::timeDiffStr($articles[$id]['time'], $details['time']);
				if ($articles[$id]['time'] < $details['time']) {
					$warnUser = $articles[$id]['user'];
					$articles[$id]['time'] = $details['time'];
					$articles[$id]['user'] = $user;
					$articles[$id]['leave_old_media'] = $leaveOldMedia;
				} else {
					$warnUser = $user;
				}
				$articles[$id]['warning'] = "Reprocessing since user $warnUser uploaded $diffStr earlier\n";
			} else if ( $details['time'] > $articles[$id]['time'] ) {
				// same user but more recent upload (since users can upload in their main folder and in the replace subfolder
				// therefore we must update the time and the leave old media field
				$articles[$id]['time'] = $details['time'];
				$articles[$id]['leave_old_media'] = $leaveOldMedia;
			}
		}

		return $articles;
	}
	
	/**
	 * Unzip a file into a directory.
	 */
	private static function unzip($dir, $zip) {
		$err = '';
		$files = array();
		system("unzip -j -o -qq $dir/$zip -d $dir", $ret);
		if ($ret != 0) {
			$err = "error in unzipping $dir/$zip";
		}
		if (!$err) {
			if (!unlink($dir . '/' . $zip)) {
				$err = "error removing zip file $dir/$zip";
			}
		}
		if (!$err) {
			list($err, $files) = self::getUnzippedFiles($dir);
// 			$fileExts = array_unique(array_merge(self::$imgExts, self::$videoExts));
// 			$upcase = array_map('strtoupper', self::$fileExts);
// 			$exts = array_merge($upcase, self::$fileExts);
// 			$ret = glob($dir . '/*.{' . join(',', $exts) . '}', GLOB_BRACE);
// 			if (false === $ret) {
// 				$err = 'no files unzipped';
// 			} else {
// 				$files = $ret;
// 			}
		}
		return array($err, $files);
	}
	
	private static function getUnzippedFiles($dir) {
		$fileExts = array_unique(array_merge(self::$imgExts, self::$videoExts));
		$upcase = array_map('strtoupper', $fileExts);
		$exts = array_merge($upcase, $fileExts);
		$ret = glob($dir . '/*.{' . join(',', $exts) . '}', GLOB_BRACE);
		$err = null;
		$files = null;

		if (false === $ret) {
			$err = 'error unzipping files';
		} else if ( empty( $ret ) ) {
			$err = 'no valid files unzipped. check your file extensions (png is not supported)';
		} else {
			$files = $ret;
		}
		return array($err, $files);
	}

	private static function checkMediaFileName( $name, $pageId ) {
		$splitName = explode( "-", $name );
		if ( !$splitName[0] ) {
			throw new WVFileException( 'bad input file name: '.$name.' but expected '.$pageId .'-(STEPNUM).ext' );
		} else if ( count($splitName) < 1 ) {
			throw new WVFileException( 'input file is: '.$name.' but expected '.$pageId .'-'.$splitName[0] );
		} else if ( $splitName[0] != $pageId ) {
			throw new WVFileException( 'input file is: '.$name.' but expected '.$pageId .'-'.end($splitName) );
		}
	}
	
	private static function splitSrcMediaFileList( $files, $pageId ) {
		$photoList = array();
		$videoList = array();

		foreach ( $files as $file ) {
			$name = basename( $file );
			self::checkMediaFileName( $name, $pageId );
			$arr = array (
				'name' => $name,
				'filename' => $file
			);
			if ( array_key_exists ( Utils::getFileExt ( $file ), self::$assocImgExts ) ) {
				$photoList[] = $arr;
			}
			if ( array_key_exists ( Utils::getFileExt ( $file ), self::$assocVideoExts ) ) {
				$videoList[] = $arr;
			}
		}
		return array($photoList, $videoList);
	}
	
	private static function zip($dir, $zip) {
		$err = '';
		system("(cd $dir; zip -9 -q $zip *)", $ret);
		if ($ret != 0) {
			$err = "problems while executing zip command to create $zip";
		}
		return $err;
	}
	
	/**
	 * Upload a file to S3
	 */
	public static function postFile( $s3, $file, $uri, $bucket ) {
		$err = '';
		$ret = $s3->putObject( array( 'file' => $file ), $bucket, $uri );
		if ( !$ret ) {
			$err = "unable to upload $file to S3 in bucket [$bucket]";
		}
		return $err;
	}
	
	
	/**
	 * Download files from S3
	 */
	private function pullFiles($id, &$s3, $prefix, &$files) {
		$err = '';
		$dir = self::$stagingDir . '/' . $id . '-' . mt_rand();
		$ret = mkdir($dir);
		if (!$ret) {
			$err = 'unable to create dir: ' . $dir;
			return array($err, '');
		}
	
		foreach ($files as &$file) {
			$aws_file = $prefix . $file;
			$file = preg_replace('@/@', '-', $file);
			$local_file = $dir . '/' . $file;
			$ret = $s3->getObject($this->mAwsBucket, $aws_file, $local_file);
			if (!$ret || $ret->error) {
				$err = "problem retrieving file from S3: s3://" . $this->mAwsBucket . "/$aws_file";
				break;
			}
		}
		return array($err, $dir);
	}
	
	/**
	 * Remove tmp directory.
	 */
	private static function safeCleanupDir($dir) {
		$staging_dir = self::$stagingDir;
		if ($dir && $staging_dir && strpos($dir, $staging_dir) === 0) {
            self::i(">>> safeCleanupDir($dir)");
			system("rm -rf $dir");
		}
	}
	
	
	/**
	 * Cleanup and remove all old copies of photos.
	 * If there's a zip file and
	 * a folder, delete the folder.
	 */
	private function doS3Cleanup() { 
		$s3 = new S3 ( WH_AWS_WIKIVISUAL_UPLOAD_ACCESS_KEY, WH_AWS_WIKIVISUAL_UPLOAD_SECRET_KEY );
		$src = $this->getS3Articles ( $s3, $this->mAwsBucket, $this->mAwsBucketPrefix );
		foreach ( $src as $id => $details ) {
			if ($details ['zip'] && $details ['files']) {
				if ( $details['leave_old_media'] == true && $user != 'replace' ) {
					$uri = $details ['user'] . '/replace/' . $id . '.zip';
				} else {
					$uri = $details ['user'] . '/' . $id . '.zip';
				}
				$count = count ( $details ['files'] );
				if ($count <= 1) {
					$files = join ( ',', $details ['files'] );
					self::i("not enough files ($count) to delete $uri: $files");
				} else {
					self::i("deleting $uri");
					$s3->deleteObject ( $this->mAwsBucket, $uri );
				}
			}
		}
	}
	
	private static $incubationCreators = null; //to cache creators list
	public static function isIncubated($creator) {
		if (!self::$incubationCreators) {
			$val = ConfigStorage::dbGetConfig(self::ADMIN_CONFIG_INCUBATION_LIST);
			$incubationCreators = preg_split('@\s+@', trim($val));
		}
		return array_search($creator, $incubationCreators);
	}
	
	private function isHybridMedia($articleStatusRow) {
		if (is_null($articleStatusRow)) return NULL;
		return $articleStatusRow['vid_cnt'] > 0 && $articleStatusRow['photo_cnt'] > 0;
	}

	private function isReadyForWikiTextProcessing($articleStatusRow) {
		$article = $this->dbGetArticle($articleStatusRow['article_id']);
		if (count($article) == 0) {
			return null;
		}
		$a = $article[0];
		
		if ($a['vid_cnt'] > 0 && !isset($a['vid_processed'])) return false;
		if ($a['photo_cnt'] > 0 && !isset($a['photo_processed'])) return false;
		return true;
	}
	
	private function processTranscodingArticles() {
		$articles = $this->dbGetTranscodingArticles();
		foreach ($articles as $a) {
			$err = null;
			
			$aid = $a['article_id'];
			$creator = $a['creator'];
            self::d("processTranscodingArticles, processing article: ". $aid);
			
			$retCode = 0;
			$msg = '';
			
			$isHybridMedia = $this->isHybridMedia($a);
			if (is_null($isHybridMedia)) {
				$err = 'Coluld not get article row data!';
                self::d("processTranscodingArticles ". $err);
			}
			
            self::d( "Handle video articles by mp4Transcoder->processTranscodingArticle($aid, $creator);");
            self::d( "a['vid_cnt']", $a['vid_cnt'] );
            self::d( "a['vid_processed']", $a['vid_processed'] );
            self::d( "leave_old_media", $a['leave_old_media'] );
            self::d( "err:", $err );
			if (!$err
				&& isset($a['vid_cnt']) && $a['vid_cnt'] > 0 
				&& empty($a['vid_processed'])) { //handle video articles
				list($retCode, $msg) = $this->mp4Transcoder->processTranscodingArticle( $aid, $creator );

                self::d("mp4Transcoder->processTranscodingArticle ret code : ". $retCode);
				if ($retCode == self::STATUS_ERROR) {
					$err = $msg;
				} else if ($retCode == self::STATUS_COMPLETE) {
					$this->updateArticleStatusMediaProcessed('vid_processed', $aid);
				}
			}
			//presently no need to do this processing for imageonly as their transcoding process
			//is synchronous and not a real transcoding
            $stageDir = '';
			if ($err) {
				self::dbSetArticleProcessed( $aid, $creator, $err );
			} elseif (self::isStillTranscoding($aid)) {
                self::d("isStillTranscoding is true so skip this article ". $aid); 
				continue;
			} else {
				if ($this->isReadyForWikiTextProcessing($a)) {
                    self::d("isReadyForWikiTextProcessing result = ready");
					$photoCnt = $a['photo_cnt'] ? $a['photo_cnt'] : 0;
					$vidCnt = $a['vid_cnt'] ? $a['vid_cnt'] : 0;
					$leaveOldMedia = $a['leave_old_media'];
					$titleChange = $a['titlechange'];
					list( $err, $warning, $replaced, $gifsAdded ) = $this->processWikitext( $aid, $creator, $photoCnt, $vidCnt, $stageDir, $leaveOldMedia, $titleChange );
					$this->updateArticleStatusHybridMediaProcessed( $aid, $err, $warning, $photoCnt, $vidCnt, $replaced, $gifsAdded );
                    if ( !empty( $stageDir ) ) {
						self::safeCleanupDir( $stageDir );
                    }
				} else {
					$err = 'Unknown error occured while checking if ready for wiki text processing';
					self::dbSetArticleProcessed( $aid, $creator, $err );
                    self::d("isReadyForWikiTextProcessing not ready, err=$err");
				}
			}
		}
	}
	
	// get articles that have been processed from the DB
	private function getProcessedArticles() {
		$debug = self::$debugArticleID;
		$result = array();
		if ( $debug ) {
			$result = $this->dbGetArticlesUpdatedById( self::$debugArticleID );
		} else {
			$result = $this->dbGetArticlesUpdatedAll();
		}
		return $result;
	}
	
	/**
	 * Process images on S3 instead of from the images web server dir
	 */
	private function processS3Media() {
		// get list of the articles in the s3 bucket
		$s3 = new S3( WH_AWS_WIKIVISUAL_ACCESS_KEY, WH_AWS_WIKIVISUAL_SECRET_KEY );
		$articles = $this->getS3Articles( $s3, $this->mAwsBucket, $this->mAwsBucketPrefix );

		// also get processed articles from DB to see if we need to retry or flag them
		$processed = $this->getProcessedArticles();

		$debug = self::$debugArticleID;
        $articlesProcessed = 0;

		// process all articles
		foreach ( $articles as $id => $details ) {
			if ( $debug && $debug != $id ) {
				continue;
			}
			
			if ( @$details['err'] ) {
				if ( !$processed[$id] ) {
					self::dbSetArticleProcessed( $id, $details['user'], $details['err'] );
				}
				continue;
			}
			// if article needs to be processed again because new files were
			// uploaded, but article has already been processed, we should
			// just flag as a retry attempt
			if ( !$debug
				&& isset( $processed[$id] )
				&& !$processed[$id]['retry']
				&& $processed[$id]['processed'] < $details['time'] ) {
				if ($details ['time'] >= self::REPROCESS_EPOCH) {
					$processed[$id]['retry'] = 1;
					$processed[$id]['error'] = '';
				} else {
                    self::d("don't reprocess stuff from before a certain point in time: Article id :". $id);
					// don't reprocess stuff from before a certain point in time
					continue;
				}
			}
			
			// if this article was already processed, and nothing about its
			// images has changes, and it's not set to be retried, don't
			// process it again
			if ( !$debug
				&& isset( $processed[$id] )
				&& ! $processed[$id]['retry']
				&& $processed[$id]['titlechange'] != 1
				&& $processed[$id]['processed'] > $details['time'] ) {
                self::d("if this article was already processed, and nothing about its images has changes, and it's not set to be retried, don't process it again:". $id .", processed[id]['processed']=". $processed[$id]['processed']. " > details['time']=". $details['time']);
				continue;
			}
			
			// if article is not on Wikiphoto article exclude list
			if ( WikiPhoto::checkExcludeList ( $id ) ) {
				$err = 'Article was found on Wikiphoto EXCLUDE list';
				self::dbSetArticleProcessed ( $id, $details ['user'], $err );
				continue;
			}
			
			// pull zip file into staging area
			$stageDir = '';
			$photoList = array ();
			$videoList = array();
			$leaveOldMedia = $details['leave_old_media'];
			if ( !$details['zip'] ) { 
				continue;
			}

			$titleChange = false;
			if ( isset( $processed[$id] ) ) {
				// only set title change to true here if the value is 1. it may be 0 if it is not a title changeo
				// and it may be 2 if it was previouslly processed as a title change
				$titleChange = $processed[$id]['titlechange'] == 1;
			}
			$prefix = $details['user'] . '/';
			if ( $leaveOldMedia == true && $details['user'] != 'replace' ) {
				$prefix .= 'replace/';
			}
			$zipFile = $id . '.zip';
			$files = array( $zipFile );

			list( $err, $stageDir ) = $this->pullFiles( $id, $s3, $prefix, $files );
			if ( !$err ) {
				list( $err, $files ) = $this->unzip( $stageDir, $zipFile );
			}
			if ( !$err ) {
				try {
					list( $photoList, $videoList ) = self::splitSrcMediaFileList( $files, $id );
				} catch (WVFileException $e) {
					$err = $e->getMessage();
				}
			}
			
			if ( !$err && in_array ( $id, self::$excludeArticles ) ) {
				$err = 'Forced skipping this article because there was a repeated error when processing it';
			}
			self::d("PhotoList size ". count($photoList) . ", VideoList size ". count($videoList) ." err=$err");
				
			$title = Title::newFromID( $id );
			$titleStr = "";
			if ( !$title || !$title->exists() ) {
				$err = "Title with id: $id does not exist";
			} else {
				$titleStr =  ' (' . $title->getText() . ')';
			}

			if ( $err ) {
				self::dbSetArticleProcessed( $id, $details ['user'], $err );
			}

			$isHybridMedia = false;
			$photoCnt = 0;
			$vidCnt = 0;
			if ( !$err ) {
				$warning = @$details['warning'];
				$photoCnt = count( $photoList );
				$vidCnt = count( $videoList );
				
				self::dbSetArticleProcessed( $id, $details ['user'], $err, $warning, $vidCnt, $photoCnt, self::STATUS_PROCESSING_UPLOADS, $stageDir);
				$isHybridMedia = $photoCnt > 0 && $vidCnt > 0;
                self::d( "isHybridMedia", var_export( $isHybridMedia, true ) );
				//start processing uploads
				$url = $title->getFullURL();
				if ($photoCnt > 0 && $vidCnt <= 0) {
					list( $err, $warning, $replaced ) = 
						$this->imageTranscoder->processMedia( $id, $details ['user'], $photoList, $warning, $isHybridMedia, $leaveOldMedia, $titleChange );

					if ( $err ) {
						$photoCnt = 0;
					}

					$this->updateArticleStatusPhotoProcessed( $id, $err, $warning, $url, $photoCnt, $replaced, $leaveOldMedia, $titleChange );
				} else if ( $vidCnt > 0 ) {
					list( $err, $status ) = $this->mp4Transcoder->processMedia( $id, $details['user'], $videoList, $warning, $isHybridMedia );
					$this->updateArticleStatusVideoTranscoding( $id, $err, $warning, $url, $status, $leaveOldMedia, $titleChange );
				}
                $articlesProcessed ++;
			}
			
			//don't cleanup if isHybridMedia is present and zip file contains images.
			if ( !empty($stageDir) && $isHybridMedia === false ) {
				self::safeCleanupDir( $stageDir );
			}
			
			$errStr = $err ? ', err=' . $err : '';
			$mediaCount = count( $files );
			self::i("processed: {$details['user']}/$id$titleStr original mediaFilesCount=$mediaCount $errStr");
            if ( self::$DEBUG !== false && self::$exitAfterNumArticles > 0 && $articlesProcessed >= self::$exitAfterNumArticles ) {
                self::d( "articlesProcessed $articlesProcessed >= self::\$exitAfterNumArticles ". self::$exitAfterNumArticles .", hence stopping further processing of articles if there are any." );
                break;
            }
		}
	}
	
	private function processWikitext( $pageId, $creator, $photoCnt, $vidCnt, &$stagingDir, $leaveOldMedia, $titleChange ) {
        self::d("pageId=$pageId, creator=$creator, photoCnt=$photoCnt, vidCnt=$vidCnt, leaveOldMedia=$leaveOldMedia, titleChange=$titleChange");
		try {
			//get all essential data from related media handlers
			$photoList = null;
			if ( $photoCnt > 0 ) {
				$stagingDir = self::dbGetStagingDir($pageId);
				if ( $stagingDir ) {
					list( $err, $files ) = self::getUnzippedFiles( $stagingDir );
					list( $photoList, ) = self::splitSrcMediaFileList( $files, $pageId );
				}
				self::d( "stagingDir=$stagingDir, photoCnt=$photoCnt actual file cnt=". count( $photoList ) );
			}
		} catch ( WVFileException $e ) {
			return [ $e->getMessage(), null, 0, 0, 0 ];
		}
		
		$videoList = null;
		if ( $vidCnt > 0 ) {
			$videoList = $this->mp4Transcoder->dbGetTranscodingArticleJobs( $pageId );
		}
	
		$ret = $this->mp4Transcoder->processHybridMedia( $pageId, $creator, $videoList, $photoList, $leaveOldMedia, $titleChange );
		self::d( "result", $ret );
		
		return $ret;
	}
	
	public function main() {
		global $wgLanguageCode, $wgIsDevServer;
		date_default_timezone_set('America/Los_Angeles');
		

		if ( $wgIsDevServer ) {
			$this->mAwsBucket = 'wikivisual-upload-test';
		} else {
			$this->mAwsBucket = 'wikivisual-upload';
		}

		if ( $wgLanguageCode != 'en' ) {
			$this->mAwsBucketPrefix = $wgLanguageCode;
		}

		if ( !self::$assocVideoExts ) {
			self::$assocVideoExts = Utils::arrToAssoArr(self::$videoExts);
			self::$assocImgExts = Utils::arrToAssoArr(self::$imgExts);
		}
		
		$opts = getopt( 'bcd:e:f:vt',
			array (
				'backup',
				'cleanup',
				'staging-dir:',
				'exclude-article-id:',
				'force:',
				'verbose',
				'transcoding'
			)
		);

		$doCleanup = isset( $opts ['c'] ) || isset ( $opts ['cleanup'] );
		
		self::$stagingDir = @$opts ['d'] ? @$opts ['d'] : @$opts ['staging-dir'];
		if ( empty( self::$stagingDir ) )
			self::$stagingDir = self::DEFAULT_STAGING_DIR;
		
		if ( array_key_exists( 'v', $opts ) ) {
			self::$DEBUG = true;
			self::d( "running script in debug mode" );
		}

		self::d( "using aws bucket", $this->mAwsBucket);
		if ( $this->mAwsBucketPrefix ) {
			self::d( "using aws bucket prefix", $this->mAwsBucketPrefix);
		}

		self::$debugArticleID = @$opts ['f'] ? @$opts ['f'] : @$opts ['force'];
		if (self::$debugArticleID) {
			self::d( "running on article id", self::$debugArticleID);
		}
		$processTranscodingOnly = false;
		if ( array_key_exists( 't', $opts ) ) {
			self::d( "skipping new articles, will only process transcoding jobs" );
			$processTranscodingOnly = true;
		}

		$skipID = @$opts ['e'] ? $opts ['e'] : @$opts ['exclude-article-id'];
		if ( $skipID )
			self::$excludeArticles[] = $skipID;
		
		if ( $_ENV ['USER'] != 'apache' ) {
			self::e( "script must be run as part of wikivisual-process-media.sh" );
			exit();
		}

		self::d( "running in lang", $wgLanguageCode );
		
		Misc::loginAsUser ( self::MEDIA_USER );
		
		if ( $doCleanup ) {
			$this->doS3Cleanup();
		} else {
			$this->mp4Transcoder = new Mp4Transcoder();
			$this->imageTranscoder = new ImageTranscoder();
				
			// this will look for articles which had jobs sent to s3 for transcoding
			$this->processTranscodingArticles();

			// now process new incoming assets and queue their transcoding jobs if video
			// or simply place them into the system if they are video only
			if ( !$processTranscodingOnly ) {
				$this->processS3Media();
			}
		}
	}
}
$wmt = new WikiVisualTranscoder();
$wmt->main();
