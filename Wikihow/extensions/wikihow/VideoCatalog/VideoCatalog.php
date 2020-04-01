<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'VideoCatalog',
	'author' => 'Trevor Parscal <trevorparscal@gmail.com>',
	'description' => 'Video catalog'
];

$wgAutoloadClasses['VideoCatalog'] = __DIR__ . '/includes/VideoCatalog.php';
$wgAutoloadClasses['VideoCatalogObject'] = __DIR__ . '/includes/VideoCatalogObject.php';
$wgAutoloadClasses['VideoCatalogItem'] = __DIR__ . '/includes/VideoCatalogItem.php';
$wgAutoloadClasses['VideoCatalogLink'] = __DIR__ . '/includes/VideoCatalogLink.php';
$wgAutoloadClasses['VideoCatalogSource'] = __DIR__ . '/includes/VideoCatalogSource.php';

$wgHooks['WikiVisualS3VideosAdded'][] = 'VideoCatalog::onWikiVisualS3VideosAdded';

$wgResourceModules['ext.wikihow.videoCatalog'] = [
	'scripts' => [
		'resources/Catalog.js'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoCatalog',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [
		'wikihow.common.taffy',
		'ext.wikihow.common_top',
		'ext.wikihow.common_bottom'
	]
];
