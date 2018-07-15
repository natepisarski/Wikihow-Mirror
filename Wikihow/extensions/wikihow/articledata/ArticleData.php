<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Titles by Category',
	'author' => 'Jordan Small',
	'description' => 'Returns data on titles that fall under a category (as well as subcategories) in the wikiHow category tree',
);

$wgSpecialPages['ArticleData'] = 'ArticleData';
$wgAutoloadClasses['ArticleData'] = dirname(__FILE__) . '/ArticleData.body.php';
$wgExtensionMessagesFiles['ArticleData'] = dirname(__FILE__) . '/ArticleData.i18n.php';
