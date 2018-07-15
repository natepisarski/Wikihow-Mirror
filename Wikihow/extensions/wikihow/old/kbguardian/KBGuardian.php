<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgLogTypes[] = 'kbguardian';
$wgLogNames['kbguardian'] = 'kbguardian';
$wgLogHeaders['kbguardian'] = 'kbguardian_log';

$wgExtensionCredits['specialpage'][] = [
	'name' => 'KB Guardian',
	'author' => 'Jordan Small',
	'description' => 'A guardian tool for Knowledge Box content',
];

$wgSpecialPages['KBGuardian'] = 'KBGuardian';
$wgAutoloadClasses['KBGuardian'] = dirname(__FILE__) . '/KBGuardian.body.php';
$wgExtensionMessagesFiles['KBGuardian'] = dirname(__FILE__) . '/KBGuardian.i18n.php';

$wgHooks['EditfishArticlesCompleted'][] = 'KBGuardian::onEditfishArticlesCompleted';

$wgResourceModules['ext.wikihow.kbguardian.scripts'] = [
	'scripts' => 'kbguardian.js',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/kbguardian',
	'messages' => [
		'kbg-image-placeholder-txt',
		'kbg-waiting-initial-heading',
		'kbg-waiting-initial-sub',
		'kbg-waiting-yes-heading',
		'kbg-waiting-yes-sub',
		'kbg-waiting-no-heading',
		'kbg-waiting-no-sub',
		'kbg-waiting-maybe-heading',
		'kbg-waiting-maybe-sub',
		'kbg-knowledge-loading',
		'kbg-msg-anon-limit',
		'kbg-login',
		'kbg-signup',
		'kbg-error-old-browser',
		'kbg-type-tip',
		'kbg-type-talk',
		'kbg-type-spelling',
		'kbg-type-spelling-plural',
		'kbg-error-unknown',
		'kbg-yes',
		'kbg-yes-plural',
		'kbg-no',
		'kbg-no-plural',
		'howto'
	],
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ]
];

$wgResourceModules['ext.wikihow.mobile.kbguardian.scripts'] =
	$wgResourceModules['ext.wikihow.kbguardian.scripts'] + [
		'dependencies' =>
			['mobile.wikihow', 'mediawiki.page.ready', 'ext.wikihow.MobileToolCommon']
	];

$wgResourceModules['ext.wikihow.kbguardian.styles'] = [
	'styles' => 'kbguardian.css',
	'localBasePath' => dirname(__FILE__),
	'remoteExtPath' => 'wikihow/kbguardian',
	'targets' => ['desktop', 'mobile'],
];

$wgResourceModules['ext.wikihow.desktop.kbguardian.styles'] = [
	'styles' => 'kbguardian_desktop.css',
	'localBasePath' => dirname(__FILE__),
	'remoteExtPath' => 'wikihow/kbguardian',
	'targets' => ['desktop'],
	'position' => 'top'
];
