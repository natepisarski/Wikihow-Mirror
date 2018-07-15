<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RC Lite',
	'author' => 'Jordan Small',
	'description' => 'A mobile, lite version of RC Patrol',
);

$wgSpecialPages['RCLite'] = 'RCLite';
$wgSpecialPages['AdminRCMobile'] = 'AdminRCMobile';
$wgAutoloadClasses['RCLite'] = dirname(__FILE__) . '/RCLite.body.php';
$wgAutoloadClasses['AdminRCMobile'] = dirname(__FILE__) . '/AdminRCMobile.body.php';
$wgExtensionMessagesFiles['RCLite'] = dirname(__FILE__) . '/RCLite.i18n.php';

$wgResourceModules['mobile.rclite'] = array(
	'scripts' => array('../ext-utils/anon_throttle.js', 'rclite.js'),
	'styles' => 'rclite.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/rclite',
	'messages' => array(
		'rcl-image-placeholder-txt',
		'rcl-waiting-initial-heading',
		'rcl-waiting-initial-sub',
		'rcl-waiting-yes-heading',
		'rcl-waiting-yes-sub',
		'rcl-waiting-no-heading',
		'rcl-waiting-no-sub',
		'rcl-waiting-maybe-heading',
		'rcl-waiting-maybe-sub',
		'rcl-msg-anon-limit',
		'rcl-login',
		'rcl-signup',
		'rcl-error-old-browser',
		'rcl-type-tip',
		'rcl-type-talk',
		'rcl-type-talk-title',
		'rcl-type-spelling',
		'rcl-type-spelling-plural',
		'rcl-error-unknown',
		'rcl-yes',
		'rcl-yes-plural',
		'rcl-no',
		'rcl-no-plural',
	),
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
	'dependencies' => array('mobile.wikihow', 'ext.wikihow.MobileToolCommon')
);
