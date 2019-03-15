<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['RCTestAdmin'] = 'RCTestAdmin';
$wgAutoloadClasses['RCTestAdmin'] = __DIR__ . '/RCTestAdmin.body.php';
$wgExtensionMessagesFiles['RCTestAdmin'] = __DIR__ . '/RCTestAdmin.i18n.php';

$wgResourceModules['ext.wikihow.rcTestAdmin'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'styles' => [ 'rctestadmin.css' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery.ui.dialog' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top' ];

