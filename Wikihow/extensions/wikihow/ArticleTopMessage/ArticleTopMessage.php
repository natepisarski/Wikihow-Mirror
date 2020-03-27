<?php

$wgAutoloadClasses['ArticleTopMessage'] = __DIR__ . '/ArticleTopMessage.class.php';
$wgMessagesDirs['ArticleTopMessage'] = __DIR__ . '/i18n/';

$wgHooks['BeforePageDisplay'][] = ['ArticleTopMessage::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.article_top_message.scripts'] = [
	'scripts' => [ 'article_top_message.js' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/ArticleTopMessage/resources',
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [ 'mediawiki.cookie' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.article_top_message.styles'] = [
	'styles' => [ 'article_top_message.less' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/ArticleTopMessage/resources',
	'targets' => [ 'desktop', 'mobile' ]
];
