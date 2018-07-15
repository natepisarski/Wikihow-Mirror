<?php

// In our development environment, we don't want to keep a full
// copy of all wikihow files, so we download them on demand
class DevImageHooks {
	
	public static function onFileThumbName($image) {
		global $wgLanguageCode;
		if ($image->exists()) {
			# Translate to our S3 bucket names. Disclaimer: there is a better way, but needed
			# this to work on dev. I can't figure out the right non-abstract functions to use
			# in Mediawiki's file system backend classes.
			$storagePath = $image->getPath();
			$s3obj = preg_replace('@^mwstore://local-backend/local-public@', '/images_' . $wgLanguageCode, $storagePath);
			$localFile = preg_replace('@^mwstore://local-backend/local-public@', '/opt/images/images_' . $wgLanguageCode, $storagePath);
			// Sometimes prefix is mwstore://local-backend/local-temp
			if (!preg_match('@^mwstore://@', $localFile) && !file_exists($localFile)) {
				$result = AwsFiles::getFile($s3obj, $localFile);
				if (!$result) {
					echo "(dev message) Key not found on S3: " . $s3obj . "\n";
				}
			}
		}
	}

}

