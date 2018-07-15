<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'wikiVisualLibrary',
	'author' => 'George Bahij',
	'namemsg' => 'wikivisuallibrary',
	'description' => 'Collect all the visual things',
	'descriptionmsg' => 'wikivisuallibrarydescription',
	'version' => 1
];

$wgSpecialPages['wikiVisualLibrary'] = 'WVL\WikiVisualLibrary';
$wgAutoloadClasses['WVL\WikiVisualLibrary'] = __DIR__ . '/WikiVisualLibrary.body.php';
$wgAutoloadClasses['WVL\Controller'] = __DIR__ . '/WikiVisualLibraryController.class.php';
$wgAutoloadClasses['WVL\Model'] = __DIR__ . '/WikiVisualLibraryModel.class.php';
$wgAutoloadClasses['WVL\Util'] = __DIR__ . '/WikiVisualLibraryUtil.class.php';
$wgAutoloadClasses['WVL\Indexer'] = __DIR__ . '/WikiVisualLibraryIndexer.class.php';
$wgSpecialPages['AdminWikiVisualLibrary'] = 'AdminWikiVisualLibrary';
$wgAutoloadClasses['AdminWikiVisualLibrary'] = __DIR__ . '/AdminWikiVisualLibrary.body.php';

$wgResourceModules['ext.wikihow.wikivisuallibrary.special.top'] = [
	'styles' => [
		'../common/swipebox/swipebox.css',
		'resources/wvl_special.css'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/WikiVisualLibrary',
	'position' => 'top',
	'targets' => ['desktop']
];

$wgResourceModules['ext.wikihow.wikivisuallibrary.special.bottom'] = [
	'scripts' => [
		'../common/swipebox/jquery.swipebox.js',
		'resources/wvl_special.js'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/WikiVisualLibrary',
	'position' => 'bottom',
	'dependencies' => [
		'jquery.ui.datepicker',
		'wikihow.common.mustache'
	],
	'targets' => ['desktop']
];

$wgResourceModules['ext.wikihow.adminwikivisuallibrary'] = [
	'scripts' => [
		'resources/wvl_admin.js'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/WikiVisualLibrary',
	'position' => 'top',
	'targets' => ['desktop']
];

$wgHooks['WikiVisualS3ImagesAdded'][] = ['WVL\Indexer::onWikiVisualS3ImagesAdded'];
$wgHooks['WikiVisualS3VideosAdded'][] = ['WVL\Indexer::onWikiVisualS3VideosAdded'];

