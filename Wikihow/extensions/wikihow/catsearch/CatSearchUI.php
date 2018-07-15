<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['CatSearchUI'] = 'CatSearchUI';
$wgAutoloadClasses['CatSearchUI'] = dirname( __FILE__ ) . '/CatSearchUI.body.php';
$wgExtensionMessagesFiles['CatSearchUI'] = dirname(__FILE__) . '/CatSearchUI.i18n.php';

$wgResourceModules['ext.wikihow.catsearchui'] = array(
	'scripts' => 'catsearchui.js',
	'styles' => 'catsearchui.css',
	'dependencies' => 'jquery.ui.autocomplete',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/catsearch',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);