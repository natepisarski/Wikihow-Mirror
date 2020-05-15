<?php

// The basis for this code was taken from:
// https://www.mediawiki.org/wiki/API:Extensions

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'Flavius API',
	'description' => 'An API extension to serve up Flavius data',
	'author' => 'Gershon Bialer (wikiHow)'
);

$wgAutoloadClasses['ApiFlavius'] = __DIR__ . '/ApiFlavius.body.php';
$wgAPIModules['flavius'] = 'ApiFlavius';
