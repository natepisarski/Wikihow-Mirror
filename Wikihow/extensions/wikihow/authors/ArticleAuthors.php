<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Article Authors',
	'author' => 'Jordan Small',
	'description' => 'An extension that provides the list of authors for a given article',
);

$wgAutoloadClasses['ArticleAuthors'] = dirname(__FILE__) . '/ArticleAuthors.class.php';
$wgExtensionMessagesFiles['ArticleAuthors'] = dirname(__FILE__) . '/ArticleAuthors.i18n.php';

