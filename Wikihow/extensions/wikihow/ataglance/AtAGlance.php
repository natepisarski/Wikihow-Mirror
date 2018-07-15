<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['AtAGlance'] = dirname(__FILE__) . '/AtAGlance.body.php';
$wgExtensionMessagesFiles['AtAGlance'] = dirname(__FILE__) . '/AtAGlance.i18n.php';

$wgResourceModules['ext.wikihow.ataglance'] = array(
	'scripts' => array( 'ataglance.js', ),
	'styles' => array( 'ataglance.css' ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/ataglance',
	'messages' => array('ataglance_slideshow_title'),
);
$wgResourceModules['ext.wikihow.ataglance.slider'] = array(
	'scripts' => array( 'jquery.lightSlider.js' ),
	'styles' => array( 'lightSlider.css' ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/ataglance',
	'dependencies' => 'ext.wikihow.ataglance',
);
$wgHooks['BeforePageDisplay'][] = 'AtAGlance::onBeforePageDisplay';
