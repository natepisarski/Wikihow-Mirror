<?php
/**
 * This extension allows pages to add a popup guided tour to help new users
 * It is partly based on the Guiders JavaScript library, originally developed by Optimizely.
 *
 * There have also been further changes to Guiders in conjunction with this extension.
 *
 * @file
 * @author Terry Chay tchay@wikimedia.org
 * @author Matthew Flaschen mflaschen@wikimedia.org
 * @author Luke Welling lwelling@wikimedia.org
 *
 */

/**
 * Prevent a user from accessing this file directly and provide a helpful
 * message explaining how to install this extension.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install the Guided Tour extension, put the following line in your
LocalSettings.php file:
require_once( "\$IP/extensions/GuidedTour/GuidedTour.php" );
EOT;
	exit( 1 );
}

// Find the full directory path of this extension
$dir = __DIR__ . DIRECTORY_SEPARATOR;
$wgAutoloadClasses += array(
	'GuidedTourHooks' => $dir . 'GuidedTourHooks.php',
	'ResourceLoaderGuidedTourSiteStylesModule' =>
	$dir . 'includes/ResourceLoaderGuidedTourSiteStylesModule.php',
);

$wgHooks['BeforeWelcomeCreation'][] = array('GuidedTourHooks::onBeforeWelcomeCreation');
$wgHooks['BeforePageDisplay'][] = 'GuidedTourHooks::onBeforePageDisplay';
$wgHooks['MakeGlobalVariablesScript'][] = 'GuidedTourHooks::onMakeGlobalVariablesScript';
$wgHooks['ResourceLoaderTestModules'][] = 'GuidedTourHooks::onResourceLoaderTestModules';
$wgHooks['UnitTestsList'][] = 'GuidedTourHooks::onUnitTestsList';
$wgHooks['RedirectSpecialArticleRedirectParams'][] = 'GuidedTourHooks::onRedirectSpecialArticleRedirectParams';

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'GuidedTour',
	'author' => array('Terry Chay', 'Matthew Flaschen', 'Luke Welling',),
	'url' => 'https://www.mediawiki.org/wiki/Extension:GuidedTour',
	'descriptionmsg' => 'guidedtour-desc',
	'version'  => 1.0,
);

$guidersPath = 'modules/mediawiki.libs.guiders';

// Modules
$wgResourceModules['schema.GuidedTour'] = array(
	'class' => 'ResourceLoaderSchemaModule',
	'schema' => 'GuidedTour',
	'revision' => 5222838,
);

$wgResourceModules['mediawiki.libs.guiders'] = array(
	'styles' => 'mediawiki.libs.guiders.less',
	'scripts' => array(
		'mediawiki.libs.guiders.js',
	),
	'localBasePath' => $dir . $guidersPath,
	'remoteExtPath' => "GuidedTour/$guidersPath",
);

// TODO (mattflaschen, 2013-07-30): When the location of the rendering code
// is decided, this module can be merged to there.
$wgResourceModules['ext.guidedTour.styles'] = array(
	'styles' => array('ext.guidedTour.less', 'ext.guidedTour.wh.css'),
	'localBasePath' => $dir . 'modules',
	'remoteExtPath' => 'GuidedTour/modules',
	'dependencies' => array(
		'mediawiki.libs.guiders',
		// Ideally 'mediawiki.ui.button' should be added with addModuleStyles to
		// avoid duplication.
		// However, that wouldn't work if a tour is loaded dynamically on the client-side.
		'mediawiki.ui.button',
	),
);

// Depends on ext.guidedTour.styles
$wgResourceModules['ext.guidedTour.siteStyles'] = array(
	'class' => 'ResourceLoaderGuidedTourSiteStylesModule',
);

// Internal API (refactoring to here in progress)
$wgResourceModules['ext.guidedTour.lib.internal'] = array(
	'scripts' => 'ext.guidedTour.lib.internal.js',
	'localBasePath' => $dir . 'modules',
	'remoteExtPath' => 'GuidedTour/modules',
);

// Public API, and legacy parts of the internal API pending move
$wgResourceModules['ext.guidedTour.lib'] = array(
	'scripts' => 'ext.guidedTour.lib.js',
	'localBasePath' => $dir . 'modules',
	'remoteExtPath' => 'GuidedTour/modules',
	'dependencies' => array(
		'jquery.cookie',
		'jquery.json',
		'mediawiki.jqueryMsg',
		'mediawiki.libs.guiders',
		'mediawiki.util',
		'schema.GuidedTour',
		'ext.guidedTour.lib.internal',
		'ext.guidedTour.siteStyles',
	),
	'messages' => array(
		'guidedtour-next-button',
		'guidedtour-okay-button',
	),
);

// This calls code in guidedTour.lib to attempt to launch a tour, based on the environment
// (currently query string and a cookie)
$wgResourceModules['ext.guidedTour'] = array(
	'scripts' => 'ext.guidedTour.js',
	'localBasePath' => $dir . 'modules',
	'remoteExtPath' => 'GuidedTour/modules',
	'dependencies' => 'ext.guidedTour.lib',
);


// Tour modules
// wikiHow Community Dashboard tour
$wgResourceModules['ext.guidedTour.tour.dashboard'] = array(
	'scripts' => 'dashboard.js',
	'localBasePath' => $dir . 'modules/tours',
	'remoteExtPath' => 'GuidedTour/modules/tours',
	'dependencies' => 'ext.guidedTour',
	'messages' => array(
		'guidedtour-tour-dashboard-initial',
		'guidedtour-tour-dashboard-description',
		'guidedtour-tour-dashboard-tipspatrol-title',
		'guidedtour-tour-dashboard-tipspatrol-description',
		'guidedtour-tour-dashboard-rc-title',
		'guidedtour-tour-dashboard-rc-description',
		'guidedtour-tour-dashboard-spelling-title',
		'guidedtour-tour-dashboard-spelling-description',
		'guidedtour-tour-dashboard-answerrequests-title',
		'guidedtour-tour-dashboard-answerrequests-description',
		'guidedtour-tour-dashboard-end-title',
		'guidedtour-tour-dashboard-end-description',
		'guidedtour-tour-dashboard-answerquestions-title',
		'guidedtour-tour-dashboard-answerquestions-description',
		'guidedtour-tour-dashboard-editbytopic-title',
		'guidedtour-tour-dashboard-editbytopic-description'
	),
);

// wikiHow firstedit
$wgResourceModules['ext.guidedTour.tour.fe'] = array(
	'scripts' => 'fe.js',
	'localBasePath' => $dir . 'modules/tours',
	'remoteExtPath' => 'GuidedTour/modules/tours',
	'dependencies' => 'ext.guidedTour',
	'messages' => array(
		'guidedtour-tour-fe-initial-title',
		'guidedtour-tour-fe-initial-description',
		'guidedtour-tour-fe-editing-title',
		'guidedtour-tour-fe-editing-description',
		'guidedtour-tour-fe-preview-title',
		'guidedtour-tour-fe-preview-description',
		'guidedtour-tour-fe-summary-title',
		'guidedtour-tour-fe-summary-description',
		'guidedtour-tour-fe-save-title',
		'guidedtour-tour-fe-save-description',
		'guidedtour-tour-fe-end-title',
		'guidedtour-tour-fe-end-description',
	),
);

// RC Patrol
$wgResourceModules['ext.guidedTour.tour.rc'] = array(
	'scripts' => 'rc.js',
	'localBasePath' => $dir . 'modules/tours',
	'remoteExtPath' => 'GuidedTour/modules/tours',
	'dependencies' => 'ext.guidedTour',
	'messages' => array(
		'guidedtour-tour-rc-initial-title',
		'guidedtour-tour-rc-initial-description',
		'guidedtour-tour-rc-review-title',
		'guidedtour-tour-rc-review-description',
		'guidedtour-tour-rc-rollback-title',
		'guidedtour-tour-rc-rollback-description',
		'guidedtour-tour-rc-patrolled-first-title',
		'guidedtour-tour-rc-patrolled-first-description',
		'guidedtour-tour-rc-patrolled-title',
		'guidedtour-tour-rc-patrolled-description',
		'guidedtour-tour-rc-talk-first-title',
		'guidedtour-tour-rc-talk-first-description',
		'guidedtour-tour-rc-talk-title',
		'guidedtour-tour-rc-talk-description',
		'guidedtour-tour-rc-quickedit-first-title',
		'guidedtour-tour-rc-quickedit-first-description',
		'guidedtour-tour-rc-quickedit-title',
		'guidedtour-tour-rc-quickedit-description',
		'guidedtour-tour-rc-driveby-first-title',
		'guidedtour-tour-rc-driveby-first-description',
		'guidedtour-tour-rc-driveby-title',
		'guidedtour-tour-rc-driveby-description',
		'guidedtour-tour-rc-end-title',
		'guidedtour-tour-rc-end-description',
	),
);

// Talk Page
$wgResourceModules['ext.guidedTour.tour.talk'] = array(
	'scripts' => 'talk.js',
	'localBasePath' => $dir . 'modules/tours',
	'remoteExtPath' => 'GuidedTour/modules/tours',
	'dependencies' => 'ext.guidedTour',
	'messages' => array(
		'guidedtour-tour-talk-initial-title',
		'guidedtour-tour-talk-initial-description',
		'guidedtour-tour-talk-reply-title',
		'guidedtour-tour-talk-reply-description',
	),
);


// First edit to main namespace; for when VisualEditor is unavailable or disabled
$wgResourceModules['ext.guidedTour.tour.firstedit'] = array(
	'scripts' => 'firstedit.js',
	'localBasePath' => $dir . 'modules/tours',
	'remoteExtPath' => 'GuidedTour/modules/tours',
	'dependencies' => 'ext.guidedTour',
	'messages' => array(
		'editsection',
		'savearticle',
		'showpreview',
		'vector-view-edit',
		'guidedtour-tour-firstedit-edit-page-title',
		'guidedtour-tour-firstedit-edit-page-description',
		'guidedtour-tour-firstedit-edit-section-title',
		'guidedtour-tour-firstedit-edit-section-description',
		'guidedtour-tour-firstedit-preview-title',
		'guidedtour-tour-firstedit-preview-description',
		'guidedtour-tour-firstedit-save-title',
		'guidedtour-tour-firstedit-save-description',
	),
);

// First edit to main namespace; for when VisualEditor is available and enabled
$wgResourceModules['ext.guidedTour.tour.firsteditve'] = array(
	'scripts' => 'firsteditve.js',
	'localBasePath' => $dir . 'modules/tours',
	'remoteExtPath' => 'GuidedTour/modules/tours',
	'dependencies' => 'ext.guidedTour',
	'messages' => array(
		'editsection',
		'vector-view-edit',
		'visualeditor-beta-appendix',
		'visualeditor-toolbar-savedialog',
		'guidedtour-tour-firstedit-edit-page-title',
		'guidedtour-tour-firsteditve-edit-page-description',
		'guidedtour-tour-firstedit-edit-section-title',
		'guidedtour-tour-firsteditve-edit-section-description',
		'guidedtour-tour-firstedit-save-title',
		'guidedtour-tour-firsteditve-save-description',
	),
);

// Test tour as demonstration
$wgResourceModules['ext.guidedTour.tour.test'] = array(
	'scripts' => 'test.js',
	'localBasePath' => $dir . 'modules/tours',
	'remoteExtPath' => 'GuidedTour/modules/tours',
	'dependencies' => 'ext.guidedTour',
	'messages' => array(
		'portal',
		'guidedtour-help-url',
		'guidedtour-tour-test-testing',
		'guidedtour-tour-test-test-description',
		'guidedtour-tour-test-callouts',
		'guidedtour-tour-test-portal-description',
		'guidedtour-tour-test-mediawiki-parse',
		'guidedtour-tour-test-description-page',
		'guidedtour-tour-test-go-description-page',
		'guidedtour-tour-test-launch-tour',
		'guidedtour-tour-test-launch-tour-description',
		'guidedtour-tour-test-launch-using-tours',
	),
);

// Messages
$wgExtensionMessagesFiles += array(
	'GuidedTour' => $dir . 'GuidedTour.i18n.php',
);
