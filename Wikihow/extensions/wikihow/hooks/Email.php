<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['EmailBounceHooks'] = __DIR__ . '/Email.body.php';
$wgAutoloadClasses['EmailNotificationHooks'] = __DIR__ . '/Email.body.php';

$wgHooks['FilterOutBouncingEmails'][] = array( 'EmailBounceHooks::onFilterOutBouncingEmails' );
$wgHooks['AppendUnsubscribeLinkToBody'][] = array( 'EmailNotificationHooks::appendUnsubscribeLinkToBody' );
