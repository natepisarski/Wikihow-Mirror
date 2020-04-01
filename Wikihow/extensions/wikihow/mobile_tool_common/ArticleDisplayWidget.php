<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ArticleDisplayWidget',
	'author' => 'Jordan Small',
	'description' => 'A mobile widget that displays html',
);

$wgSpecialPages['ArticleDisplayWidget'] = 'ArticleDisplayWidget';
$wgAutoloadClasses['ArticleDisplayWidget'] = __DIR__ . '/ArticleDisplayWidget.body.php';
$wgExtensionMessagesFiles['ArticleDisplayWidget'] = __DIR__ . '/ArticleDisplayWidget.i18n.php';

$wgResourceModules['ext.wikihow.ArticleDisplayWidget'] = array(
	'styles' => array('article_display_widget.less'),
	'scripts' => 'article_display_widget.js',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/mobile_tool_common',
	'messages' => array(
		'adw_show',
		'adw_hide',
	),
	'position' => 'bottom',
	'targets' => array( 'mobile' ),
	'dependencies' => array('mobile.wikihow')
);
