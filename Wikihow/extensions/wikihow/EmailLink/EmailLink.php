<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'EmailLink',
	'author' => 'Travis <travis@wikihow.com>',
	'description' => 'Customed search backend for Google Mini and wikiHow',
);
$wgSpecialPages['EmailLink'] = 'EmailLink';
$wgAutoloadClasses['EmailLink'] = dirname( __FILE__ ) . '/EmailLink.body.php';
$wgExtensionMessagesFiles['EmailLinkAlias'] = dirname( __FILE__ ) . '/EmailLink.alias.php';
