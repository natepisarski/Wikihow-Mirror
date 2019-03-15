<?php
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'CivicLogin',
    'author' => 'Jordan Small',
    'description' => 'Civic login integration for wikihow',
);


$wgSpecialPages['CivicLogin'] = 'CivicLogin';
$wgAutoloadClasses['CivicLogin'] = __DIR__ . '/CivicLogin.body.php';
$wgAutoloadClasses['CivicApiClient'] = __DIR__ . '/CivicApiClient.php';
$wgMessagesDirs['CivicLogin'] = [__DIR__ . '/i18n/'];

// Civic-supplied classes
$wgAutoloadClasses['Civic_JWT'] = __DIR__ . '/civic_sdk/Civic_JWT.php';
$wgAutoloadClasses['Civic_SIP'] = __DIR__ . '/civic_sdk/Civic_SIP.php';


$wgDefaultUserOptions['show_civic_authorship'] = 0;


$wgResourceModules['ext.wikihow.CivicLogin'] = [
    'scripts' => 'civic_login.js',
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/civic_login',
    'position' => 'bottom',
    'targets' => array( 'desktop', 'mobile' )
];

$wgResourceModules['ext.wikihow.CivicLogin.styles'] = [
    'styles' => 'civic_login.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/civic_login',
    'targets' => array('desktop', 'mobile'),
];

$wgResourceModules['ext.wikihow.mobile.CivicLogin.styles'] = [
    'styles' => 'civic_login_mobile.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/civic_login',
    'targets' => array('mobile'),
];
