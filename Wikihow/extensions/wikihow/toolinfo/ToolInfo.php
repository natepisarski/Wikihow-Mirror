<?php
/*
 * ToolInfo is for adding the (?) to our tools
 * with an expanding box below explaining stuff
 */

$wgAutoloadClasses['ToolInfo'] = __DIR__ . '/ToolInfo.class.php';
$wgExtensionMessagesFiles['ToolInfo'] = __DIR__ . '/ToolInfo.i18n.php';

$wgResourceModules['ext.wikihow.toolinfo'] = array(
	'scripts' => 'toolinfo.js',
	'styles' => 'toolinfo.less',
	'messages' => 'ti_help',
	'dependencies' => 'ext.wikihow.UsageLogs',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/toolinfo',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);
