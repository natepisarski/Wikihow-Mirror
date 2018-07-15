<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'WikiText Downloader',
	'author' => 'Jordan Small',
	'description' => 'Download the wikitext of an article given an article id',
);

$wgSpecialPages['WikitextDownloader'] = 'WikitextDownloader';
$wgAutoloadClasses['WikitextDownloader'] = dirname(__FILE__) . '/WikitextDownloader.body.php';
$wgExtensionMessagesFiles['WikitextDownloader'] = dirname(__FILE__) . '/WikitextDownloader.i18n.php';
