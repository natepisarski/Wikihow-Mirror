<?php
if ( ! defined( 'MEDIAWIKI' ) )
  die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Dedup',
    'author' => 'Gershon Bialer',
    'description' => 'Dedup titles',
);

$wgSpecialPages['Dedup'] = 'Dedup';
$wgAutoloadClasses['Dedup'] = __DIR__ . '/Dedup.body.php';

$wgResourceModules['ext.wikihow.Dedup'] = array(
	'scripts' => ['dedup.js'],
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/dedup',
	'position' => 'top',
	'targets' => [ 'desktop' ]
);
