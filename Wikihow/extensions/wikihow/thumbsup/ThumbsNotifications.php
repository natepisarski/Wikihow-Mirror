<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['ThumbsNotifications'] = 'ThumbsNotifications';
$wgAutoloadClasses['ThumbsNotifications'] = dirname( __FILE__ ) . '/ThumbsNotifications.body.php';
$wgExtensionMessagesFiles['ThumbsNotifications'] = dirname(__FILE__) . '/ThumbsNotifications.i18n.php';
