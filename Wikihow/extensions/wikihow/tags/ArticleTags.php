<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['ConfigStorage'] = __DIR__ . '/ConfigStorage.php';
$wgAutoloadClasses['ConfigStorageHistory'] = __DIR__ . '/ConfigStorageHistory.php';
$wgAutoloadClasses['AdminTags'] = __DIR__ . '/SpecialAdminTags.php';
$wgAutoloadClasses['ArticleTag'] = __DIR__ . '/ArticleTag.php';
$wgAutoloadClasses['ArticleTagList'] = __DIR__ . '/ArticleTagList.php';
$wgExtensionMessagesFiles['ArticleTagAlias'] = __DIR__ . '/ArticleTags.alias.php';

$wgHooks['ConfigStorageStoreConfig'] = ['ArticleTag::onConfigStorageStoreConfig'];
$wgSpecialPages['AdminTags'] = 'AdminTags';
$wgSpecialPages['AdminConfigEditor'] = 'AdminTags'; // alias from old special page name

$wgResourceModules['ext.wikihow.AdminTags'] = [
	'styles' => [
		'admintags.css',
	],
	'scripts' => [
		'admintags.js',
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/tags',
	'position' => 'top',
	'messages' => [],
	'targets' => [ 'desktop' ],
	'dependencies' => [
		'ext.wikihow.common_top',
		'ext.wikihow.common_bottom',
	]
];
