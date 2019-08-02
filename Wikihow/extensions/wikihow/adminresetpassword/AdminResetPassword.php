<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminResetPassword',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to reset a user\'s password without an email address attached to the account',
);

$wgSpecialPages['AdminResetPassword'] = 'AdminResetPassword';
$wgAutoloadClasses['AdminResetPassword'] = __DIR__ . '/AdminResetPassword.body.php';

$wgResourceModules['ext.wikihow.adminresetpassword'] = [
    'scripts' => [
        'adminresetpassword.js',
    ],
    'styles' => [
        'adminresetpassword.css',
    ],
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/adminresetpassword',
    'position' => 'bottom',
    'targets' => ['desktop'],
    'dependencies' => [
        'ext.wikihow.common_bottom',
    ],
];
