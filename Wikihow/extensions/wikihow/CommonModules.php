<?php

if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['CommonModules'] = __DIR__ . '/CommonModules.body.php';
$wgHooks['BeforePageDisplay'][] = 'CommonModules::onBeforePageDisplay';

$wgResourceModules['ext.wikihow.common_top'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'scripts' => [
		'wikihow_common_top.js',
		'stu/scroll_handler.js',
		// note: stu will be loaded in same spot as defer images now (higher up, embedded in the page)
	],
	'dependencies' => ['mediawiki.user'],
	'remoteExtPath' => 'wikihow',
	'position' => 'top'
];

if ($wgLanguageCode == 'zh' && $wgIsProduction) {
	$wgResourceModules['ext.wikihow.common_top']['scripts'][] = 'baidu.js';
}

$wgResourceModules['ext.wikihow.common_bottom_styles'] = [
	'styles' => [ 'wikihow_common_bottom.css' ],
	'targets' => [ 'desktop', 'mobile' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow',
];

$wgResourceModules['ext.wikihow.common_bottom'] = [
	'scripts' => [
		'../../skins/WikiHow/MachinifyAPI.js',
		'wikihow_common_bottom.js',
	],
	'targets' => [ 'desktop', 'mobile' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow',
	'dependencies' => [
		'ext.wikihow.common_top',
		'wikihow.common.mustache',
		'mediawiki.jqueryMsg'
	],
	'position' => 'bottom',
	'messages' => [
		'mhmt-question',
		'mhmt-cheer',
		'mhmt-oops',
		'mhmt-helped',
		'mhmt-thanks'
	]
];

$wgResourceModules['ext.wikihow.responsive_base'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'scripts' => [
		'../../skins/common/wikihowbits.js',
		'common/jquery.scrollTo.1.4.12/jquery.scrollTo.min.js',
		'../../skins/WikiHow/google_cse_search_box.js',
		'../../skins/WikiHow/gaWHTracker.js',
		'../../skins/WikiHow/opWHTracker.js',
	],
	'dependencies' => [
		'ext.wikihow.common_top',
		'ext.wikihow.loginreminder',
		'ext.wikihow.socialauth',
		'ext.wikihow.userloginbox'
	],
	'remoteExtPath' => 'wikihow',
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.desktop_base'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'scripts' => [
		'../../skins/common/wikihowbits.js',
		'common/jquery.scrollTo.1.4.12/jquery.scrollTo.min.js',
		'../../skins/WikiHow/google_cse_search_box.js',
		'../../skins/WikiHow/gaWHTracker.js',
		'../../skins/WikiHow/opWHTracker.js',
	],
	'dependencies' => [
		'ext.wikihow.common_top',
		'ext.wikihow.loginreminder',
		'ext.wikihow.socialauth',
	],
	'remoteExtPath' => 'wikihow',
	'position' => 'top' ];

// NOTE: Should the CSS of whvid be loaded higher?
$wgResourceModules['ext.wikihow.whvid'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'styles' => [ 'whvid/whvid.less' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top' ];


$wgResourceModulesDesktopBoilerStyles = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'remoteExtPath' => 'wikihow'
];

$wgResourceModulesDesktopBoiler = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top'
];

$wgResourceModulesResponsiveBoilerStyles = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'remoteExtPath' => 'wikihow'
];

$wgResourceModulesResponsiveBoiler = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [ 'ext.wikihow.responsive_base' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.rcwidget'] =
	$wgResourceModulesResponsiveBoiler
	+ [ 'scripts' => [ 'rcwidget/rcwidget.js' ] ];

$wgResourceModules['ext.wikihow.rcwidget_styles'] =
	$wgResourceModulesResponsiveBoilerStyles
	+ [ 'styles' => [ 'rcwidget/rcwidget.css' ] ];

$wgResourceModules['ext.wikihow.toptentips_styles'] =
	$wgResourceModulesDesktopBoilerStyles
	+ [ 'styles' => [ 'tipsandwarnings/topten.css' ] ];

$wgResourceModules['ext.wikihow.toptentips'] =
	$wgResourceModulesDesktopBoiler
	+ [ 'scripts' => [ 'tipsandwarnings/toptentips.js' ] ];

$wgResourceModules['ext.wikihow.homepage_styles'] =
	$wgResourceModulesDesktopBoilerStyles
	+ [ 'styles' => [ '../../skins/owl/home.css' ] ];

$wgResourceModules['ext.wikihow.homepage'] =
	$wgResourceModulesDesktopBoiler
	+ [ 'scripts' => [ 'homepage/wikihowhomepage.js' ] ];

$wgResourceModules['ext.wikihow.image_feedback_styles'] =
	$wgResourceModulesResponsiveBoilerStyles
	+ [ 'styles' => [ 'imagefeedback/imagefeedback.css' ] ];

$wgResourceModules['ext.wikihow.image_feedback'] =
	$wgResourceModulesResponsiveBoiler
	+ [ 'scripts' => [ 'imagefeedback/imagefeedback.js' ] ];

$wgResourceModules['ext.wikihow.editor_script'] =
	$wgResourceModulesResponsiveBoiler
	+ [ 'scripts' => [ '../../skins/common/editor_script.js' ] ];

$wgResourceModules['ext.wikihow.notindexed_styles'] =
	$wgResourceModulesDesktopBoilerStyles
	+ [ 'styles' => [ '../../skins/owl/noindex.css' ] ];

$wgResourceModules['ext.wikihow.specials_styles'] =
	$wgResourceModulesDesktopBoilerStyles
	+ [ 'styles' => [ '../../skins/owl/special.css' ] ];

$wgResourceModules['ext.wikihow.nonarticle_styles'] =
	$wgResourceModulesResponsiveBoilerStyles
	+ [ 'styles' => [ '../../skins/owl/nonarticle.css' ] ];

$wgResourceModules['ext.wikihow.diff_styles'] =
	$wgResourceModulesResponsiveBoilerStyles
	+ [ 'styles' => [ '../../skins/common/diff.css' ] ];

$wgResourceModules['ext.wikihow.loggedin_styles'] =
	$wgResourceModulesDesktopBoilerStyles + [
		'styles' => [
			'../../skins/WikiHow/loggedin.css',
			'../../skins/owl/loggedin.css'
		],
		'group' => 'prio2', // Load these styles after main CSS bundle
	];

// Styles for the printable version of our site, restricted to @media type 'print'.
$wgResourceModules['ext.wikihow.printable'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'styles' => [ '../../skins/owl/printable_media_print.less' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top' ];

$wgResourceModules['ext.wikihow.magnificpopup'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'scripts' => ['common/magnific/magnific.popup.1.1.0.min.js'],
	'styles' => ['common/magnific/magnific.1.1.0.css'],
	'remoteExtPath' => 'wikihow',
	'position' => 'top' ];
