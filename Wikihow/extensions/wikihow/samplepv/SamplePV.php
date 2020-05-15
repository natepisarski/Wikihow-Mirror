<?php

if ( ! defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Sample Page Views',
	'author' => 'Gershon Bialer (wikiHow)',
	'description' => 'List the number of page views for some set of samples'
);

$wgSpecialPages['SamplePV'] = 'SamplePV';
$wgAutoloadClasses['SamplePV'] = __DIR__ . '/SamplePV.body.php';
