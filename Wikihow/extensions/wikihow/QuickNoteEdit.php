<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'QuickNoteEdit',
	'author' => 'Vu (wikiHow)',
	'description' => 'quick popups for notes and edit',
);

$wgExtensionMessagesFiles['QuickNoteEdit'] = __DIR__ . '/QuickNoteEdit.i18n.php';
$wgSpecialPages['QuickNoteEdit'] = 'QuickNoteEdit';
$wgAutoloadClasses['QuickNoteEdit'] = __DIR__ . '/QuickNoteEdit.body.php';

$wgResourceModules['ext.wikihow.QuickNoteEdit'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'scripts' => [
		'quicknote.js',
		'PostComment/postcomment.js',
		'thumbsup/thumbsup.js',
	],
	'dependencies' => [ 'ext.wikihow.desktop_base' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top',
];
