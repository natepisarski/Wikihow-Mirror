<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Admin MMK Queries',
	'author' => 'Bebeth Steudel',
);

$wgSpecialPages['AdminMMKQueries'] = 'AdminMMKQueries';
$wgAutoloadClasses['AdminMMKQueries'] = dirname( __FILE__ ) . '/AdminMMKQueries.body.php';

$wgResourceModules['ext.wikihow.adminmmkqueries'] = array(
	'scripts' => array(
		'adminmmkqueries.js',
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/mmk',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile')
);

