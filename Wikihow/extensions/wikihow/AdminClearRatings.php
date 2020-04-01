<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminClearRatings',
	'author' => 'George Bahij',
	'description' => 'Tool for clearing the page ratings of many articles at once'
);

$wgSpecialPages['AdminClearRatings'] = 'AdminClearRatings';
$wgAutoloadClasses['AdminClearRatings'] = __DIR__ . '/AdminClearRatings.body.php';

$wgResourceModules['ext.wikihow.admin_clear_ratings'] = [
	'scripts' => [ 'admin_clear_ratings.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/',
	'targets' => [ 'desktop' ],
	'position' => 'bottom'
];