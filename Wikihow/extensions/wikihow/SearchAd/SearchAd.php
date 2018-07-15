<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['SearchAd'] = dirname(__FILE__) . '/SearchAd.class.php';

$wgSpecialPages['SearchAd'] = 'SearchAd';
$wgExtensionMessagesFiles['SearchAd'] = dirname(__FILE__) . '/SearchAd.i18n.php';

$wgResourceModules['ext.wikihow.search_ad'] = array(
	'scripts' => 'search_ad.js',
	'styles' => 'search_ad.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/SearchAd',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);





