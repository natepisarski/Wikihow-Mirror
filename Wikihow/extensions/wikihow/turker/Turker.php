<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Turker',
	'author' => 'RJSB',
	'description' => 'Interface for autoturk tool, to post work to mTurk and view the results',
);

$wgSpecialPages['Turker'] = 'Turker';
$wgAutoloadClasses['Turker'] = __DIR__ . '/Turker.body.php';
