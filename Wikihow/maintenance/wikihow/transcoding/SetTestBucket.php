<?php

require_once __DIR__ . '/../../Maintenance.php';
global $IP;
require_once "$IP/extensions/wikihow/common/S3.php";

// this script will let you search for the zip assets for a wikivisual article on s3
// it will look in the db, in the test bucket, then if not found there in the main s3 bucket
// it looks at the main one last since it takes much longer
// it will simply print out the s3 path to the latest version of the asset if found and can
// optionall copy it to the test bucket
class SetTestBucket extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "set up test bucket on s3 for testing the Fred system";

		// which page we are testing
		$this->addOption( 'id', 'page id to act on', true, true, 'p' );
		$this->addOption( 'force', 'copy file even if it is already on dev', false, false, 'f' );
		$this->addOption( 'replace', 'in the output path use replace', false, false, 'r' );
    }

	private $s3 = null;

	private function findFileInDev( $pageId ) {
		$bucket = self::AWS_BUCKET_TEST;
		return $this->findLatestFileWithBucket( $pageId, $bucket );
	}

	private function findLatestFile( $pageId ) {
		$bucket = self::AWS_BUCKET_PROD;
		return $this->findLatestFileWithBucket( $pageId, $bucket );
	}

	private function findLatestFileWithBucket( $pageId, $bucket ) {
		$list = $this->s3->getBucket($bucket);

		// compile all the articles into a list of files/zips from s3
		$articles = array();
		foreach ($list as $path => $details) {

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
			$details['creator'] = $user;
			$id = intval($id);
			if ( $id == $pageId )  {
				if ( count( $articles ) == 0 ) {
					$articles[] = $details;
				} else if ( intval( $details['time'] ) > intval( $articles[0]['time'] ) ) {
					$articles[0] = $details;
				}
			} else {
				continue;
			}
		}

		return $articles[0]['creator'];
	}

	const AWS_BUCKET_PROD = 'wikivisual-upload';
	const AWS_BUCKET_TEST = 'wikivisual-upload-test';


	private static function getPath( $creator, $pageId, $replace = false, $dev=false) {
		$bucket = self::AWS_BUCKET_PROD;
		if ( $dev ) {
			$bucket = self::AWS_BUCKET_TEST;
		}
		$path = "s3://".$bucket."/".$creator."/".$pageId.".zip";
		return $path;
	}

	public function execute() {
		global $wgIsDevServer;
		$dev = $wgIsDevServer;

		$pageId = $this->getOption( 'id' );
		$force = $this->getOption( 'force' );
		$replace = $this->getOption( 'replace' );
		$copy = $this->getOption( 'copy' );

		// first see if the file is already on the test bucket
		$this->s3 = new S3( WH_AWS_WIKIVISUAL_ACCESS_KEY, WH_AWS_WIKIVISUAL_SECRET_KEY );

		$creator = $this->findFileInDev( $pageId );
		if ( $creator && !$force ) {
			$path = self::getPath( $creator, $pageId, true );
			echo $path."\n";
			exit();
		}

		// first look for the uploader name in the db....
		if ( true || !$dev ) {
			$dbr = wfGetDB( DB_SLAVE );
			$table = "wikivisual_article_status";
			$vars = array("creator");
			$conds = array( "article_id" => $pageId );
			$res = $dbr->selectRow( $table, $vars, $conds, __METHOD__ );
			if ( $res->creator ) {
				$creator = $res->creator;
			}
			// if it is in the db we have to confirm it is on the s3 file system
			$prefix = $creator."/".$pageId.".zip";

			$ret = $this->s3->getObjectInfo( self::AWS_BUCKET_PROD, $prefix );
			if ( !$ret ) {
				$creator = "";
			}
		}

		if ( !$creator ) {
			// search on s3.. much slower
			$creator = $this->findLatestFile( $pageId );
		}
		$fromPath = self::getPath( $creator, $pageId );
		echo $fromPath."\n";

		$srcPrefix = $creator."/".$pageId.".zip";
		$dstPrefix = "test/".$pageId.".zip";
		if ( $copy ) {
			$ret = $this->s3->copyObject( self::AWS_BUCKET_PROD, $srcPrefix, self::AWS_BUCKET_TEST, $dstPrefix );
		}
	}
}


$maintClass = "SetTestBucket";
require_once RUN_MAINTENANCE_IF_MAIN;

