<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminEditInfo',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to hand-edit meta descriptions and page titles of articles, given a list of wikiHow URLs',
);

$wgSpecialPages['AdminEditMetaInfo'] = 'AdminEditInfo';
$wgSpecialPages['AdminEditPageTitles'] = 'AdminEditInfo';
$wgAutoloadClasses['AdminEditInfo'] = __DIR__ . '/AdminEditInfo.body.php';

$wgResourceModules['ext.wikihow.admineditinfo'] = [
	'scripts' => [ 'admineditinfo.js' ],
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery', 'ext.wikihow.common_bottom' ],
	'remoteExtPath' => 'wikihow/custom_meta',
	'position' => 'top'
];
