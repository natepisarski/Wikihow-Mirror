<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ManageRelated',
	'author' => 'Travis Derouin',
	'description' => 'Provides a way of searching, previewing and adding related wikiHows to an existing article',
	'url' => 'http://www.wikihow.com/WikiHow:ManageRelated-Extension',
);

$wgSpecialPages['ManageRelated'] = 'ManageRelated';
$wgSpecialPages['RelatedArticle'] = 'ManageRelated';
$wgAutoloadClasses['ManageRelated'] = __DIR__ . '/ManageRelated.body.php';

$wgSpecialPages['PreviewPage'] = 'PreviewPage';
$wgAutoloadClasses['PreviewPage'] = __DIR__ . '/ManageRelated.body.php';

$wgExtensionMessagesFiles['RelatedArticleAlias'] = __DIR__ . '/RelatedArticle.alias.php';

$wgResourceModules['ext.wikihow.ManageRelated'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'styles' => [ 'managerelated.css' ],
	'scripts' => [ 'managerelated.js', '../common/jquery.scrollTo/jquery.scrollTo.js' ],
	'dependencies' => [ 'ext.wikihow.desktop_base' ],
	'remoteExtPath' => 'wikihow/ManageRelated',
	'position' => 'bottom'
];
