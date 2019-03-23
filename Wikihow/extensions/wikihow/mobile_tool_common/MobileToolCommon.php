<?php

$wgResourceModules['ext.wikihow.MobileToolCommon'] = array(
	'styles' => 'mobile_tool_common.less',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/mobile_tool_common',
	'messages' => array(
		'adw_show',
		'adw_hide',
	),
	'position' => 'bottom',
	'targets' => array( 'mobile' ),
	'dependencies' => array('mobile.wikihow', 'wikihow.common.font-awesome', 'ext.wikihow.ArticleDisplayWidget')
);
