<?php

$wgExtensionCredits['MobileAppCTA'][] = array(
	'name' => 'MobileAppCTA',
	'author' => 'Jordan Small',
	'description' => 'A CTA to prompt users to download os-specific wikiHow apps',
);

$wgAutoloadClasses['MobileAppCTA'] = __DIR__ . '/MobileAppCTA.class.php';
$wgExtensionMessagesFiles['MobileAppCTA'] = __DIR__ . '/MobileAppCTA.i18n.php';

$wgHooks['BeforePageDisplay'][] = 'MobileAppCTA::onBeforePageDisplay';

$wgResourceModules['mobile.wikihow.mobile_app_cta'] = array(
	'styles' => array('mobile_app_cta.less'),
	'scripts' => array('mobile_app_cta.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/mobile_app_cta',
	'position' => 'bottom',
	'messages' => [
		'mcta_prompt_ios',
		'mcta_prompt_android',
		'mcta_subprompt_ios',
		'mcta_subprompt_android',
		'mcta_url_ios',
		'mcta_url_android'
	],
	'targets' => array('mobile', 'desktop'),
	'dependencies' => array('mobile.wikihow','jquery.cookie'),
);