<?php
 
// The basis for this code was taken from:
// https://www.mediawiki.org/wiki/API:Extensions

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'App Articles API',
	'description' => 'An API extension to parse and list articles for the wikiHow apps',
	'descriptionmsg' => 'sampleapiextension-desc',
	'version' => 1,
	'author' => 'Reuben Smith',
	'url' => 'https://www.mediawiki.org/wiki/API:Extensions',
);
 
$wgAutoloadClasses['ApiApp'] = dirname( __FILE__ ) . '/ApiApp.body.php';

$wgAPIModules['app'] = 'ApiApp';
 
