<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['other'][] = array(
	'name' => 'SearchBox',
	'author' => 'Trevor <trevor@wikihow.com>',
	'description' => 'Search box widget',
);

$wgExtensionMessagesFiles['SearchBox'] = __DIR__ . '/SearchBox.i18n.php';
$wgAutoloadClasses['SearchBox'] = __DIR__ . '/SearchBox.body.php';
