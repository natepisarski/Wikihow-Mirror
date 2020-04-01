<?php

// The basis for this code was taken from:
// https://www.mediawiki.org/wiki/API:Extensions

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'App Articles API',
	'description' => 'An API extension to parse and list articles for the wikiHow apps',
	'version' => 1,
	'author' => 'Reuben',
);

$wgAutoloadClasses['ApiApp'] = __DIR__ . '/ApiApp.body.php';

$wgAPIModules['app'] = 'ApiApp';
