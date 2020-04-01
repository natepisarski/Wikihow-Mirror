<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminSamples',
	'author' => 'Scott Cushman',
	'description' => 'Tool for managing Sample pages',
);

$wgSpecialPages['AdminSamples'] = 'AdminSamples';
$wgAutoloadClasses['AdminSamples'] = __DIR__ . '/AdminSamples.body.php';

$wgResourceModules['ext.wikihow.adminsamples'] = [
	'scripts' => array('adminsamples.js'),
	'localBasePath' => __DIR__ ,
	'remoteExtPath' => 'wikihow',
	'position' => 'bottom',
	'targets' => array('desktop')
];
