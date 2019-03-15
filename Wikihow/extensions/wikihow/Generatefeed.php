<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'GenerateFeed',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Generates the RSS feed for the featured articles',
);

$wgSpecialPages['GenerateFeed'] = 'GenerateFeed';
$wgAutoloadClasses['GenerateFeed'] = __DIR__ . '/Generatefeed.body.php';
