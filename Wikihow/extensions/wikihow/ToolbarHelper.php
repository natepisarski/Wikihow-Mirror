<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ToolbarHelper',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Server side helper for the toolbar, could be replaced by RCBuddy at some point',
);

$wgSpecialPages['ToolbarHelper'] = 'ToolbarHelper';
$wgAutoloadClasses['ToolbarHelper'] = __DIR__ . '/ToolbarHelper.body.php';
