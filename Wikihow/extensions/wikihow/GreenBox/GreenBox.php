<?php

$wgSpecialPages['GreenBoxEditTool'] = 'GreenBoxEditTool';

$wgAutoloadClasses['GreenBox'] = __DIR__ . '/GreenBox.class.php';
$wgAutoloadClasses['GreenBoxEditTool'] = __DIR__ . '/GreenBoxEditTool.body.php';

$wgExtensionMessagesFiles['GreenBox'] = __DIR__ . '/GreenBox.i18n.magic.php';
$wgMessagesDirs['GreenBox'] = __DIR__ . '/i18n';

$wgHooks['ParserFirstCallInit'][] 					= ['GreenBox::onParserFirstCallInit'];
$wgHooks['ProcessArticleHTMLAfter'][] 			= ['GreenBox::onProcessArticleHTMLAfter'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['GreenBox::onProcessArticleHTMLAfter'];
$wgHooks['BeforePageDisplay'][] 						= ['GreenBox::onBeforePageDisplay'];
$wgHooks['PageContentSave'][] 							= ['GreenBox::onPageContentSave'];

$wgResourceModules['ext.wikihow.green_box'] = [
	'styles' => [ 'green_box.css' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/GreenBox/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.green_box.scripts'] = [
	'scripts' => [ 'green_box.js' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/GreenBox/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.green_box_cta'] = [
	'styles' => [ 'green_box_cta.css' ],
	'scripts' => [ 'green_box_cta.js' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/GreenBox/assets',
	'targets' => [ 'desktop' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.green_box_edit'] = [
	'styles' => [ 'green_box_edit.less' ],
	'scripts' => [ 'green_box_edit.js' ],
	'messages' => [
		'green_box_error_no_expert',
		'green_box_error_no_answer',
		'green_box_error_too_long'
	],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/GreenBox/assets',
	'targets' => [ 'desktop' ],
	'position' => 'bottom'
];
