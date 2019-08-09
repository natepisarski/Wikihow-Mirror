<?php
if ( ! defined( 'MEDIAWIKI' ) )
  die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Alfredo',
	'author' => 'Gershon Bialer',
	'description' => 'Add wikiphotos to international',
);

$wgSpecialPages['Alfredo'] = 'Alfredo';
$wgAutoloadClasses['Alfredo'] = __DIR__ . '/Alfredo.body.php';

$wgResourceModules['ext.wikihow.alfredo'] = [
	'scripts' => [
		'../common/download.jQuery.js',
		'alfredo.js'
	],
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery', 'ext.wikihow.common_bottom' ],
	'remoteExtPath' => 'wikihow/alfredo',
	'position' => 'top'
];
