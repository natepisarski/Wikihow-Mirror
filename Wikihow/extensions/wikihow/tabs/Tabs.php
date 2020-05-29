<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['MobileTabs'] = __DIR__ . '/MobileTabs.class.php';

$wgHooks['BeforePageDisplay'][] = 'MobileTabs::onBeforePageDisplay'; //add mobile js

$wgResourceModules['ext.wikihow.mobile_tabs'] = array(
	'scripts' => array('mobile_tabs.js'),
	'localBasePath' => __DIR__ . '/scripts/',
	'remoteExtPath' => 'wikihow/tabs/scripts',
	'position' => 'top',
	'targets' => array( 'mobile' ),
);