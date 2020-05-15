<?php

if ( ! defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'EditContribution',
	'author' => 'Gershon Bialer (wikiHow)',
	'description' => 'A tool to see what users have contributed to an article',
);

$wgSpecialPages['EditContribution'] = 'EditContribution';
$wgAutoloadClasses['EditContribution'] = __DIR__ . '/EditContribution.body.php';
