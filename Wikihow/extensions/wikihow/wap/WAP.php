<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['WAPMaintenance'] = __DIR__ . '/WAPMaintenance.class.php';
$wgAutoloadClasses['WAPTemplate'] = __DIR__ . '/WAPTemplate.class.php';
$wgAutoloadClasses['WAPReport'] = __DIR__ . '/WAPReport.class.php';
$wgAutoloadClasses['WAPUtil'] = __DIR__ . '/WAPUtil.class.php';
$wgAutoloadClasses['WAPArticlePager'] = __DIR__ . '/WAPArticlePager.class.php';
$wgAutoloadClasses['WAPDB'] = __DIR__ . '/WAPDB.class.php';
$wgAutoloadClasses['WAPLinker'] = __DIR__ . '/WAPLinker.class.php';
$wgAutoloadClasses['WAPTagDB'] = __DIR__ . '/WAPTagDB.class.php';
$wgAutoloadClasses['WAPUser'] = __DIR__ . '/WAPUser.class.php';
$wgAutoloadClasses['WAPArticle'] = __DIR__ . '/WAPArticle.class.php';
$wgAutoloadClasses['WAPConfig'] = __DIR__ . '/WAPConfig.class.php';
$wgAutoloadClasses['WAPUIController'] = __DIR__ . '/WAPUIController.class.php';
$wgAutoloadClasses['WAPUIUserController'] = __DIR__ . '/WAPUIUserController.class.php';
$wgAutoloadClasses['WAPUIAdminController'] = __DIR__ . '/WAPUIAdminController.class.php';

$wgResourceModules['ext.wikihow.wap'] = array(
	'scripts' => [
		'wap.js',
		'../common/jquery-ui-1.9.2.core_datepicker.custom.min.js',
		'../common/jquery.tablesorter.min.js',
		'../common/download.jQuery.js'
	],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/wap',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
	'dependencies' => ['jquery.chosen', 'jquery']
);

$wgResourceModules['ext.wikihow.wap_styles'] = array(
	'styles' => ['wap.css'],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/wap',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);
