<?php

if ( !defined('MEDIAWIKI') ) die();

$wgSpecialPages['BuildWikihowArticle'] = 'BuildWikihowArticle';

$wgAutoloadClasses['WikihowArticleHTML'] = __DIR__ . '/WikihowArticle.class.php';
$wgAutoloadClasses['WikihowArticleEditor'] = __DIR__ . '/WikihowArticleEditor.class.php';
$wgAutoloadClasses['BuildWikihowArticle'] = __DIR__ . '/WikihowArticleEditor.class.php';

$wgExtensionMessagesFiles['WikihowArticleMagic'] = __DIR__ . '/WikihowArticle.i18n.magic.php';
$wgHooks['GetDoubleUnderscoreIDs'][] = array("wfAddMagicWords");

// adding custom magic words for the parser to utilize
function wfAddMagicWords(&$magic_array) {
	$magic_array[] = 'forceadv';
	$magic_array[] = 'parts';
	$magic_array[] = 'methods';
	$magic_array[] = 'ways';
	$magic_array[] = 'summarized';
	return true;
}
