<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['other'][] = array(
	'name' => 'SearchBox',
	'author' => 'Trevor <trevor@wikihow.com>',
	'description' => 'Search box widget',
);

$wgExtensionMessagesFiles['SearchBox'] = dirname( __FILE__ ) . '/SearchBox.i18n.php';
$wgAutoloadClasses['SearchBox'] = dirname( __FILE__ ) . '/SearchBox.body.php';
