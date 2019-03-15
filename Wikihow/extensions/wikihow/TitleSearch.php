<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'TitleSearch',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Used for the related wikihows tool drop down auto-suggest feature',
);

$wgSpecialPages['TitleSearch'] = 'TitleSearch';
$wgAutoloadClasses['TitleSearch'] = __DIR__ . '/TitleSearch.body.php';
