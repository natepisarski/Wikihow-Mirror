<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['CatSearchUI'] = 'CatSearchUI';
$wgAutoloadClasses['CatSearchUI'] = __DIR__ . '/CatSearchUI.body.php';
$wgExtensionMessagesFiles['CatSearchUI'] = __DIR__ . '/CatSearchUI.i18n.php';

$wgResourceModules['ext.wikihow.catsearchui'] = array(
	'scripts' => 'catsearchui.js',
	'styles' => 'catsearchui.css',
	'dependencies' => 'jquery.ui.autocomplete',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/catsearch',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);
