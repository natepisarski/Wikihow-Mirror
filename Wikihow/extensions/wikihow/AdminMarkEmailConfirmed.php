<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminMarkEmailConfirmed',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to confirm a user\'s email address attached to the account',
);

$wgSpecialPages['AdminMarkEmailConfirmed'] = 'AdminMarkEmailConfirmed';
$wgAutoloadClasses['AdminMarkEmailConfirmed'] = __DIR__ . '/AdminMarkEmailConfirmed.body.php';
