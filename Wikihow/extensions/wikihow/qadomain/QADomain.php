<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'QA Domain',
	'author' => 'Bebeth Steudel',
	'description' => 'Special page to simulate new domain for QA',
);

$wgSpecialPages['QADomain'] = 'QADomain';
$wgAutoloadClasses['QADomain'] = __DIR__ . '/QADomain.body.php';
$wgSpecialPages['AdminQADomain'] = 'AdminQADomain';
$wgAutoloadClasses['AdminQADomain'] = __DIR__ . '/AdminQADomain.body.php';

$wgResourceModules['ext.wikihow.qadomain'] = array(
	'styles' => 'qadomain.less',
	'scripts' => [
		'../../../skins/WikiHow/MachinifyAPI.js',
	],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/qadomain',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.adminqadomain'] = array(
	'scripts' => 'adminqadomain.js',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/qadomain',
	'position' => 'top',
	'targets' => array( 'desktop')
);

$wgHooks['DeferHeadScripts'][] = ['QADomain::onDeferHeadScripts'];
$wgHooks['BeforePageDisplay'][] = ['QADomain::onBeforePageDisplay'];
$wgHooks['OutputPageAfterGetHeadLinksArray'][] = 'QADomain::onOutputPageAfterGetHeadLinksArray';
