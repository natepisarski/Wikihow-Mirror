<?php

if (!defined('MEDIAWIKI')) die();

/*
 * wikiHow stores its images on S3. This class contains a bunch of the glue for
 * those operations.
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'S3Images',
	'author' => 'Reuben',
	'description' => 'Glue to allow wikiHow store its uploaded images on S3',
);

$wgAutoloadClasses['S3Images'] = __DIR__ . '/S3Images.body.php';
$wgAutoloadClasses['AwsFiles'] = __DIR__ . '/AwsFiles.body.php';
$wgAutoloadClasses['UploadS3FileJob'] = __DIR__ . '/S3Job.body.php';
$wgAutoloadClasses['DeleteS3FileJob'] = __DIR__ . '/S3Job.body.php';

$wgJobClasses['UploadS3FileJob'] = 'UploadS3FileJob';
$wgJobClasses['DeleteS3FileJob'] = 'DeleteS3FileJob';

#$wgHooks['FileTransformed'][] = array('S3Images::onFileTransformed');
#$wgHooks['LocalFilePurgeThumbnails'][] = array('S3Images::onLocalFilePurgeThumbnails');
#$wgHooks['NewRevisionFromEditComplete'][] = array('S3Images::onNewRevisionFromEditComplete');
$wgHooks['FileUpload'][] = array('S3Images::onFileUpload');
