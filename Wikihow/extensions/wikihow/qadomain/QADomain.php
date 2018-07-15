<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'QA Domain',
	'author' => 'Bebeth Steudel',
	'description' => 'Special page to simulate new domain for QA',
);

$wgSpecialPages['QADomain'] = 'QADomain';
$wgAutoloadClasses['QADomain'] = dirname(__FILE__) . '/QADomain.body.php';
$wgSpecialPages['AdminQADomain'] = 'AdminQADomain';
$wgAutoloadClasses['AdminQADomain'] = dirname(__FILE__) . '/AdminQADomain.body.php';

$wgResourceModules['ext.wikihow.qadomain'] = array(
	'styles' => 'qadomain.less',
	'scripts' => [
		'../../../skins/WikiHow/MachinifyAPI.js',
	],
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/qadomain',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.adminqadomain'] = array(
	'scripts' => 'adminqadomain.js',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/qadomain',
	'position' => 'top',
	'targets' => array( 'desktop')
);

$wgHooks['DeferHeadScripts'][] = ['QADomain::onDeferHeadScripts'];
$wgHooks['BeforePageDisplay'][] = ['QADomain::onBeforePageDisplay'];
$wgHooks['OutputPageAfterGetHeadLinksArray'][] = 'QADomain::onOutputPageAfterGetHeadLinksArray';