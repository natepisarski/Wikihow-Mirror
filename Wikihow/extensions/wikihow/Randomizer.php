<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Randomizer',
	'author' => 'wikiHow',
	'description' => 'Select a random page from a set of higher quality articles, rather than just any page',
);

$wgSpecialPages['Randomizer'] = 'Randomizer';
$wgAutoloadClasses['Randomizer'] = __DIR__ . '/Randomizer.body.php';
$wgExtensionMessagesFiles['RandomizerAliases'] = __DIR__ . '/Randomizer.alias.php';
