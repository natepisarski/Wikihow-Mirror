<?php

/***********************************************
 * Our custom login and sign-up page templates *
 ***********************************************/

if ( !defined( 'MEDIAWIKI' ) ) die( -1 );

$wgResourceModules['ext.wikihow.loginpage'] = array(
	'styles' => 'wikihowlogin.css',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/WikihowLogin',
	'position' => 'top'
);

$wgAutoloadClasses['WikihowLogin'] = __DIR__ . '/WikihowLogin.body.php';
$wgAutoloadClasses['WikihowLoginTemplate'] = __DIR__ . '/WikihowLogin.body.php';
$wgAutoloadClasses['WikihowCreateTemplate'] = __DIR__ . '/WikihowLogin.body.php';
