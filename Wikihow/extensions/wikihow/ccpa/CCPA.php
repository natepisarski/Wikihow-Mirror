<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CCPA',
	'author' => 'aaron',
	'description' => 'endpoint for ccpa amp',
);

$wgSpecialPages['CCPA'] = 'CCPA';
$wgAutoloadClasses['CCPA'] = __DIR__ . '/CCPA.body.php';
