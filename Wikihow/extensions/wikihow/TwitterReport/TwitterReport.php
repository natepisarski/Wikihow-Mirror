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
$wgAutoloadClasses['TwitterReport\TwitterReport'] = dirname(__FILE__) . '/TwitterReport.body.php';
$wgAutoloadClasses['TwitterReport\TwitterClient'] = dirname(__FILE__) . '/TwitterClient.php';

$wgResourceModules['ext.wikihow.twitter_report'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/TwitterReport/resources',
	'localBasePath' => dirname(__FILE__) . '/resources',
	'styles' => ['twitter_report.less'],
	'scripts' => [
		'../../common/download.jQuery.js',
		'twitter_report.js'
	],
];
