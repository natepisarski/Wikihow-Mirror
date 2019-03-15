<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'UserLoginBox',
	'author' => 'Scott Cushman',
	'description' => 'This is the component that displays and processes user login in the header',
);

$wgSpecialPages['UserLoginBox'] = 'UserLoginBox';
$wgAutoloadClasses['UserLoginBox'] = __DIR__ . '/UserLoginBox.body.php';
$wgMessagesDirs['UserLoginBox'] = __DIR__ . '/i18n/';

$wgResourceModules['ext.wikihow.userloginbox'] = array(
	'scripts' => 'userloginbox.js',
	'messages' => [ 'ulb-btn-loading' ],
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/userloginbox',
);
