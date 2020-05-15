<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Winter Survival Guide',
	'author' => 'Scott Cushman',
	'description' => 'Accessory stuff for wikiHow:Winter-Survival-Guide'
);

$wgAutoloadClasses['WinterSurvivalGuide'] = __DIR__ . '/WinterSurvivalGuide.class.php';

$wgMessagesDirs['WinterSurvivalGuide'] = __DIR__ . '/i18n';

$wgResourceModules['ext.wikihow.winter_survival_guide_cta'] = [
	'styles' => 'assets/winter_survival_guide_cta.css',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/WinterSurvivalGuide',
	'targets' => ['desktop'],
	'position' => 'top'
];
