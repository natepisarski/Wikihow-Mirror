<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCopyCheck',
	'author' => 'Scott Cushman',
	'description' => 'Tool for checking for plagiarism',
);

$wgSpecialPages['AdminCopyCheck'] = 'AdminCopyCheck';
$wgAutoloadClasses['AdminCopyCheck'] = __DIR__ . '/AdminCopyCheck.body.php';
