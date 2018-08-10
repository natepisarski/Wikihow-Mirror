<?php

define( 'COMMONS_DIR', 'wikihow/common' );

// jQuery extension to prompt the user to download a file
$wgResourceModules['wikihow.common.jquery.download'] = array(
	'scripts' => array(
		'download.jQuery.js',
	),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR
);

$wgResourceModules['wikihow.common.jquery.dateformat'] = array(
	'messages' => array(
		'justnow',
		'minuteago',
		'minutesago',
		'hourago',
		'hoursago',
		'yesterday',
		'daysago',
		'weeksago',
		'yearago',
		'yearsago'
	),
	'scripts' => array(
		'jquery-dateFormat.js',
	),
	'targets' => array( 'desktop', 'mobile' ),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR
);

// http://underscorejs.org/
$wgResourceModules['wikihow.common.underscore'] = array (
	'scripts' => array(
		'underscore.1.7.0.min.js',
	),
	'targets' => array('desktop', 'mobile'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR
);

// AIM: upload files via javascript/REST rather than submitting form then
// changing pages. Not written by wikiHow.
$wgResourceModules['wikihow.common.aim'] = array(
	'scripts' => array(
		'../mobile/webtoolkit.aim.min.js',
	),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR
);

// the browser detection library
// https://github.com/ded/bowser
$wgResourceModules['wikihow.common.bowser'] = array (
	'scripts' => array(
		'bowser.min.js',
	),
	'targets' => array('desktop', 'mobile'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR
);

// the javascript templating library
// https://github.com/janl/mustache.js/
$wgResourceModules['wikihow.common.mustache'] = array (
	'scripts' => array(
		'mustache.js',
	),
	'targets' => array('desktop', 'mobile'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR
);

// for using the backbone mvc framework,
// needs underscore, and mustache
$wgResourceModules['wikihow.common.backbone'] = array (
	'scripts' => array(
		'backbone.1.1.2.js',
	),
	'targets' => array('desktop', 'mobile'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR,
	'dependencies' => array(
		'wikihow.common.underscore',
    'wikihow.common.mustache'
  )
);

// List.js - http://www.listjs.com/
$wgResourceModules['wikihow.common.listjs'] = [
	'scripts' => ['list.min.js'],
	'targets' => ['desktop', 'mobile'],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR,
];

$wgResourceModules['wikihow.common.font-awesome'] = array(
	'targets' => array('desktop', 'mobile'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => COMMONS_DIR,
	'styles' => array(
		'font-awesome-4.2.0/css/font-awesome.min.css'
	)
);


// for using the backbone mvc framework,
// needs underscore, and mustache
$wgResourceModules['wikihow.common.slick'] = array (
	'styles' => array(
		'slick.css',
		'slick-theme.css',
	),
	'scripts' => array(
		'slick.js',
	),
	'position' => 'top',
	'targets' => array('desktop', 'mobile'),
	'localBasePath' => __DIR__ . '/slick-1.5.7/slick',
	'remoteExtPath' => COMMONS_DIR . '/slick-1.5.7/slick',
);

$wgResourceModules['wikihow.common.ace'] = array (
	'scripts' => [
		'src-min-noconflict/ace.js',
		'src-min-noconflict/mode-mysql.js',
		'src-min-noconflict/ext-language_tools.js',
	],
	'position' => 'bottom',
	'targets' => ['desktop'],
	'localBasePath' => __DIR__ . '/ace-1.2.6',
	'remoteExtPath' => COMMONS_DIR . '/ace-1.2.6',
);

$wgResourceModules['wikihow.common.querybuilder'] = array (
	'styles' => [
		'select2/select2.min.css',
		'query-builder/query-builder.css',
		'font-awesome-4.2.0/css/font-awesome.min.css',
		'toastr/toastr.min.css',
	],
	'scripts' => [
		'jquery.extendext.min.js',
		'dot.min.js',
		'moment.min.js',
		'interact.min.js',
		'select2/select2.js',
		'jquery-ui-1.8.full.min.js',
		'toastr/toastr.min.js',
		'query-builder/query-builder.min.js',
		'query-builder/query-builder.select2.js',
		'sql-formatter.min.js',
		'sql-parser.min.js',
	],
	'position' => 'bottom',
	'targets' => ['desktop'],
	'localBasePath' => __DIR__,
	'remoteExtPath' => COMMONS_DIR,
);

$wgResourceModules['wikihow.common.select2'] = array (
	'styles' => ['select2/select2.min.css'],
	'scripts' => ['select2/select2.js'],
	'position' => 'bottom',
	'targets' => ['desktop'],
	'localBasePath' => __DIR__,
	'remoteExtPath' => COMMONS_DIR,
);

$wgResourceModules['wikihow.common.taffy'] = array(
	'scripts' => [ 'taffy.js' ],
	'targets' => [ 'desktop', 'mobile' ],
	'localBasePath' => __DIR__ . '/taffy',
	'remoteExtPath' => COMMONS_DIR . '/taffy'
);

$wgResourceModules['wikihow.router'] = array(
	'scripts' => [ 'Router.js' ],
	'targets' => [ 'desktop', 'mobile' ],
	'localBasePath' => __DIR__ . '/router',
	'remoteExtPath' => COMMONS_DIR . '/router'
);

$wgResourceModules['wikihow.render'] = array(
	'scripts' => [
		'lib/incremental-dom.js',
		'lib/jsonml2idom.js',
		'Render.js'
	],
	'targets' => [ 'desktop', 'mobile' ],
	'localBasePath' => __DIR__ . '/render',
	'remoteExtPath' => COMMONS_DIR . '/render'
);
