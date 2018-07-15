<?php

$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'Mobile Article Downloader API',
	'description' => 'An API extension to download and save mobile articles',
	'descriptionmsg' => 'sampleapiextension-desc',
	'version' => 1,
	'author' => 'George Bahij',
	'url' => 'https://www.mediawiki.org/wiki/API:Extensions',
);

$wgAutoloadClasses['ApiMobileArticleDownloader'] = dirname(__FILE__) . '/ApiMobileArticleDownloader.body.php';

$wgAPIModules['mobiledownload'] = 'ApiMobileArticleDownloader';
