<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/*
 * ToolInfo is for adding the (?) to our tools
 * with an exanding box below explaining stuff
 */
 
$wgAutoloadClasses['ToolInfo'] = dirname( __FILE__ ) . '/ToolInfo.class.php';
$wgExtensionMessagesFiles['ToolInfo'] = dirname(__FILE__) . '/ToolInfo.i18n.php';

$wgResourceModules['ext.wikihow.toolinfo'] = array(
	'scripts' => 'toolinfo.js',
	'styles' => 'toolinfo.css',
	'messages' => 'ti_help',
	'dependencies' => 'ext.wikihow.UsageLogs',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/toolinfo',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);