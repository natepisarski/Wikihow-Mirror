<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['DesktopAds'] = __DIR__ . '/DesktopAds.class.php';
$wgAutoloadClasses['DeprecatedDFPAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion1'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion2'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion3'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion4'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorVersion5'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MixedAdCreatorScrollTo'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['TwoRightRailAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['DocViewerAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['DocViewerAdCreatorVersion2'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['SearchPageAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['InternationalAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['AlternateDomainAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['CategoryPageAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';
$wgAutoloadClasses['MainPageAdCreator'] = __DIR__ . '/DesktopAdCreator.class.php';

$wgHooks['BeforeActionbar'][] = 'DesktopAds::onBeforeActionbar';
$wgHooks['AfterActionbar'][] = 'DesktopAds::onAfterActionbar';


