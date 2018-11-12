<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['DesktopAds'] = dirname(__FILE__) . '/DesktopAds.class.php';
$wgAutoloadClasses['DeprecatedDFPAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion1'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion2'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion3'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion4'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion5'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MethodsButNoIntroAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['AdsenseRaddingRR1AdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['DocViewerAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['DocViewerAdCreatorVersion2'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['SearchPageAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['InternationalAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['AlternateDomainAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['CategoryPageAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MainPageAdCreator'] = dirname(__FILE__) . '/DesktopAdCreator.class.php';

$wgHooks['BeforeActionbar'][] = 'DesktopAds::onBeforeActionbar';
$wgHooks['AfterActionbar'][] = 'DesktopAds::onAfterActionbar';


