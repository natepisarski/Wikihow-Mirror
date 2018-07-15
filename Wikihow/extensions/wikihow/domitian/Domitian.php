<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgAutoloadClasses['DomitianDB'] = dirname(__FILE__) . '/DomitianDB.class.php';

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Domitian Summary',
	'namemsg' => 'domitiansummary',
	'description' => 'A tool to collect and aggregate usage log data',
	'descriptionmsg' => 'domitiandescription',
	'version' => 1,
	'author' => 'George Bahij',
	'url' => 'http://www.wikihow.com/Special:DomitianSummary'
);

$wgSpecialPages['DomitianSummary'] = 'DomitianSummary';
$wgAutoloadClasses['DomitianSummary'] = dirname(__FILE__) . '/DomitianSummary.body.php';

$wgResourceModules['ext.wikihow.domitian.Summary'] = array(
	'scripts' => array(
		'resources/domitian.js',
		'resources/domitian_summary.js'
	),
	'styles' => array(
		'resources/domitian.css',
		'resources/domitian_summary.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/domitian',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'mediawiki.page.ready',
		'wikihow.common.jquery.download'
	)
);

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Domitian Details',
	'namemsg' => 'domitiandetails',
	'description' => 'A tool to collect and aggregate usage log data',
	'descriptionmsg' => 'domitiandescription',
	'version' => 1,
	'author' => 'George Bahij',
	'url' => 'http://www.wikihow.com/Special:DomitianDetails'
);

$wgSpecialPages['DomitianDetails'] = 'DomitianDetails';
$wgAutoloadClasses['DomitianDetails'] = dirname(__FILE__) . '/DomitianDetails.body.php';

$wgResourceModules['ext.wikihow.domitian.Details'] = array(
	'scripts' => array(
		'resources/domitian.js',
		'resources/domitian_details.js'
	),
	'styles' => array(
		'resources/domitian.css',
		'resources/domitian_details.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/domitian',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'mediawiki.page.ready',
		'wikihow.common.jquery.download'
	)
);

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Domitian Segments',
	'namemsg' => 'domitiansegments',
	'description' => 'A tool to collect and aggregate usage log data',
	'descriptionmsg' => 'domitiandescription',
	'version' => 1,
	'author' => 'George Bahij',
	'url' => 'http://www.wikihow.com/Special:DomitianSegments'
);

$wgSpecialPages['DomitianSegments'] = 'DomitianSegments';
$wgAutoloadClasses['DomitianSegments'] = dirname(__FILE__) . '/DomitianSegments.body.php';

$wgResourceModules['ext.wikihow.domitian.Segments'] = array(
	'scripts' => array(
		'resources/domitian.js',
		'resources/domitian_segments.js'
	),
	'styles' => array(
		'resources/domitian.css',
		'resources/domitian_segments.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/domitian',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'mediawiki.page.ready',
		'wikihow.common.jquery.download'
	)
);

