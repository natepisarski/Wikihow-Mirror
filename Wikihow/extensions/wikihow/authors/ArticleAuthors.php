<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Article Authors',
	'author' => 'Jordan Small',
	'description' => 'An extension that provides the list of authors for a given article',
);

$wgAutoloadClasses['ArticleAuthors'] = __DIR__ . '/ArticleAuthors.class.php';
$wgExtensionMessagesFiles['ArticleAuthors'] = __DIR__ . '/ArticleAuthors.i18n.php';

