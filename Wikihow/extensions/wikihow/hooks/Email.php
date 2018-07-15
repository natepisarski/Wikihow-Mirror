<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['EmailBounceHooks'] = dirname(__FILE__) . '/Email.body.php';
$wgAutoloadClasses['EmailNotificationHooks'] = dirname(__FILE__) . '/Email.body.php';

$wgHooks['FilterOutBouncingEmails'][] = array( 'EmailBounceHooks::onFilterOutBouncingEmails' );
$wgHooks['AppendUnsubscribeLinkToBody'][] = array( 'EmailNotificationHooks::appendUnsubscribeLinkToBody' );
