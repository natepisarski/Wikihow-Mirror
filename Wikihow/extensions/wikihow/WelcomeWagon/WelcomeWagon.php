<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'WelcomeWagon',
	'author' => 'Aaron',
	'description' => 'Tool for support personnel to help welcome new users',
);

$wgExtensionMessagesFiles['WelcomeWagon'] = __DIR__ .'/WelcomeWagon.i18n.php';

$wgSpecialPages['WelcomeWagon'] = 'WelcomeWagon';
$wgAutoloadClasses['WelcomeWagon'] = __DIR__ . '/WelcomeWagon.body.php';

$wgLogTypes[] = 'welcomewag';
$wgLogNames['welcomewag'] = 'welcomewag';
$wgLogHeaders['welcomewag'] = 'welcomewag_log';

$wgResourceModules['ext.wikihow.welcome_wagon'] = [
    'styles' => ['welcomewagon.css'],
    'scripts' => ['welcomewagon.js'],
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/WelcomeWagon',
    'position' => 'top',
    'targets' => ['desktop', 'mobile'],
    'dependencies' => ['ext.wikihow.common_top'],
];
