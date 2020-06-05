<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Wikihow Content Ads',
	'author' => 'Bebeth Steudel',
	'description' => 'Ads to test possible new content'
);

$wgSpecialPages['WikihowContentAds'] = 'WikihowContentAds';

$wgAutoloadClasses['WikihowContentAds']            = __DIR__ . '/WikihowContentAds.body.php';

$wgMessagesDirs['WikihowContentAds'] = __DIR__ . '/i18n/';

$wgResourceModules['ext.wikihow.wikihowcontentads'] = [
	'scripts' => ['wikihowcontentads.js'],
	'styles' => ['wikihowcontentads.less'],
	'localBasePath' => __DIR__ . "/resources" ,
	'remoteExtPath' => 'wikihow/WikihowContentAds/resources',
	'targets' => ['desktop', 'mobile']
];

$wgResourceModules['ext.wikihow.wikihowcategorycontentads'] = [
	'scripts' => ['wikihowcategorycontentads.js'],
	'styles' => ['wikihowcategorycontentads.less'],
	'localBasePath' => __DIR__ . "/resources" ,
	'remoteExtPath' => 'wikihow/WikihowContentAds/resources',
	'targets' => ['desktop', 'mobile']
];