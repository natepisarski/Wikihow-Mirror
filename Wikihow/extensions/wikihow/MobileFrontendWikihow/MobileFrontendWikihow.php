<?php
/*
  Place for all our custom and overriding
  variables for the MobileFrontend extension
*/

if ( ! defined( 'MEDIAWIKI' ) )
	die();

$localBasePath = __DIR__;
$remoteExtPath = 'wikihow/MobileFrontendWikihow';


//hook 'em
$wgHooks['BeforePageDisplay'][] = array('MobileFrontendWikiHowHooks::onBeforePageDisplay');
$wgHooks['MinervaPreRender'][] = 'WikihowMobileTools::onMobilePreRender';
$wgHooks['SpecialPage_initList'][] = 'MobileFrontendWikiHowHooks::onSpecialPage_initList';
$wgHooks['MobileToggleView'][] = array('MobileFrontendWikiHowHooks::onMobileToggleView');
$wgHooks['SpecialPageBeforeExecute'][] = array('MobileFrontendWikiHowHooks::onSpecialPageBeforeExecute');
$wgHooks['MobileEndOfPage'][] = array('MobileFrontendWikiHowHooks::onMobileEndOfPage');


/**
 * A boilerplate for the MFResourceLoaderModule that supports templates
 */
$wgMFMobileResourceBoilerplateWikihow = array(
	'localBasePath' => $localBasePath,
	'remoteExtPath' => $remoteExtPath,
	'localTemplateBasePath' => $localBasePath . '/templates',
	'targets' => ['mobile', 'desktop'],
);

/**
 * A boilerplate containing common properties for all RL modules served to mobile site special pages
 */
$wgMFMobileSpecialPageResourceBoilerplateWikihow = array(
	'localBasePath' => $localBasePath,
	'remoteExtPath' => $remoteExtPath,
	'targets' => 'mobile',
	'group' => 'other',
);

/**
 * A boilerplate for RL script modules
 */
$wgMFMobileSpecialPageResourceScriptBoilerplateWikihow = $wgMFMobileSpecialPageResourceBoilerplateWikihow + array(
	'dependencies' => array( 'mobile.stable' ),
);

