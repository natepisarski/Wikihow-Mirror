<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['extension'][] = array(
    'name' => 'LoginDialog',
    'author' => 'Trevor Parscal <trevor@wikihow.com>',
    'description' => 'Login using a dialog.',
);

$wgResourceModules[ 'ext.wikihow.loginDialog' ] = [
	'scripts' => [ 'logindialog.js' ],
	'styles' => [ 'logindialog.less' ],
	'dependencies' => [
		'oojs-ui-core',
		'oojs-ui-widgets',
		'oojs-ui-windows',
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/LoginDialog',
	'position' => 'bottom',
	'targets' => [ 'desktop', 'mobile' ],
	'messages' => [
		'userlogin-yourname-ph',
		'userlogin-yourpassword-ph',
		'rememberme',
		'pt-login-button',
		'loginor',
		'userlogin-resetpassword-link',
		'log_in_via',
		"ulb-btn-fb",
		"ulb-btn-gplus",
		"ulb-btn-civic",
		'nologinlink',
		'cancel'
	]
];
