<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'TwitterReport',
	'author' => 'Alberto Burgos',
	'description' => "A tool to help staff members find relevant Tweets",
);

$wgSpecialPages['TwitterReport'] = 'TwitterReport\TwitterReport';
$wgAutoloadClasses['TwitterReport\TwitterReport'] = __DIR__ . '/TwitterReport.body.php';
$wgAutoloadClasses['TwitterReport\TwitterClient'] = __DIR__ . '/TwitterClient.php';

$wgResourceModules['ext.wikihow.twitter_report'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/TwitterReport/resources',
	'localBasePath' => __DIR__ . '/resources',
	'styles' => ['twitter_report.less'],
	'scripts' => [
		'../../common/download.jQuery.js',
		'twitter_report.js'
	],
];
