<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Finner',
	'author' => 'George Bahij',
	'description' => 'Modified SpecialSearch',
);

$wgExtensionMessagesFiles['Finner'] = __DIR__ . '/Finner.i18n.php';
$wgSpecialPages['Finner'] = 'Finner';
$wgAutoloadClasses['Finner'] = __DIR__ . '/Finner.body.php';
$wgAutoloadClasses['FinnerHooks'] = __DIR__ . '/FinnerHooks.class.php';
$wgAutoloadClasses['FinnerSearchEngine'] = __DIR__ . '/FinnerSearchEngine.class.php';

$wgResourceModules['ext.wikihow.finner.styles'] = array(
	'styles' => array(
		'resources/finner.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/finner',
	'position' => 'top',
	'targets' => array('desktop')
);

// SpecialSearch hooks
$wgHooks['SetupSearchEngine'][] = array('FinnerHooks::onSetupSearchEngine');
$wgHooks['ShowSearchHit'][] = array('FinnerHooks::onShowSearchHit');
$wgHooks['ShowSearchHitTitle'][] = array('FinnerHooks::onShowSearchHitTitle');
$wgHooks['SpecialSearchCreateLink'][] = array('FinnerHooks::onSpecialSearchCreateLink');
$wgHooks['SpecialSearchGo'][] = array('FinnerHooks::onSpecialSearchGo');
$wgHooks['SpecialSearchNogomatch'][] = array('FinnerHooks::onSpecialSearchNogomatch');
$wgHooks['SpecialSearchNoResults'][] = array('FinnerHooks::onSpecialSearchNoResults');
$wgHooks['SpecialSearchPowerBox'][] = array('FinnerHooks::onSpecialSearchPowerBox');
$wgHooks['SpecialSearchProfileForm'][] = array('FinnerHooks::onSpecialSearchProfileForm');
$wgHooks['SpecialSearchProfiles'][] = array('FinnerHooks::onSpecialSearchProfiles');
$wgHooks['SpecialSearchResults'][] = array('FinnerHooks::onSpecialSearchResults');
$wgHooks['SpecialSearchResultsAppend'][] =
	array('FinnerHooks::onSpecialSearchResultsAppend');
$wgHooks['SpecialSearchResultsPrepend'][] =
	array('FinnerHooks::onSpecialSearchResultsPrepend');
$wgHooks['SpecialSearchSetupEngine'][] =
	array('FinnerHooks::onSpecialSearchSetupEngine');

// SpecialSearch custom hooks
$wgHooks['SpecialSearchAddModules'][] =
	array('FinnerHooks::onSpecialSearchAddModules');
$wgHooks['SpecialSearchPowerBoxOpts'][] =
	array('FinnerHooks::onSpecialSearchPowerBoxOpts');

// CirrusSearch hooks
$wgHooks['CirrusSearchMappingConfig'][] =
	array('FinnerHooks::onCirrusSearchMappingConfig');

// CirrusSearch custom hooks
$wgHooks['CirrusSearchExtraFilters'][] =
	array('FinnerHooks::onCirrusSearchExtraFilters');
$wgHooks['CirrusSearchBuildDocumentFinishBatchExtras'][] =
	array('FinnerHooks::onCirrusSearchBuildDocumentFinishBatchExtras');
$wgHooks['CirrusSearchSelectSort'][] =
	array('FinnerHooks::onCirrusSearchSelectSort');

