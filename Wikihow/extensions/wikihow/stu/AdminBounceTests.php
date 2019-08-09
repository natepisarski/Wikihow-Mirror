<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Stu',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to get bounce stats, given a list of wikiHow URLs',
);

$wgSpecialPages['AdminBounceTests'] = 'AdminBounceTests';
$wgSpecialPages['Stu'] = 'AdminBounceTests'; // special page alias
$wgAutoloadClasses['AdminBounceTests'] = __DIR__ . '/AdminBounceTests.body.php';

$wgResourceModules['ext.wikihow.adminstu_styles'] = [
	'styles' => [ 'adminstu.css' ],
	'targets' => [ 'desktop' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/stu',
];

$wgResourceModules['ext.wikihow.adminstu'] = [
	'scripts' => [ '../common/download.jQuery.js', 'adminstu.js' ],
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery', 'ext.wikihow.common_bottom' ],
	'remoteExtPath' => 'wikihow/stu',
	'position' => 'top'
];
