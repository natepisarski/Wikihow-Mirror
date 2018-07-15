<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['WikiGame'] = dirname( __FILE__ ) . '/WikiGame.class.php';

$wgHooks['ProcessArticleHTMLAfter'][] = 'WikiGame::addGame';
$wgHooks['BeforePageDisplay'][] = 'WikiGame::onBeforePageDisplay';

$wgResourceModules['ext.wikihow.wikigame.js'] = [
	'scripts' => ['wikigame.js'],
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/wikigame',
	'position' => 'bottom',
	'targets' => [ 'desktop', 'mobile' ],
];

$wgResourceModules['ext.wikihow.wikigame.less'] = [
	'styles' => ['wikigame.less'],
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/wikigame',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ],
];