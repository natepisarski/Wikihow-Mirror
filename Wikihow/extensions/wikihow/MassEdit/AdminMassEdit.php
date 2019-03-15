<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminMassEdit',
	'author' => 'Aaron G',
	'description' => 'tool for admin to put edits on multiple files',
);

$wgSpecialPages['AdminMassEdit'] = 'AdminMassEdit';
$wgAutoloadClasses['AdminMassEdit'] = __DIR__ . '/AdminMassEdit.body.php';
