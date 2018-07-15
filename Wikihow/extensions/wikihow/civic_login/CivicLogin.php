<?php
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'CivicLogin',
    'author' => 'Jordan Small',
    'description' => 'Civic login integration for wikihow',
);


$wgSpecialPages['CivicLogin'] = 'CivicLogin';
$wgAutoloadClasses['CivicLogin'] = dirname( __FILE__ ) . '/CivicLogin.body.php';
$wgAutoloadClasses['CivicApiClient'] = dirname( __FILE__ ) . '/CivicApiClient.php';
$wgMessagesDirs['CivicLogin'] = [__DIR__ . '/i18n/'];

// Civic-supplied classes
$wgAutoloadClasses['Civic_JWT'] = dirname( __FILE__ ) . '/civic_sdk/Civic_JWT.php';
$wgAutoloadClasses['Civic_SIP'] = dirname( __FILE__ ) . '/civic_sdk/Civic_SIP.php';


$wgDefaultUserOptions['show_civic_authorship'] = 0;


$wgResourceModules['ext.wikihow.CivicLogin'] = [
    'scripts' => 'civic_login.js',
    'localBasePath' => dirname(__FILE__) . '/',
    'remoteExtPath' => 'wikihow/civic_login',
    'position' => 'bottom',
    'targets' => array( 'desktop', 'mobile' )
];

$wgResourceModules['ext.wikihow.CivicLogin.styles'] = [
    'styles' => 'civic_login.css',
    'localBasePath' => dirname(__FILE__),
    'remoteExtPath' => 'wikihow/civic_login',
    'targets' => array('desktop', 'mobile'),
];

$wgResourceModules['ext.wikihow.mobile.CivicLogin.styles'] = [
    'styles' => 'civic_login_mobile.css',
    'localBasePath' => dirname(__FILE__),
    'remoteExtPath' => 'wikihow/civic_login',
    'targets' => array('mobile'),
];
