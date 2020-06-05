<?php
if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgAutoloadClasses['PagePolicy'] = __DIR__ . '/PagePolicy.class.php';
$wgAutoloadClasses['SpecialValidatron'] = __DIR__ . '/SpecialValidatron.php';

$wgHooks['BeforeInitialize'][] = 'PagePolicy::onBeforeInitialize';
$wgHooks['ArticleViewHeader'][] = 'PagePolicy::onArticleViewHeader';
$wgHooks['BeforePageDisplay'][] = 'PagePolicy::onBeforePageDisplay';
$wgHooks['PreWikihowProcessHTML'][] = 'PagePolicy::onPreWikihowProcessHTML';
$wgHooks['IsEligibleForMobile'][] = 'PagePolicy::onIsEligibleForMobile';

$wgMessagesDirs['PagePolicy'] = __DIR__ . '/i18n/';

$wgSpecialPages['Validatron'] = 'SpecialValidatron';
