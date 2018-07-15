<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'StatsList',
    'author' => 'Bebeth <bebeth@wikihow.com>',
    'description' => 'Just a bunch of stats for Krystle',
);

$wgSpecialPages['StatsList'] = 'StatsList';

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['StatsList'] = $dir . 'StatsList.body.php';
