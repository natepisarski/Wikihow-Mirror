<?php

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'Title Feed API',
	'description' => 'Returns matching title info (id, title text, url) for given title feeds (featured and coauthor)',
	'author' => 'Jordan Small',
);

$wgAutoloadClasses['ApiTitleFeed'] = __DIR__ . '/ApiTitleFeed.body.php';
$wgAPIModules['titlefeed'] = 'ApiTitleFeed';
