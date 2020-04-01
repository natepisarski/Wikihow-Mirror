<?php

if ( ! defined( 'MEDIAWIKI' ) )
    die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'RCBuddy',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Helper special page for the wikihow editors toobar',
);

$wgSpecialPages['RCBuddy'] = 'RCBuddy';
$wgAutoloadClasses['RCBuddy'] = __DIR__ . '/RCBuddy.body.php';
