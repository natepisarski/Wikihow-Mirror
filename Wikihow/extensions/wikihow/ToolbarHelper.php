<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

// could be replaced by RCBuddy at some point, or an api endpoint
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ToolbarHelper',
	'author' => 'Travis Derouin (wikiHow)',
	'description' => 'Server side helper for an old browser toolbar',
);

$wgSpecialPages['ToolbarHelper'] = 'ToolbarHelper';
$wgAutoloadClasses['ToolbarHelper'] = __DIR__ . '/ToolbarHelper.body.php';
