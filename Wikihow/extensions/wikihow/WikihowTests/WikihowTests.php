<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/************
 * Tool to run js/less based tests on the site.
 * Each test will need it own resource module. That module should be put into the wikihowtests group to allow it to have shorter caching times.
 * To start the test, modify to the wikihowtests.js file to set the times when the test should be run.
 * Be sure to delete code out of wikihowtests.js each time a test is done.
 * Additionally, articles need to be in the wikihow_tests config list to be eligible to run tests
 **********/

$wgResourceModules['ext.wikihow.wikihowtests'] = [
	'scripts' => [ 'wikihowtests.js' ],
	'localBasePath' => __DIR__ . "/resources",
	'remoteExtPath' => 'wikihow/WikihowTests/resources',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom',
];

$wgResourceModules['ext.wikihow.wikihowtests.expertqanda'] = [
	'scripts' => [ 'qaexperttest.js' ],
	'styles' => [ 'qaexperttest.less' ],
	'localBasePath' => __DIR__ . "/resources",
	'remoteExtPath' => 'wikihow/WikihowTests/resources',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom',
	'group' => 'wikihowtests'
];

$wgHooks['BeforePageDisplay'][] = 'onBeforePageDisplayWikihowTests';

function onBeforePageDisplayWikihowTests(OutputPage &$out, Skin &$skin ) {
	if(ArticleTagList::hasTag("wikihow_tests", $out->getTitle()->getArticleID()) && !$out->getUser()->isLoggedIn()) {
		$out->addModules('ext.wikihow.wikihowtests');
	}
}
