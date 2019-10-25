<?php

if ( !defined( 'MEDIAWIKI' ) ) {
exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'MWMessages.php',
	'author' => 'Travis Derouin',
	'description' => 'Maintain Mediawiki messages',
);

$wgSpecialPages['MWMessages'] = 'MWMessages';
$wgAutoloadClasses['MWMessages'] = __DIR__ . '/MWMessages.body.php';
