<?php

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'SMS Listing API',
	'description' => 'An API extension to handle search requests via SMS',
	'author' => 'Bebeth Steudel',
);

$wgAutoloadClasses['ApiSmsListing'] =
	__DIR__ . '/ApiSmsListing.body.php';
$wgAutoloadClasses['CategoryLister'] =
	__DIR__ . '/ApiSmsListing.body.php';
$wgAPIModules['smslisting'] = 'ApiSmsListing';
