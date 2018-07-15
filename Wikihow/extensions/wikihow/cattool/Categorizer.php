<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['Categorizer'] = 'Categorizer';
$wgAutoloadClasses['Categorizer'] = dirname( __FILE__ ) . '/Categorizer.body.php';
$wgAutoloadClasses['CategorizerUtil'] = dirname( __FILE__ ) . '/CategorizerUtil.class.php';
$wgExtensionMessagesFiles['Categorizer'] = dirname(__FILE__) . '/Categorizer.i18n.php';
$wgExtensionMessagesFiles['CategorizerAlias'] = dirname(__FILE__) . '/Categorizer.alias.php';

$wgResourceModules['ext.wikihow.categorizer'] = [
    'localBasePath' => __DIR__,
    'targets' => [ 'desktop' ],
    'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery.ui.autocomplete' ],
	'scripts' => [ 'categorizer.js' ],
	'styles' => [ 'categorizer.css' ],
	'messages' => [ 'cat_sorry_label' ],
    'remoteExtPath' => 'wikihow',
    'position' => 'top' ];
