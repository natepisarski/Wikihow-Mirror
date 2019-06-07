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

$wgResourceModules['ext.wikihow.common_bottom'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'scripts' => [
		'../../skins/WikiHow/MachinifyAPI.js',
		'wikihow_common_bottom.js',
	],
	'styles' => [ 'wikihow_common_bottom.css' ],
	'dependencies' => [
		'ext.wikihow.common_top',
		'wikihow.common.mustache',
		'mediawiki.jqueryMsg'
	],
	'remoteExtPath' => 'wikihow',
	'position' => 'bottom',
	'messages' => [
		'mhmt-question',
		'mhmt-cheer',
		'mhmt-oops',
		'mhmt-helped',
		'mhmt-thanks'
	]
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

// NOTE: Should the CSS of whwid be loaded higher?
$wgResourceModules['ext.wikihow.whvid'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'styles' => [ 'whvid/whvid.less' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top' ];


$wgResourceModulesDesktopBoiler = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top'];

$wgResourceModules['ext.wikihow.rcwidget'] =
	$wgResourceModulesDesktopBoiler + [
		// JS doesn't seem to work when css is included
		//'styles' => [ 'rcwidget/rcwidget.css' ],
		'scripts' => [ 'rcwidget/rcwidget.js' ]
	];

$wgResourceModules['ext.wikihow.toptentips'] =
	$wgResourceModulesDesktopBoiler + [
		'styles' => [ 'tipsandwarnings/topten.css' ],
		'scripts' => [ 'tipsandwarnings/toptentips.js' ]
	];

$wgResourceModules['ext.wikihow.homepage'] =
	$wgResourceModulesDesktopBoiler + [
		'styles' => [ '../../skins/owl/home.css' ],
		'scripts' => [ 'homepage/wikihowhomepage.js' ]
	];

$wgResourceModules['ext.wikihow.image_feedback'] =
	$wgResourceModulesDesktopBoiler + [
		'styles' => [ 'imagefeedback/imagefeedback.css' ],
		'scripts' => [ 'imagefeedback/imagefeedback.js' ]
	];

$wgResourceModules['ext.wikihow.editor_script'] =
	$wgResourceModulesDesktopBoiler
	+ [ 'scripts' => [ '../../skins/common/editor_script.js' ] ];

$wgResourceModules['ext.wikihow.notindexed_styles'] =
	$wgResourceModulesDesktopBoiler
	+ [ 'styles' => [ '../../skins/owl/noindex.css' ] ];

$wgResourceModules['ext.wikihow.specials_styles'] =
	$wgResourceModulesDesktopBoiler
	+ [ 'styles' => [ '../../skins/owl/special.css' ] ];

$wgResourceModules['ext.wikihow.nonarticle_styles'] =
	$wgResourceModulesDesktopBoiler
	+ [ 'styles' => [ '../../skins/owl/nonarticle.css' ] ];

$wgResourceModules['ext.wikihow.diff_styles'] =
	$wgResourceModulesDesktopBoiler
	+ [ 'styles' => [ '../../skins/common/diff.css' ] ];

$wgResourceModules['ext.wikihow.loggedin_styles'] =
	$wgResourceModulesDesktopBoiler + [
		'styles' => [
			'../../skins/WikiHow/loggedin.css',
			'../../skins/owl/loggedin.css'
		]
	];

$wgResourceModules['ext.wikihow.thumbsup'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'scripts' => [ 'thumbsup/thumbsup.js' ],
	'dependencies' => [ 'ext.wikihow.desktop_base' ],
	'messages' => [ 'rcpatrol_thumb_msg_pending', 'rcpatrol_thumb_msg_complete' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'bottom' ];

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
