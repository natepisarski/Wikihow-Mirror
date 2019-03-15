<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['Categorizer'] = 'Categorizer';
$wgAutoloadClasses['Categorizer'] = __DIR__ . '/Categorizer.body.php';
$wgAutoloadClasses['CategorizerUtil'] = __DIR__ . '/CategorizerUtil.class.php';
$wgExtensionMessagesFiles['Categorizer'] = __DIR__ . '/Categorizer.i18n.php';
$wgExtensionMessagesFiles['CategorizerAlias'] = __DIR__ . '/Categorizer.alias.php';

$wgResourceModules['ext.wikihow.categorizer'] = [
    'localBasePath' => __DIR__,
    'targets' => [ 'desktop' ],
    'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery.ui.autocomplete' ],
	'scripts' => [ 'categorizer.js' ],
	'styles' => [ 'categorizer.css' ],
	'messages' => [ 'cat_sorry_label' ],
    'remoteExtPath' => 'wikihow',
    'position' => 'top' ];
