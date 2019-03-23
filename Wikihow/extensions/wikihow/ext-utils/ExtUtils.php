<?php
/*
 * File to include js-related modules primarily used for wikihow extensions
 */

$wgResourceModules['wikihow.common.string_validator'] = array(
	'scripts' => array('string_validator.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/ext-utils',
	'position' => 'bottom',
	'messages' => [],
	'targets' => ['mobile', 'desktop'],
	'dependencies' => [],
);

$wgResourceModules['wikihow.common.pub_sub'] = array(
	'scripts' => array('pub_sub.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/ext-utils',
	'position' => 'bottom',
	'messages' => [],
	'targets' => ['mobile', 'desktop'],
	'dependencies' => ['jquery'],
);

$wgResourceModules['wikihow.common.thumbs_up_down'] = array(
	'scripts' => array('thumbs_up_down.js'),
	'styles' => array('thumbs_up_down_desktop.less'),
	'localBasePath' => __DIR__ . '/thumbs_up_down',
	'remoteExtPath' => 'wikihow/ext-utils/thumbs_up_down',
	'position' => 'bottom',
	'messages' => [],
	'targets' => ['mobile', 'desktop'],
	'dependencies' => ['wikihow.common.pub_sub'],
);

$wgResourceModules['mobile.wikihow.common.thumbs_up_down'] = array(
	'scripts' => array('thumbs_up_down.js'),
	'styles' => array('thumbs_up_down_mobile.less'),
	'localBasePath' => __DIR__ . '/thumbs_up_down',
	'remoteExtPath' => 'wikihow/ext-utils/thumbs_up_down',
	'position' => 'bottom',
	'messages' => [],
	'targets' => ['mobile', 'desktop'],
	'dependencies' => ['wikihow.common.pub_sub'],
);
