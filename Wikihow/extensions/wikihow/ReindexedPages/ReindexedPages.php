<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ReindexedPages',
	'author' => 'Alberto',
	'description' => "A list of articles that became indexable recently.",
);

$wgSpecialPages['ReindexedPages'] = 'ReindexedPages';
$wgAutoloadClasses['ReindexedPages'] = __DIR__ . '/ReindexedPages.body.php';
$wgExtensionMessagesFiles['ReindexedPages'] = __DIR__ . '/ReindexedPages.i18n.php';
$wgExtensionMessagesFiles['ReindexedPagesdAliases'] = __DIR__ . '/ReindexedPages.alias.php';

$wgResourceModules['wikihow.reindexedpages.styles'] = [
	'styles' => [
		'reindexedpages.less',
	],
	'position' => 'top',
	'localBasePath' => __DIR__ ,
	'remoteExtPath' => 'wikihow/ReindexedPages',
	'targets' => ['mobile', 'desktop'],
];