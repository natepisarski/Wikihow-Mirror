<?php

$wgAutoloadClasses['AndroidHelper'] = dirname(__FILE__) . '/AndroidHelper.class.php';

$wgResourceModules['ext.wikihow.android_helper'] = array(
	'scripts' => array(
		'android_helper.js'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/android_helper',
	'position' => 'top',
	'targets' => array('desktop', 'mobile'),
	'dependencies' => array('mediawiki.page.ready')
);

$wgResourceModules['ext.wikihow.android_helper_ajax'] = array(
	'scripts' => array(
		'android_helper_ajax.js'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/android_helper',
	'position' => 'top',
	'targets' => array('mobile', 'desktop'),
);

$wgHooks['TitleSquidURLs'][] = array('AndroidHelper::onTitleSquidURLsPurgeVariants');
$wgHooks['ResourceLoaderGetStartupModules'][] = array('AndroidHelper::onResourceLoaderGetStartupModules');