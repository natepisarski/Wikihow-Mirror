<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'PageStats',
	'author' => 'Bebeth Steudel',
	'description' => 'Boring stats on article pages',
);

$wgSpecialPages['PageStats'] = 'PageStats';
$wgAutoloadClasses['PageStats'] = __DIR__ . '/Pagestats.body.php';
$wgExtensionMessagesFiles['PageStats'] = __DIR__ . '/Pagestats.i18n.php';
$wgResourceModules['ext.wikihow.pagestats'] = array(
	'scripts' => array(
		'../common/plotly-latest.min.js',
		'../common/moment.min.js',
		'graphs.js',
	),
	'styles' => array(
		'graphs.css',
	),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/pagestats',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'ext.wikihow.common_bottom',
		'ext.wikihow.graphs_modal',
	),
);
