<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Pagestats',
	'author' => 'Bebeth Steudel',
	'description' => 'Boring stats on article pages',
);

$wgSpecialPages['Pagestats'] = 'Pagestats';
$wgAutoloadClasses['Pagestats'] = dirname( __FILE__ ) . '/Pagestats.body.php';
$wgExtensionMessagesFiles['Pagestats'] = dirname(__FILE__) . '/Pagestats.i18n.php';
$wgResourceModules['ext.wikihow.pagestats'] = array(
	'scripts' => array(
		'../common/plotly-latest.min.js',
		'../common/moment.min.js',
		'graphs.js',
	),
	'styles' => array(
		'graphs.css',
	),
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/pagestats',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'ext.wikihow.common_bottom',
		'ext.wikihow.graphs_modal',
	),
);
