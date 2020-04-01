<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

/**
 * These constants must be defined for this extension to work properly.
 *
 * @const WH_HYPOTHESIS_OPTIMIZELY_ACCESS_TOKEN {string} Optimizely Personal Access Token for the v2 API
 *
 * @const WH_HYPOTHESIS_OPTIMIZELY_PROJECT {string} Optimizely Project ID
 */

$wgExtensionCredits['specialpage'][] = [
	'name' => 'Hypothesis',
	'author' => 'Trevor Parscal <trevorparscal@gmail.com>',
	'description' => 'Provides a way of setting up and running two-sample hypothesis tests'
];

$wgSpecialPages['Hypothesis'] = 'SpecialHypothesis';

$wgAutoloadClasses['Hypothesis'] = __DIR__ . '/Hypothesis.body.php';
$wgAutoloadClasses['SpecialHypothesis'] = __DIR__ . '/SpecialHypothesis.php';
$wgAutoloadClasses['HypothesisDataModule'] = __DIR__ . '/HypothesisDataModule.php';
$wgExtensionMessagesFiles['Hypothesis'] = __DIR__ . '/Hypothesis.i18n.php';
$wgExtensionMessagesFiles['HypothesisAliases'] = __DIR__ . '/Hypothesis.alias.php';
$wgAutoloadClasses['ApiHypothesisExperiment'] = __DIR__ . '/api/ApiHypothesisExperiment.php';
$wgAutoloadClasses['ApiHypothesisExperiments'] = __DIR__ . '/api/ApiHypothesisExperiments.php';
$wgAutoloadClasses['ApiHypothesisTest'] = __DIR__ . '/api/ApiHypothesisTest.php';
$wgAutoloadClasses['ApiHypothesisTests'] = __DIR__ . '/api/ApiHypothesisTests.php';
$wgAutoloadClasses['Optimizely'] = __DIR__ . '/Optimizely.php';

$wgAPIModules['hypx'] = 'ApiHypothesisExperiment';
$wgAPIModules['hypxs'] = 'ApiHypothesisExperiments';
$wgAPIModules['hypt'] = 'ApiHypothesisTest';
$wgAPIModules['hypts'] = 'ApiHypothesisTests';

$wgHooks['BeforePageDisplay'][] = 'Hypothesis::onBeforePageDisplay';
$wgHooks['BeforeInitialize'][] = 'Hypothesis::onBeforeInitialize';
$wgHooks['OptimizelyGetTag'][] = 'Hypothesis::onOptimizelyGetTag';

$wgResourceModules['ext.wikihow.hypothesis.data'] = [ 'class' => 'HypothesisDataModule' ];

$wgResourceModules['ext.wikihow.hypothesis.core'] = [
	'scripts' => [ 'resources/hypothesis.core.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Hypothesis',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [
		'wikihow.common.mustache'
	]
];

$wgResourceModules['ext.wikihow.hypothesis'] = [
	'styles' => [ 'resources/hypothesis.less' ],
	'scripts' => [ 'resources/hypothesis.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Hypothesis',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [
		'ext.wikihow.hypothesis.core',
		'ext.wikihow.hypothesis.data',
		'wikihow.router',
		'moment'
	]
];

$wgResourceModules['ext.wikihow.hypothesis.history'] = [
	'styles' => [ 'resources/hypothesis.history.less' ],
	'scripts' => [ 'resources/hypothesis.history.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Hypothesis',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [
		'ext.wikihow.hypothesis.core',
		'ext.wikihow.hypothesis.data'
	]
];

$wgResourceModules['ext.wikihow.hypothesis.view.mobile'] = [
	'styles' => [ 'resources/hypothesis.view.mobile.less' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Hypothesis',
	'position' => 'top',
	'targets' => [ 'mobile' ]
];
