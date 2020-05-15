<?php

if ( ! defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RCBuddy',
	'author' => 'Travis Derouin (wikiHow)',
	'description' => 'Helper special page for the wikihow editors toobar',
);

$wgSpecialPages['RCBuddy'] = 'RCBuddy';
$wgAutoloadClasses['RCBuddy'] = __DIR__ . '/RCBuddy.body.php';
