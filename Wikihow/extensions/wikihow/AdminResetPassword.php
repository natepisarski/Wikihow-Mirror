<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminResetPassword',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to reset a user\'s password without an email address attached to the account',
);

$wgSpecialPages['AdminResetPassword'] = 'AdminResetPassword';
$wgAutoloadClasses['AdminResetPassword'] = dirname( __FILE__ ) . '/AdminResetPassword.body.php';

