<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'WikiText Downloader',
	'author' => 'Jordan Small',
	'description' => 'Download the wikitext of an article given an article id',
);

$wgSpecialPages['WikitextDownloader'] = 'WikitextDownloader';
$wgAutoloadClasses['WikitextDownloader'] = __DIR__ . '/WikitextDownloader.body.php';
$wgExtensionMessagesFiles['WikitextDownloader'] = __DIR__ . '/WikitextDownloader.i18n.php';
