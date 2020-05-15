<?php

if ( ! defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'NAB Prioritizer',
	'author' => 'Gershon Bialer (wikiHow)',
	'description' => 'Shows NAB Priorities for deletion',
);

$wgSpecialPages['NABPrioritizer'] = 'NABPrioritizer';
$wgAutoloadClasses['NABPrioritizer'] = __DIR__ . '/NABPrioritizer.body.php';
