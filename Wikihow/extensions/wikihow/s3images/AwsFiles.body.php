<?php

use Aws\Common\Aws;

class AwsFiles {

	static $aws = null;

	// Create a new AWS object connected to the account we want.
	// Copied from maintenance/transcoding/...
	private static function getAws() {
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

	// Get a reference to the AWS/S3 service connection
	// Taken from maintenance/transcoding/...
	private static function getS3Service() {
		$aws = self::getAws();
		return $aws->get('S3');
	}

	// Add language code to any paths that start with /images/
	private static function translateS3path($s3obj) {
		global $wgLanguageCode;
		return preg_replace('@^/images/@', '/images_' . $wgLanguageCode . '/', $s3obj);
	}

	public static function uploadByUrl($fetchUrl, $fetchHost, $filePath, $s3obj, $mimeType, $bucket = '', $bPrint = false) {
		wfDebugLog('aws', "uploadByUrl, fetchUrl=$fetchUrl, fetchHost=$fetchHost, filePath=$filePath, s3obj=$s3obj");

		$tempFile = '';
		$curlError = false;
		if (!file_exists($filePath) || filesize($filePath) <= 0) {
			$fp = fopen($filePath, 'w');
			if (!$fp) {
				$tempFile = tempnam('/tmp', 'upload-by-url-');
				$filePath = $tempFile;
				$fp = fopen($tempFile, 'w');
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $fetchUrl);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_TIMEOUT, 90);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $fetchHost));
			$result = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curlError = !$result || $httpCode != 200;

			wfDebugLog('aws', "uploadByUrl, downloaded=$fetchUrl to filePath=$filePath. error: " . curl_error($ch));
			curl_close($ch);
			fclose($fp);
		}

		if (!$curlError && file_exists($filePath) && filesize($filePath) > 0) {
			wfDebugLog('aws', "uploadByUrl, uploading=$filePath to $s3obj");
			self::uploadFile($filePath, $s3obj, $mimeType, $bucket, $bPrint);
		} else {
			wfDebugLog('aws', "uploadByUrl, could not upload $filePath because file_exists=" . (int)file_exists($filePath) . " or filesize=" . filesize($filePath));
		}

		if ($tempFile) {
			unlink($tempFile);
		}
	}

	/**
	 * Uploads a file from a local filesystem path to S3.
	 *
	 * Created using documentation here:
	 * http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-s3.html
	 *
	 * @param string $filePath full path to local filesystem file
	 * @param string $bucket bucket on S3 of destination file
	 * @param string $s3obj path/key on S3 of destination
	 * @param string $mimeType mime type of file to be returned with requests to S3
	 */
	public static function uploadFile($filePath, $s3obj, $mimeType, $bucket = '', $bPrint = false) {
		global $wgIsDevServer;

		// Don't allow destructive actions to our production S3 buckets on dev etc
		if ($wgIsDevServer) {
			wfDebug("Not uploading $filePath to S3 (because we are not in production)\n");
			return true;
		}

		if (!$bucket) $bucket = WH_AWS_IMAGE_BUCKET;
		$s3obj = self::translateS3path($s3obj);

		$svc = self::getS3Service();

		$result = $svc->putObject(array(
			'Bucket' => $bucket,
			'Key'    => $s3obj,
			'SourceFile' => $filePath,
			'ContentType' => $mimeType,
			'ACL'    => 'public-read'
		));

		$svc->waitUntil('ObjectExists', array(
			'Bucket' => $bucket,
			'Key'    => $s3obj
		));

		if ($result['ObjectURL']) {
			if ($bPrint) {
				print "Uploaded $filePath to s3://$bucket$s3obj, url {$result['ObjectURL']}\n";
			}
			return true;
		} else {
			if ($bPrint) {
				print "Error: uploading $filePath to s3://$bucket$s3obj\n";
			}
			return false;
		}
	}

	public static function getFile($s3obj, $localFile, $bucket = '') {
		if (!$bucket) $bucket = WH_AWS_IMAGE_BUCKET;
		$svc = self::getS3Service();
		$s3obj = self::translateS3path($s3obj);
		try {
			$result = $svc->getObject(array(
				'Bucket' => $bucket,
				'Key' => $s3obj,
				'SaveAs' => $localFile
			));
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public static function fileExists($s3obj, $bucket = '') {
		if (!$bucket) $bucket = WH_AWS_IMAGE_BUCKET;
		$svc = self::getS3Service();
		$s3obj = self::translateS3path($s3obj);
		return $svc->doesObjectExist($bucket, $s3obj);
	}

	/**
	 * Deletes a file on S3.
	 *
	 * From:
	 * http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.S3.S3Client.html#_deleteObject
	 *
	 * Created using documentation here:
	 * http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-s3.html
	 *
	 * @param string $bucket bucket on S3 of destination file
	 * @param string $s3obj path/key on S3 of destination
	 */
	public static function deleteFile($s3obj, $bucket = '', $bPrint = false) {
		global $wgIsDevServer;

		// Don't allow destructive actions to our production S3 buckets on dev etc
		if ($wgIsDevServer) {
			wfDebug("Not deleting $s3obj on S3 (because we are not in production)\n");
			return true;
		}

		if (!$bucket) $bucket = WH_AWS_IMAGE_BUCKET;
		$svc = self::getS3Service();

		$s3obj = self::translateS3path($s3obj);
		$result = $svc->deleteObject(array(
			'Bucket' => $bucket,
			'Key'    => $s3obj
		));

		if ($result->DeleteMarker) {
			if ($bPrint) {
				print "Deleted S3 file: s3://$bucket$s3obj\n";
			}
			return true;
		} else {
			if ($bPrint) {
				print "Error deleting S3 file: s3://$bucket$s3obj\n";
			}
			return false;
		}
	}

}

