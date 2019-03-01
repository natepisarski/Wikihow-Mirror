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
		'resources/styles/ActionBarComponent.less',
		'resources/styles/ArticleComponent.less',
		'resources/styles/IndexComponent.less',
		'resources/styles/VideoComponent.less',
		'resources/styles/VideoListComponent.less',
		'resources/styles/TitleComponent.less',
		'resources/styles/SliderComponent.less',
		'resources/styles/ViewerComponent.less',
	],
	'scripts' => [
		'resources/scripts/main.js',
		'resources/scripts/Catalog.js',
		'resources/scripts/ActionBarComponent.js',
		'resources/scripts/ArticleComponent.js',
		'resources/scripts/BrowserComponent.js',
		'resources/scripts/IndexComponent.js',
		'resources/scripts/VideoComponent.js',
		'resources/scripts/VideoListComponent.js',
		'resources/scripts/TitleComponent.js',
		'resources/scripts/SliderComponent.js',
		'resources/scripts/ViewerComponent.js',
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'messages' => [
		'videobrowser',
		'videobrowser-auto-play',
		'videobrowser-back',
		'videobrowser-cancel',
		'videobrowser-countdown',
		'videobrowser-how-to',
		'videobrowser-index-title',
		'videobrowser-categoryindex-title',
		'videobrowser-loading',
		'videobrowser-next',
		'videobrowser-not-found',
		'videobrowser-plays',
		'videobrowser-read-more',
		'videobrowser-replay',
		'videobrowser-show-more',
		'videobrowser-viewer-title',
		'videobrowser-context',
		'videobrowser-meta-title'
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
		'resources/styles/VideoComponent.less',
		'resources/styles/VideoListComponent.less'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];

$wgResourceModules['ext.wikihow.videoBrowser-desktop-widget'] = [
	'styles' => [
		'resources/styles/desktop-widget.less',
		'resources/styles/VideoComponent.less'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];

$wgResourceModules['ext.wikihow.videoBrowser-mobile-widget'] = [
	'styles' => [
		'resources/styles/mobile-widget.less',
		'resources/styles/VideoComponent.less'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/VideoBrowser',
	'position' => 'top',
	'targets' => [ 'mobile' ]
];
