<?php

$wgExtensionCredits['api'][] = [
	'path' => __FILE__,
	'name' => 'Graphs query API',
	'description' => 'An API extension to defineq queries for graphs and format the results',
	'author' => 'wikiHow',
];

$wgAutoloadClasses['ApiGraphs'] = __DIR__ . '/ApiGraphs.body.php';
$wgAPIModules['graphs'] = 'ApiGraphs';
