<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'EditTurk',
	'author' => 'RJSB',
	'description' => 'Interface for autoturk tool, to post work to mTurk and view the results',
);

$wgSpecialPages['EditTurk'] = 'EditTurk';
$wgAutoloadClasses['EditTurk'] = __DIR__ . '/EditTurk.body.php';
