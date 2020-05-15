<?php

if ( ! defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Atlas Admin',
	'author' => 'Gershon Bialer (wikiHow)',
	'description' => 'Admin Atlas lists and such',
);

$wgSpecialPages['AtlasAdmin'] = 'AtlasAdmin';
$wgAutoloadClasses['AtlasAdmin'] = __DIR__ . '/AtlasAdmin.body.php';
