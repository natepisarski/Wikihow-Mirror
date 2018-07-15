<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['ThumbsEmailNotifications'] = 'ThumbsEmailNotifications';
$wgAutoloadClasses['ThumbsEmailNotifications'] = dirname( __FILE__ ) . '/ThumbsEmailNotifications.body.php';
$wgExtensionMessagesFiles['ThumbsEmailNotifications'] = dirname(__FILE__) . '/ThumbsEmailNotifications.i18n.php';
