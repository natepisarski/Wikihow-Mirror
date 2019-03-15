<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['special'][] = array(
	'name' => 'Interests',
	'author' => 'Travis Derouin',
	'description' => 'Gather a bunch of interests for a user and use it to suggest articles for them to edit or create',
	'url' => 'http://www.wikihow.com/WikiHow:Interests-Extension',
);

$wgExtensionMessagesFiles['Interests'] = __DIR__ . '/Interests.i18n.php';

$wgSpecialPages['Interests'] = 'Interests';
$wgAutoloadClasses['Interests'] = __DIR__ . '/Interests.body.php';
