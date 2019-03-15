<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'FBLogin',
    'author' => 'Jordan Small',
    'description' => 'Facebook app login integration to wikihow',
);

$wgResourceModules['ext.wikihow.FBEnable'] = array(
    'scripts' => 'fbenable.js',
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/fblogin',
    'position' => 'bottom',
    'targets' => array( 'desktop', 'mobile' )
);
$wgResourceModules['ext.wikihow.FBLogin'] = array(
    'scripts' => 'fblogin.js',
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/fblogin',
    'position' => 'bottom',
    'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.FBLogin.styles'] = array(
    'styles' => 'fblogin.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/fblogin',
    'targets' => array('desktop', 'mobile'),
);

$wgResourceModules['ext.wikihow.mobile.FBLogin.styles'] = array(
    'styles' => 'mobile-fblogin.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/fblogin',
    'targets' => array('mobile'),
);

$wgSpecialPages['FBLogin'] = 'FBLogin';
$wgAutoloadClasses['FBLogin'] = __DIR__ . '/FBLogin.body.php';
$wgAutoloadClasses['FacebookApiClient'] = __DIR__ . '/FacebookApiClient.php';
$wgExtensionMessagesFiles['FBLogin'] = __DIR__ . '/FBLogin.i18n.php';

/**
 * Facebook Login debug flag -- always check-in as false and make a
 * local edit.
 */
define('FBLOGIN_DEBUG', false);

