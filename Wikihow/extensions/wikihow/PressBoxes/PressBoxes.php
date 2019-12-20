<?php

$wgAutoloadClasses['PressBoxes'] = __DIR__ . '/PressBoxes.class.php';
$wgMessagesDirs['PressBoxes'] = __DIR__ . '/i18n/';

$wgResourceModules['ext.wikihow.press_boxes'] = [
	'styles' => [ 'press_boxes.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/PressBoxes/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.press_boxes_desktop'] = [
	'styles' => [ 'press_boxes_desktop.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/PressBoxes/assets',
	'targets' => [ 'desktop' ],
	'position' => 'top'
];
