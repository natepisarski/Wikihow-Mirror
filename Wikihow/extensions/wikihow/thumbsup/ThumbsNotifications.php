<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['ThumbsNotifications'] = 'ThumbsNotifications';
$wgAutoloadClasses['ThumbsNotifications'] = __DIR__ . '/ThumbsNotifications.body.php';
$wgExtensionMessagesFiles['ThumbsNotifications'] = __DIR__ . '/ThumbsNotifications.i18n.php';
