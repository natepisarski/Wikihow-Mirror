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
$wgAutoloadClasses['EmailLink'] = __DIR__ . '/EmailLink.body.php';
$wgExtensionMessagesFiles['EmailLinkAlias'] = __DIR__ . '/EmailLink.alias.php';
