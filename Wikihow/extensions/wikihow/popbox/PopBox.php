<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'PopBox',
	'author' => 'wikiHow',
	'description' => 'Provides a basic way of adding new entries to the Spam Blacklist from diff pages',
);

$wgExtensionMessagesFiles['PopBox'] = __DIR__ . '/PopBox.i18n.php';

$wgAutoloadClasses['PopBox'] = __DIR__ . '/PopBox.body.php';

$wgResourceModules['ext.wikihow.popbox'] = [
    'styles' => ['popbox.css'],
    'scripts' => ['PopBox.js'],
    'targets' => array( 'desktop' ),
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/popbox',
	'messages' => [
		'popbox_noelement', 'popbox_noresults', 'popbox_related_articles',
		'popbox_revise', 'popbox_nothanks', 'popbox_editdetails',
		'popbox_search', 'popbox_no_text_selected',
	],
];
