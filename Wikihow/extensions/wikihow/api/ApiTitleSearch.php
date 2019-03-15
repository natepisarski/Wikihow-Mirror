<?php

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'Title Search API',
	'description' => 'Returns matching title info (id, title text, url) for a given query',
	'descriptionmsg' => 'sampleapiextension-desc',
	'version' => 1,
	'author' => 'Jordan Small',
	'url' => 'https://www.mediawiki.org/wiki/API:Extensions',
);

$wgAutoloadClasses['ApiTitleSearch'] = __DIR__ . '/ApiTitleSearch.body.php';
$wgAPIModules['titlesearch'] = 'ApiTitleSearch';


