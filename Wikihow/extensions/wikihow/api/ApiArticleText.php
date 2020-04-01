<?php

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'Article Text API',
	'description' => 'An API extension to return article text and media asset info for main namespace articles',
	'version' => 1,
	'author' => 'Jordan Small',
);

$wgAutoloadClasses['ApiArticleText'] = __DIR__ . '/ApiArticleText.body.php';
$wgAPIModules['articletext'] = 'ApiArticleText';
