<?php

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'SMS Listing API',
	'description' => 'An API extension to handle search requests via SMS',
	'descriptionmsg' => 'sampleapiextension-desc',
	'version' => 1,
	'author' => 'Bebeth Steudel',
	'url' => 'https://www.mediawiki.org/wiki/API:Extensions',
);

$wgAutoloadClasses['ApiSmsListing'] =
	dirname(__FILE__) . '/ApiSmsListing.body.php';
$wgAutoloadClasses['CategoryLister'] =
	dirname(__FILE__) . '/ApiSmsListing.body.php';
$wgAPIModules['smslisting'] = 'ApiSmsListing';


