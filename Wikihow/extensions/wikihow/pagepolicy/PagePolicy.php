<?php
if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgAutoloadClasses['PagePolicy'] = __DIR__ . '/PagePolicy.class.php';
$wgAutoloadClasses['SpecialValidatron'] = __DIR__ . '/SpecialValidatron.php';
$wgHooks['ArticleViewHeader'][] = 'PagePolicy::onArticleViewHeader';
$wgHooks['BeforePageDisplay'][] = 'PagePolicy::onBeforePageDisplay';
$wgHooks['PreWikihowProcessHTML'][] = 'PagePolicy::onPreWikihowProcessHTML';
$wgHooks['IsEligibleForMobile'][] = 'PagePolicy::onIsEligibleForMobile';

$wgMessagesDirs['PagePolicy'] = __DIR__ . '/i18n/';

$wgSpecialPages['Validatron'] = 'SpecialValidatron';

$wgResourceModules['ext.wikihow.login_popin'] = $wgResourceModulesDesktopBoiler + [
	'styles' => [ 'pagepolicy/login_popin.css' ],
	'scripts' => [
		'common/jquery.simplemodal.1.4.4.min.js',
		'pagepolicy/login_popin.js'
	]
];
$wgResourceModules['ext.wikihow.login_popin']['dependencies'][] = 'jquery.ui.dialog';
