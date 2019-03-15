<?php

if ( !defined('MEDIAWIKI') ) exit;

$wgExtensionMessagesFiles['EchoWikihow'] = __DIR__ . '/EchoWikihow.i18n.php';
$wgAutoloadClasses['EchoWikiHowFormatter'] = __DIR__ . '/EchoWikihow.body.php';
$wgAutoloadClasses['EchoWikiHowHooks'] = __DIR__ . '/EchoWikihow.body.php';

/****************** HOOK, LINE, WINNER ***/
$wgHooks['BeforeCreateEchoEvent'][] = array('EchoWikiHowHooks::onBeforeCreateEchoEvent');
$wgHooks['EchoGetDefaultNotifiedUsers'][] = array('EchoWikiHowHooks::onEchoGetDefaultNotifiedUsers');
$wgHooks['CreateEmailPreferences'][] = array('EchoWikiHowHooks::onCreateEmailPreferences');
$wgHooks['GetPreferences'][] = array('EchoWikiHowHooks::onGetPreferences');
$wgHooks['AddNewAccount'][] = array('EchoWikiHowHooks::onAccountCreated');
$wgHooks['EchoAbortEmailNotification'][] = array('EchoWikiHowHooks::onEchoAbortEmailNotification');
$wgHooks['UserClearNewKudosNotification'][] = array('EchoWikihowHooks::onUserClearNewKudosNotification');

//only web
$wgEchoNotifiers = array(
	'web' => array( 'EchoNotifier', 'notifyWithNotification' ), // web-based notification
);

/******************* DEFAULT OPTIONS *****/
$wgEchoDefaultNotificationTypes = array(
	'all' => array(
		'web' => true,
		'email' => false,
	),
);

$wgDefaultUserOptions['echo-subscriptions-web-kudos'] = true;
$wgDefaultUserOptions['echo-subscriptions-web-thumbs-up'] = true;
$wgDefaultUserOptions['echo-subscriptions-web-article-linked'] = false;

