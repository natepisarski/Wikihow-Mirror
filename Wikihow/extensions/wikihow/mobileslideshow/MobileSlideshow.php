<?php

$wgAutoloadClasses['MobileSlideshow'] = dirname(__FILE__) . '/MobileSlideshow.class.php';

$wgResourceModules['mobile.wikihow.mobileslideshow'] = array(
	'scripts' => array(
		'../common/PhotoSwipe/photoswipe.js',
		'photoswipe_wh.js'
	),
	'styles' => array(
		'../common/PhotoSwipe/photoswipe.css',
		'mobileslideshow.css'
	),
	'localBasePath' => __DIR__ ,
	'remoteExtPath' => 'wikihow/mobileslideshow',
	'position' => 'bottom',
	'targets' => array('mobile', 'desktop'),
	'messages' => array(
		'Aria_step_n'
	),
	'dependencies' => array(
		'mediawiki.jqueryMsg',
	),
);