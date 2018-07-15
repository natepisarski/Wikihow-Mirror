<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Awards',
    'author' => 'Vu <vu@wikihow.com>',
    'description' => 'wikiHow Awards Page', 
);


$wgSpecialPages['Awards'] = 'Awards'; 
$wgAutoloadClasses['Awards'] = dirname( __FILE__ ) . '/Awards.body.php';
