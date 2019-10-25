<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCustomMeta',
	'author' => 'Reuben',
	'description' => 'Tool for support personnel to upload/download lists of custom titles',
);

$wgSpecialPages['AdminCustomMeta'] = 'AdminCustomMeta';
$wgAutoloadClasses['AdminCustomMeta'] = __DIR__ . '/AdminCustomMeta.body.php';
$wgExtensionMessagesFiles['AdminCustomMetaAlias'] = __DIR__ . '/AdminCustomMeta.alias.php';

$wgResourceModules['ext.wikihow.admincustommeta'] = [
	'scripts' => [ 'admincustommeta.js' ],
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery', 'ext.wikihow.common_bottom' ],
	'remoteExtPath' => 'wikihow/custom_meta',
	'position' => 'top'
];
