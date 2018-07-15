<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['WAPMaintenance'] = dirname(__FILE__) . '/WAPMaintenance.class.php';
$wgAutoloadClasses['WAPTemplate'] = dirname(__FILE__) . '/WAPTemplate.class.php';
$wgAutoloadClasses['WAPReport'] = dirname(__FILE__) . '/WAPReport.class.php';
$wgAutoloadClasses['WAPUtil'] = dirname(__FILE__) . '/WAPUtil.class.php';
$wgAutoloadClasses['WAPArticlePager'] = dirname(__FILE__) . '/WAPArticlePager.class.php';
$wgAutoloadClasses['WAPDB'] = dirname(__FILE__) . '/WAPDB.class.php';
$wgAutoloadClasses['WAPLinker'] = dirname(__FILE__) . '/WAPLinker.class.php';
$wgAutoloadClasses['WAPTagDB'] = dirname(__FILE__) . '/WAPTagDB.class.php';
$wgAutoloadClasses['WAPUser'] = dirname(__FILE__) . '/WAPUser.class.php';
$wgAutoloadClasses['WAPArticle'] = dirname(__FILE__) . '/WAPArticle.class.php';
$wgAutoloadClasses['WAPConfig'] = dirname(__FILE__) . '/WAPConfig.class.php';
$wgAutoloadClasses['WAPUIController'] = dirname(__FILE__) . '/WAPUIController.class.php';
$wgAutoloadClasses['WAPUIUserController'] = dirname(__FILE__) . '/WAPUIUserController.class.php';
$wgAutoloadClasses['WAPUIAdminController'] = dirname(__FILE__) . '/WAPUIAdminController.class.php';
