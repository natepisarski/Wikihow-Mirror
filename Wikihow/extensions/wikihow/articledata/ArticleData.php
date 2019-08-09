<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Titles by Category',
	'author' => 'Jordan Small',
	'description' => 'Returns data on titles that fall under a category (as well as subcategories) in the wikiHow category tree',
);

$wgSpecialPages['ArticleData'] = 'ArticleData';
$wgAutoloadClasses['ArticleData'] = __DIR__ . '/ArticleData.body.php';
$wgExtensionMessagesFiles['ArticleData'] = __DIR__ . '/ArticleData.i18n.php';

$wgResourceModules['ext.wikihow.articledata'] = array(
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'styles' => [ 'articledata.css' ],
	'scripts' => [ 'articledata.js' ],
	'dependencies' => [ 'ext.wikihow.common_bottom', 'jquery', 'wikihow.common.jquery.download' ],
	'remoteExtPath' => 'wikihow/articledata',
	'messages' => [],
	'position' => 'bottom'
);
