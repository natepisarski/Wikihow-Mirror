<?php

$wgExtensionCredits['api'][] = [
	'path' => __FILE__,
	'name' => 'Graphs query API',
	'description' => 'An API extension to defineq queries for graphs and format the results',
	'descriptionmsg' => 'sampleapiextension-desc',
	'author' => 'Andrew Hayworth',
	'url' => 'https://www.mediawiki.org/wiki/API:Extensions',
];

$wgAutoloadClasses['ApiGraphs'] = dirname(__FILE__) . '/ApiGraphs.body.php';
$wgAPIModules['graphs'] = 'ApiGraphs';
