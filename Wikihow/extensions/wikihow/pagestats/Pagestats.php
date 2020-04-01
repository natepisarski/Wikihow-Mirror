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

$wgHooks['BeforePageDisplay'][] = ['PageStats::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.pagestats'] = array(
	'scripts' => array(
		'../common/plotly-latest.min.js',
		'../common/moment.min.js',
		'graphs.js',
		'pagestats.js',
		'../SensitiveArticle/widget/resources/sensitive_article_widget.js',
	),
	'styles' => array(
		'graphs.css',
		'pagestats.css'
	),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/pagestats',
	'position' => 'bottom',
	'targets' => array('desktop','mobile'),
	'dependencies' => array(
		'ext.wikihow.common_bottom',
		'ext.wikihow.graphs_modal',
	),
);
