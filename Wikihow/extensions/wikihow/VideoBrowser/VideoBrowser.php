<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'VideoBrowser',
	'author' => 'Trevor Parscal <trevorparscal@gmail.com>',
	'description' => 'Video browser and viewer'
];

$wgSpecialPages['VideoBrowser'] = 'SpecialVideoBrowser';
$wgAutoloadClasses['SpecialVideoBrowser'] = __DIR__ . '/SpecialVideoBrowser.php';
$wgAutoloadClasses['VideoBrowser'] = __DIR__ . '/VideoBrowser.body.php';
$wgHooks['IsEligibleForMobileSpecial'][] = 'VideoBrowser::onIsEligibleForMobileSpecial';
$wgHooks['WebRequestPathInfoRouter'][] = 'VideoBrowser::onWebRequestPathInfoRouter';
$wgHooks['WikihowHomepageFAContainerHtml'][] = 'VideoBrowser::onWikihowHomepageFAContainerHtml';

$wgExtensionMessagesFiles['VideoBrowser'] = __DIR__ . '/VideoBrowser.i18n.php';
$wgExtensionMessagesFiles['VideoBrowserAliases'] = __DIR__ . '/VideoBrowser.alias.php';

$wgResourceModules['ext.wikihow.videoBrowser'] = [
	'styles' => [
		'resources/styles/main.less',
		'resources/styles/ItemComponent.less',
		'resources/styles/ListComponent.less',
		'resources/styles/TitleComponent.less',
		'resources/styles/ViewerComponent.less',
	],
	'scripts' => [
		'resources/scripts/main.js',
		'resources/scripts/Catalog.js',
		'resources/scripts/BrowserComponent.js',
		'resources/scripts/IndexComponent.js',
		'resources/scripts/ItemComponent.js',
		'resources/scripts/ListComponent.js',
		'resources/scripts/TitleComponent.js',
		'resources/scripts/ViewerComponent.js',
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'messages' => [
		'videobrowser',
		'videobrowser-index-title',
		'videobrowser-viewer-title',
		'videobrowser-back',
		'videobrowser-next',
		'videobrowser-replay',
		'videobrowser-auto-play',
		'videobrowser-cancel',
		'videobrowser-countdown',
		'videobrowser-not-found',
		'videobrowser-how-to',
		'videobrowser-show-more',
		'videobrowser-read-more'
	],
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [
		'wikihow.router',
		'wikihow.render',
		'wikihow.common.taffy',
		'ext.wikihow.common_top',
		'ext.wikihow.common_bottom'
	]
];

$wgResourceModules['ext.wikihow.videoBrowser-desktop-section'] = [
	'styles' => [
		'resources/styles/desktop-section.less',
		'resources/styles/ItemComponent.less',
		'resources/styles/ListComponent.less'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];

$wgResourceModules['ext.wikihow.videoBrowser-desktop-widget'] = [
	'styles' => [
		'resources/styles/desktop-widget.less',
		'resources/styles/ItemComponent.less'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];

$wgResourceModules['ext.wikihow.videoBrowser-mobile-widget'] = [
	'styles' => [
		'resources/styles/mobile-widget.less',
		'resources/styles/ItemComponent.less'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'targets' => [ 'mobile' ]
];
