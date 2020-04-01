<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['ThumbsEmailNotifications'] = 'ThumbsEmailNotifications';
$wgAutoloadClasses['ThumbsEmailNotifications'] = __DIR__ . '/ThumbsEmailNotifications.body.php';
$wgExtensionMessagesFiles['ThumbsEmailNotifications'] = __DIR__ . '/ThumbsEmailNotifications.i18n.php';
