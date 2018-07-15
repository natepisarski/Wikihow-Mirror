<?

class WikiVideo {

	const AWS_PROD_BUCKET = 'wikivideo-prod';
	const AWS_PROD_TEST_BUCKET = 'wikivideo-prod-test';

	public static function getBucket() {
		global $wgIsDevServer;
		if ($wgIsDevServer) {
			return self::AWS_PROD_TEST_BUCKET;
		}

		return self::AWS_PROD_BUCKET;
	}

	private static $s3 = null;

	private static function getS3() {
		global $IP;
		require_once("$IP/extensions/wikihow/common/S3.php");
		if (is_null(self::$s3)) {
			self::$s3 =  new S3(WH_AWS_WIKIVIDEO_PROD_ACCESS_KEY, WH_AWS_WIKIVIDEO_PROD_SECRET_KEY);
		}
		return self::$s3;
		
	}
	/*
	* Returns the full path for a file if it exists, '' otherwise
	*/
	public static function findFile($filename) {
		$filePath = '';
		$s3 = self::getS3();
		$uri = WHVid::getVidFilePath($filename);
		if ($info = $s3->getObjectInfo(self::getBucket(), $uri)) {
			$filePath = $uri;
		}

		return $filePath;
	}

	/*
	* Copies file from an amazon bucket to the prod bucket. Will determine
	* appropriate hashed path given filename.
	* 
	* Returns an associative array with an 'error' and 'filePath' key. 
	* Returns an error if a file exists with the given filename.
	*/
	public static function copyFileToProd($srcBucket, $srcUri, $filename) {
		$ret = array('error' => '', 'filePath' => '');
		if (self::fileExists($filename)) {
			$ret['error'] = "File with name $filename already exists in repo";
			return $ret;
		}

		$s3 = self::getS3();
		$bucket = self::getBucket();
		$uri = WHVid::getVidFilePath($filename); 
		$result = $s3->copyObject($srcBucket, $srcUri, $bucket, $uri, S3::ACL_PUBLIC_READ);
		if (!$result) {
			$ret['error'] = "Unable to copy $filename to S3 path $uri";
		} else {
			$ret['filePath'] = $uri;
		}
		return $ret;
	}

	public static function fileExists($filename) {
		return strlen(self::findFile($filename));
	}

}
