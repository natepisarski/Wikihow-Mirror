<?php

class S3Images {

	/**
	 * Hook called when a new file is uploaded.
	 */
	public static function onFileUpload( $localFile, $reupload, $titleExists ) {
		global $wgLanguageCode;
		$jobTitle = $localFile->getTitle();
		$buckets = array(WH_AWS_IMAGE_BUCKET, WH_AWS_IMAGE_BACKUPS_BUCKET);
		$localIP = null;
		if ( array_key_exists( 'SERVER_ADDR', $_SERVER ) ) {
			$localIP = $_SERVER['SERVER_ADDR'];
		}

		if (!$localIP) {
			$localIP = `/sbin/ifconfig |grep 'addr:10\.' |awk '{print $2}' |cut -d : -f 2`;
			$localIP = trim($localIP);
			if (!preg_match('@^10\.([0-9]{1,3}\.){2}[0-9]{1,3}$@', $localIP)) {
				$localIP = '127.0.0.1';
			}
		}
		foreach ($buckets as $bucket) {
			$localUrl = 'http://' . $localIP  . '/images/' . $localFile->getRel();
			$jobParams = array(
				'fetchUrl' => $localUrl,
				'fetchHost' => Misc::getLangDomain($wgLanguageCode),
				'file' => $localFile->getLocalRefPath(),
				'bucket' => $bucket,
				'uploadPath' => "/images_$wgLanguageCode/" . $localFile->getRel(),
				'mimeType' => $localFile->getMimeType(),
			);
			$job = Job::factory('UploadS3FileJob', $jobTitle, $jobParams);
			JobQueueGroup::singleton()->push($job);
		}

		return true;
	}

}