$wgResourceModules = array_merge( $wgResourceModules, array(
	'mobile.wikihow' => $wgMFMobileResourceBoilerplateWikihow + array(
		'scripts' => array(
			'../tipsandwarnings/toptentips.js',
			'javascripts/wikihow/scripts.js',
			'javascripts/wikihow/scroll_handler.js',
			'../thumbratings/thumbratings.js',
			'../tipsandwarnings/tipsandwarnings.js',
			'../common/jquery.scrollTo/jquery.scrollTo.js',
			'../../../skins/WikiHow/opWHTracker.js',
			'../mobileslideshow/mobileslideshow.js',
		),
		'messages' => array(
			'thanks_to_all_authors',
			'categories',
			'howto',
			'mobile-facebook-login-failed',
			'sources',
			'references',
			'image-attribution'
		),
		'dependencies' => array(
			'ext.wikihow.common_top',
		),
	),
	// Have to add zzz to beginning of module to ensure it loads after other mw modules
	// and properly overrides css without having to add !important with all the rules.
	// A hack, for sure, but has to be done since the OutputPage alphabetically sorts
	// all the modules before building a ss url.  Another alternative, if we want to spend the time,
	// is to pull out specific styles for each mw module we are overriding and inject
	// css into those modules. This approach, of course, is brittle and still will largely
	// be influenced by the sort order
	'zzz.mobile.wikihow.styles_late_load' => $wgMFMobileResourceBoilerplateWikihow + array(
			'styles' => array(
				'less/wikihow/style.css',
				'../thumbratings/thumbratings.css',
			)
	),
	// Split out script from styles so we can use addModuleStyles with the style module, otherwise
	// it gets skipped (and logs an error) because it's not expecting a "general" module
	'zzz.mobile.wikihow.scripts_late_load' => $wgMFMobileResourceBoilerplateWikihow + array(
			'scripts' => array(
				'javascripts/wikihow/late_load_scripts.js',
			)
	),
	'mobile.wikihow.user' => $wgMFMobileResourceBoilerplateWikihow + array(
		'styles' => array(
			'less/wikihow/userpage.css',
		),
		'position' => 'top',
	),
	'mobile.wikihow.userscript' => $wgMFMobileResourceBoilerplateWikihow + array(
		'scripts' => array(
			'javascripts/wikihow/userpage.js',
		),
	),
	// Have to add zzz to beginning of module to ensure it loads after other mw modules
	// and properly overrides css without having to add !important with all the rules.
	// A hack, for sure, but has to be done since the OutputPage alphabetically sorts
	// all the modules before building a ss url.  Another alternative, if we want to spend the time,
	// is to pull out specific styles for each mw module we are overriding and inject
	// css into those modules. This approach, of course, is brittle and still will largely
	// be influenced by the sort order
	'zzz.mobile.wikihow.homepage.styles' => $wgMFMobileResourceBoilerplateWikihow + array(
			'styles' => array(
				'less/wikihow/homepage.less',
				'less/wikihow/related_boxes.css',
				'../homepage/less/hipsterSlider.css',
			),
			'position' => 'top',
	),
	'zzz.mobile.wikihow.homepage.scripts' => $wgMFMobileResourceBoilerplateWikihow + array(
			'scripts' => array(
				'../homepage/javascripts/wikihow/jquery.hipsterSlider.js',
				'../homepage/javascripts/wikihow/homepage.js',
			),
			'position' => 'top',
		),
	// Have to add zzz to beginning of module to ensure it loads after other mw modules
	// and properly overrides css without having to add !important with all the rules.
	// A hack, for sure, but has to be done since the OutputPage alphabetically sorts
	// all the modules before building a ss url.  Another alternative, if we want to spend the time,
	// is to pull out specific styles for each mw module we are overriding and inject
	// css into those modules. This approach, of course, is brittle and still will largely
	// be influenced by the sort order
	'zzz.mobile.wikihow.notifications' => $wgMFMobileResourceBoilerplateWikihow + array(
		'styles' => array(
			'less/wikihow/notifications.css',
		),
		'position' => 'top',
	),
	'mobile.wikihow.loggedout' => $wgMFMobileResourceBoilerplateWikihow + array(
#			'dependencies' => array('mobile.overlays'),
			'scripts' => array(
				'javascripts/modules/loggedout/loggedout.js',
			),
	),
	'mobile.wikihow.loggedout.overlay' => $wgMFMobileResourceBoilerplateWikihow + array(
			'dependencies' => array(
				'mobile.stable',
			),
			'scripts' => array(
				'javascripts/modules/loggedout/LoggedOutOverlay.js',
			),
			'styles' => array(
				'less/modules/LoggedOutOverlay.less',
			),
			'templates' => array(
				'modules/loggedout/LoggedOutOverlay',
			),
			'messages' => array(
				'loggedout-overlay-heading',
			),
		),
	'mobile.wikihow.notifications.overlay' => $wgMFMobileResourceBoilerplateWikihow + array(
			'styles' => array(
				'less/modules/NotificationsOverlayWikihow.less',
			),
			'position' => 'top',
			'dependencies' => array('mobile.notifications.overlay'),
	),
	'zzz.mobile.wikihow.sample' => $wgMFMobileResourceBoilerplateWikihow + array(
		'styles' => array(
			'../docviewer/docviewer_m.css',
		),
	),
	// Have to add zzz to beginning of module to ensure it loads after other mw modules
	// and properly overrides css without having to add !important with all the rules.
	// A hack, for sure, but has to be done since the OutputPage alphabetically sorts
	// all the modules before building a ss url.  Another alternative, if we want to spend the time,
	// is to pull out specific styles for each mw module we are overriding and inject
	// css into those modules. This approach, of course, is brittle and still will largely
	// be influenced by the sort order
	'zzz.mobile.wikihow.login.styles' => $wgMFMobileSpecialPageResourceBoilerplateWikihow + array(
		'styles' => array(
			'less/wikihow/login.css',
		),
		'position' => 'bottom',
	),
	'mobile.wikihow.login' => $wgMFMobileResourceBoilerplateWikihow + array(
		'scripts' => array(
			'javascripts/wikihow/login_mobile.js'
		),
		'position' => 'bottom',
		'dependencies' => [ 'ext.wikihow.socialauth' ],
	),
	'mobile.wikihow.whCtaDrawer' => $wgMFMobileResourceBoilerplateWikihow + [
		'scripts' => 'javascripts/modules/whCtaDrawer/WhCtaDrawer.js',
		'styles' => 'less/modules/whCtaDrawer/whCtaDrawer.less',
		'dependencies' => [ 'ext.wikihow.socialauth', 'mobile.stable' ],
		//'templates' => 'modules/whCtaDrawer/whCtaDrawer', // this was throwing an exception
		'messages' => [
			'mobile-cta-drawer-log-in-google',
			'mobile-cta-drawer-log-in-facebook'
		]
	],
	// Have to add zzz to beginning of module to ensure it loads after other mw modules
	// and properly overrides css without having to add !important with all the rules.
	// A hack, for sure, but has to be done since the OutputPage alphabetically sorts
	// all the modules before building a ss url.  Another alternative, if we want to spend the time,
	// is to pull out specific styles for each mw module we are overriding and inject
	// css into those modules. This approach, of course, is brittle and still will largely
	// be influenced by the sort order
	'zzz.mobile.wikihow.passwordreset' => $wgMFMobileResourceBoilerplateWikihow + array(
			'styles' => array(
				'less/wikihow/passwordreset.less',
			),
			'position' => 'top',
	),
	'mobile.wikihow.stable.styles' => $wgMFMobileResourceBoilerplateWikihow + array(
			'styles' => array(
				'less/modules/tutorialswikihow.less',
			),
			'position' => 'top',
#			'dependencies' => array('mobile.stable.styles'),
		),
));

