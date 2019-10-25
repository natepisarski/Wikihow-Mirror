<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminMarkEmailConfirmed',
	'author' => 'Reuben',
	'description' => 'Tool for support personnel to confirm a user\'s email address attached to the account',
);

$wgSpecialPages['AdminMarkEmailConfirmed'] = 'AdminMarkEmailConfirmed';
$wgAutoloadClasses['AdminMarkEmailConfirmed'] = __DIR__ . '/AdminMarkEmailConfirmed.body.php';

$wgResourceModules['ext.wikihow.adminmarkemailconfirmed'] = array(
    'scripts' => ['adminmarkemailconfirmed.js'],
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/adminmarkemailconfirmed',
    'position' => 'bottom',
    'targets' => ['desktop', 'mobile'],
    'dependencies' => ['ext.wikihow.common_bottom', 'jquery'],
);
