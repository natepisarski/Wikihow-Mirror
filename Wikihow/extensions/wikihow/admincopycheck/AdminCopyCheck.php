<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCopyCheck',
	'author' => 'Scott Cushman',
	'description' => 'Tool for checking for plagiarism',
);

$wgSpecialPages['AdminCopyCheck'] = 'AdminCopyCheck';
$wgAutoloadClasses['AdminCopyCheck'] = __DIR__ . '/AdminCopyCheck.body.php';

$wgResourceModules['ext.wikihow.admincopycheck'] = [
	'scripts' => [ 'admincopycheck.js' ],
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery', 'ext.wikihow.common_bottom' ],
	'remoteExtPath' => 'wikihow/admincopycheck',
	'position' => 'top'
];
