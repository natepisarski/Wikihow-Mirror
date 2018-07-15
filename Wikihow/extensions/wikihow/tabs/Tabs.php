<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['Tabs'] = __DIR__ . '/Tabs.class.php';
$wgAutoloadClasses['MobileTabs'] = __DIR__ . '/MobileTabs.class.php';
$wgAutoloadClasses['DesktopTabs'] = __DIR__ . '/DesktopTabs.class.php';
$wgExtensionMessagesFiles['Tabs'] = __DIR__ .'/tabs.i18n.php';

$wgHooks['BeforePageDisplay'][] = 'MobileTabs::onBeforePageDisplay'; //add mobile js
$wgHooks['DesktopTopStyles'][] = ['DesktopTabs::addDesktopCSS']; //embed desktop css
$wgHooks['MobileEmbedStyles'][] = 'MobileTabs::addMobileCSS'; //embed mobile css

$wgResourceModules['ext.wikihow.tabs'] = array(
	'scripts' => array('tabs.js'),
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/tabs',
	'position' => 'top',
	'targets' => array( 'mobile', 'desktop' ),
);

$wgResourceModules['ext.wikihow.mobile_tag_4'] = array(
	'scripts' => array('mobile_tag_4.js'),
	'localBasePath' => dirname(__FILE__) . '/scripts/',
	'remoteExtPath' => 'wikihow/tabs/scripts',
	'position' => 'top',
	'targets' => array( 'mobile' ),
);