// Extend the core configuration found in extensions/MobileFrontend/includes/Resources.php
#$wgResourceModules['mobile.editor']['dependencies'][] = 'mobile.wikihow.whCtaDrawer';

// Add a few styles to MobileFrontend-specific modules. We do this rather than create our own since they are common/core
// modules and would require modifying other MobileFrontend modules to reference our new module
#$wgResourceModules['mobile.overlays']['styles'][] = '../wikihow/MobileFrontendWikihow/less/common/OverlayNew.less';
// NOTE: Reuben, 2019: we CANNOT remove these next 3 statements without doing other work. Fixing any exceptions
// caused by these next statements will cause MFE to import modules such as "collapsible", which will hide our
// == Steps == section on all mobile articles. This is horrible and needs to be debugged, and the offending
// modules removed. But these errors need to stay in place until then.
$wgResourceModules['skins.minerva.buttons.styles']['styles'][] = '../wikihow/MobileFrontendWikihow/less/common/buttons.less';
$wgResourceModules['mobile.styles']['styles'][] = '../wikihow/MobileFrontendWikihow/less/common/buttons.less';
$wgResourceModules['skins.minerva.content.styles']['styles'][] = '../wikihow/MobileFrontendWikihow/less/common/common.less';
#$wgResourceModules['mobile.startup']['templates'][] = '../../wikihow/MobileFrontendWikihow/templates/pagewh';

$cwd = $localBasePath;

//custom messages
$wgExtensionMessagesFiles['MobileFrontendWikihow'] = $localBasePath. '/MobileFrontendWikihow.i18n.php';

// autoload extension classes
$autoloadClasses = array (
	'MinervaTemplateWikihow' => 'includes/skins/MinervaTemplateWikihow.php',
	'SkinMinervaWikihow' => 'includes/skins/SkinMinervaWikihow.php',
	'SkinMinervaWikihowAmp' => '../googleamp/SkinMinervaWikihowAmp.php',
	'MobileFrontendWikiHowHooks' => 'MobileFrontendWikihow.hooks.php',
	'UserLoginAndCreateTemplate'=> 'includes/skins/wh_UserLoginAndCreateTemplate.php',
	'UserLoginMobileTemplate' => 'includes/skins/wh_UserLoginMobileTemplate.php',
	'UserAccountCreateMobileTemplate' => 'includes/skins/wh_UserAccountCreateMobileTemplate.php',
	'SpecialMobileNotifications' => 'includes/specials/wh_SpecialMobileNotifications.php',
	'SpecialPasswordReset' => 'includes/specials/SpecialPasswordResetWikihow.php',
	'SpecialMobileLoggedOutComplete' => 'includes/specials/SpecialMobileLoggedOutComplete.php',
	'ApiMobileViewWikihow' => 'includes/api/ApiMobileViewWikihow.php',
	'SkinMinervaQADomain' => '../qadomain/SkinMinervaQADomain.php',
	'MinervaTemplateQADomain' => '../qadomain/MinervaTemplateQADomain.php',
);

foreach ( $autoloadClasses as $className => $classFilename ) {
	$wgAutoloadClasses[$className] = "$cwd/$classFilename";
}

$wgAPIModules['mobileviewwikihow'] = 'ApiMobileViewWikihow';

/*
 * Override default values in MobileFrontend.php
 */

$wgMFDefaultSkinClass = 'SkinMinervaWikihow';
if ( @$_GET["amp"] == 1 ) {
	// NOTE: we use this AMP skin for some Android (wh_an=1) requests, such
	// as to article pages, as well.
	$wgMFDefaultSkinClass = 'SkinMinervaWikihowAmp';
}
if (class_exists("QADomain") && QADomain::isQADomain()) {
	$wgMFDefaultSkinClass = 'SkinMinervaQADomain';
}
$wgMFNoindexPages = false;

$wgMobileFrontendFormatCookieExpiry = 86400; //1 day
