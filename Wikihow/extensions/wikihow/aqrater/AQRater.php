<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array (
    'name' => 'Article Quality Rater',
    'author' => 'RJS Bhatia',
    'description'=> 'Script for rating articles using classifier',
    );

$wgSpecialPages['AQRater'] = 'AQRater';
$wgAutoloadClasses['AQRater'] = __DIR__ . '/AQRater.body.php';

$wgResourceModules['ext.aqrater'] = [
	'scripts' => [
		'../common/download.jQuery.js',
		'../mobile/webtoolkit.aim.min.js',
		'aqrater.js',
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/AQRater',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];
