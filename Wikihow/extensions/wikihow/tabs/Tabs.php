<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['Tabs'] = __DIR__ . '/Tabs.class.php';
$wgAutoloadClasses['MobileTabs'] = __DIR__ . '/MobileTabs.class.php';
$wgAutoloadClasses['DesktopTabs'] = __DIR__ . '/DesktopTabs.class.php';
$wgExtensionMessagesFiles['Tabs'] = __DIR__ .'/tabs.i18n.php';

$wgHooks['BeforePageDisplay'][] = 'MobileTabs::onBeforePageDisplay'; //add mobile js
$wgHooks['DesktopTopStyles'][] = ['DesktopTabs::addDesktopCSS']; //embed desktop css
$wgHooks['MinvervaTemplateBeforeRender'][] = ['MobileTabs::addTabsToArticle']; //add the tabs on mobile

$wgResourceModules['ext.wikihow.tabs'] = array(
	'scripts' => array('tabs.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/tabs',
	'position' => 'top',
	'targets' => array( 'mobile', 'desktop' ),
);

$wgResourceModules['ext.wikihow.mobile_tabs'] = array(
	'scripts' => array('mobile_tabs.js'),
	'localBasePath' => __DIR__ . '/scripts/',
	'remoteExtPath' => 'wikihow/tabs/scripts',
	'position' => 'top',
	'targets' => array( 'mobile' ),
);

