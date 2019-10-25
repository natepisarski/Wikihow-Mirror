<?php

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'Title Search API',
	'description' => 'Returns matching title info (id, title text, url) for a given query',
	'author' => 'Jordan Small',
);

$wgAutoloadClasses['ApiTitleSearch'] = __DIR__ . '/ApiTitleSearch.body.php';
$wgAPIModules['titlesearch'] = 'ApiTitleSearch';
