<?php


if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['Ads'] = __DIR__ . '/Ads.class.php';
$wgAutoloadClasses['AdCreator'] = __DIR__ . '/AdCreator.class.php';
$wgAutoloadClasses['SecondaryAdCreator'] = __DIR__ . '/AdCreator.class.php';
$wgAutoloadClasses['DefaultAdCreator'] = __DIR__ . '/AdCreator.class.php';

$wgAutoloadClasses['DefaultInternationalAdCreator'] = __DIR__ . '/AdCreator.class.php';
$wgAutoloadClasses['DefaultInternationalAdCreatorAllAdsense'] = __DIR__ . '/AdCreator.class.php';
$wgAutoloadClasses['DefaultInternationalSearchPageAdCreator'] = __DIR__ . '/AdCreator.class.php';

$wgAutoloadClasses['DefaultDocViewerAdCreator'] = __DIR__ . '/AdCreator.class.php';
$wgAutoloadClasses['DefaultSearchPageAdCreator'] = __DIR__ . '/AdCreator.class.php';
$wgAutoloadClasses['DefaultAlternateDomainAdCreator'] = __DIR__ . '/AlternateDomainAdCreator.class.php';
$wgAutoloadClasses['DefaultCategoryPageAdCreator'] = __DIR__ . '/AdCreator.class.php';
$wgAutoloadClasses['DefaultMainPageAdCreator'] = __DIR__ . '/AdCreator.class.php';

// TODO what are these for?
$wgHooks['BeforeActionbar'][] = 'Ads::onBeforeActionbar';
$wgHooks['AfterActionbar'][] = 'Ads::onAfterActionbar';


