<?php

$wgAutoloadClasses['AboutWikihow'] = __DIR__ . '/AboutWikihow.class.php';
$wgMessagesDirs['AboutWikihow'] = __DIR__ . '/i18n/';

$wgHooks['WikihowTemplateShowTopLinksSidebar'][] = ['AboutWikihow::onWikihowTemplateShowTopLinksSidebar'];
$wgHooks['BeforePageDisplay'][] = ['AboutWikihow::onBeforePageDisplay'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['AboutWikihow::onMobileProcessArticleHTMLAfter'];

$wgResourceModules['ext.wikihow.press_boxes'] = [
	'styles' => [ 'press_boxes.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/AboutWikihow/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.mobile_about_wikihow'] = [
	'styles' => [ 'mobile_about_wikihow.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/AboutWikihow/assets',
	'targets' => [ 'mobile' ],
	'position' => 'top',
	'dependencies' => [
		'mobile.wikihow.socialproof',
		'ext.wikihow.press_boxes'
	]
];

