<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'LoginReminder',
    'author' => 'Bebeth Steudel',
    'description' => 'Tool to retrieve username/password',
);

$wgExtensionMessagesFiles['LoginReminder'] = __DIR__ . '/LoginReminder.i18n.php';
$wgSpecialPages['LoginReminder'] = 'LoginReminder';
$wgAutoloadClasses['LoginReminder'] = __DIR__ . '/LoginReminder.body.php';
$wgSpecialPages['LoginFacebook'] = 'LoginFacebook';
$wgAutoloadClasses['LoginFacebook'] = __DIR__ . '/LoginReminder.body.php';
$wgSpecialPages['LoginCheck'] = 'LoginCheck';
$wgAutoloadClasses['LoginCheck'] = __DIR__ . '/LoginReminder.body.php';

$wgResourceModules['ext.wikihow.loginreminder'] = [
    'scripts'       => 'LoginReminder.js',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/loginreminder',
    'targets'       => ['desktop'],
    'messages' => [ 'lr_choose_longer_password', 'lr_passwords_dont_match', 'lr_password_reset' ],
	'position' => 'top'
];
