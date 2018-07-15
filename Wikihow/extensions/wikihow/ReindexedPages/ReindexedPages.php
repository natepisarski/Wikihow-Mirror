<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ReindexedPages',
	'author' => 'Alberto Burgos',
	'description' => "A list of articles that became indexable recently.",
);

$wgSpecialPages['ReindexedPages'] = 'ReindexedPages';
$wgAutoloadClasses['ReindexedPages'] = dirname(__FILE__) . '/ReindexedPages.body.php';
$wgExtensionMessagesFiles['ReindexedPages'] = dirname(__FILE__) . '/ReindexedPages.i18n.php';
$wgExtensionMessagesFiles['ReindexedPagesdAliases'] = dirname(__FILE__) . '/ReindexedPages.alias.php';
