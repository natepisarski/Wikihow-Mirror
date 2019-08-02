<?php

if ( !defined('MEDIAWIKI') ) exit;

$wgMessagesDirs['EchoWikihow'] = __DIR__ . '/i18n';

$wgAutoloadClasses['EchoWikihow'] = __DIR__ . '/EchoWikihow.body.php';
$wgAutoloadClasses['EchoWikiHowHooks'] = __DIR__ . '/EchoWikihow.body.php';
$wgAutoloadClasses['EchoWikihowMenu'] = __DIR__ . '/EchoWikihowMenu.php';

//presentation models
$wgAutoloadClasses['EchoWikihowThumbsUpPresentationModel'] = __DIR__ . '/formatters/WikihowThumbsUpPresentationModel.php';
$wgAutoloadClasses['EchoWikihowKudosPresentationModel'] = __DIR__ . '/formatters/WikihowKudosPresentationModel.php';
$wgAutoloadClasses['EchoWikihowEditUserTalkPresentationModel'] = __DIR__ . '/formatters/WikihowEditUserTalkPresentationModel.php';
$wgAutoloadClasses['EchoWikihowWelcomePresentationModel'] = __DIR__ . '/formatters/WikihowWelcomePresentationModel.php';
$wgAutoloadClasses['EchoWikihowEditThresholdPresentationModel'] = __DIR__ . '/formatters/WikihowEditThresholdPresentationModel.php';
$wgAutoloadClasses['EchoWikihowMentionPresentationModel'] = __DIR__ . '/formatters/WikihowMentionPresentationModel.php';
$wgAutoloadClasses['EchoWikihowUserRightsPresentationModel'] = __DIR__ . '/formatters/WikihowUserRightsPresentationModel.php';

/****************** HOOK, LINE, WINNER ***/
$wgHooks['BeforeCreateEchoEvent'][] = array('EchoWikiHowHooks::onBeforeCreateEchoEvent');
// $wgHooks['EchoGetDefaultNotifiedUsers'][] = array('EchoWikiHowHooks::onEchoGetDefaultNotifiedUsers');
$wgHooks['CreateEmailPreferences'][] = array('EchoWikiHowHooks::onCreateEmailPreferences');
$wgHooks['GetPreferences'][] = array('EchoWikiHowHooks::onGetPreferences');
$wgHooks['EchoAbortEmailNotification'][] = array('EchoWikiHowHooks::onEchoAbortEmailNotification');
$wgHooks['UserClearNewKudosNotification'][] = array('EchoWikihowHooks::onUserClearNewKudosNotification');
$wgHooks['BeforeEchoEventInsert'][] = array('EchoWikihowHooks::onBeforeEchoEventInsert');

// only notify via web
$wgEchoNotifiers = [
	'web' => [ 'EchoNotifier', 'notifyWithNotification' ], // web-based notification
];

$wgEchoDefaultNotificationTypes = [
	'web' => true,
	'email' => false,
];

$wgDefaultUserOptions['echo-subscriptions-web-kudos'] = true;
$wgDefaultUserOptions['echo-subscriptions-web-thumbs-up'] = true;
$wgDefaultUserOptions['echo-subscriptions-web-article-linked'] = false;

$wgResourceModules['ext.wikihow.echowikihow'] = [
	'styles' => ['echowikihow.css'],
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/EchoWikihow',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ]
];