<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = [
	'name' 				=> 'Historical Pageview Tool',
	'author' 			=> 'Andrew Hayworth',
	'description' => 'A tool to query redshift for historical pageview data',
];

$wgSpecialPages['HistoricalPV'] = 'HistoricalPV';
$wgAutoloadClasses['HistoricalPV'] = __DIR__ . '/HistoricalPV.body.php';

$wgResourceModules['ext.wikihow.historicalpv'] = [
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/historicalpv',
	'targets' => ['desktop'],
	'styles' => [
		'resources/styles/historicalpv.css',
		'../common/select2/select2.min.css',
	],
	'scripts' => [
		'../common/select2/select2.js',
		'resources/scripts/historicalpv.js',
	],
	'dependencies' => [
		'ext.wikihow.common_bottom',
		'jquery.ui.datepicker',
		'moment',
	],
];
