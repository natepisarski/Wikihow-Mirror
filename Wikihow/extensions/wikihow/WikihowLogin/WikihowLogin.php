<?php

/***********************************************
 * Our custom login and sign-up page templates *
 ***********************************************/

$wgResourceModules['ext.wikihow.loginpage_styles'] = [
	'styles' => 'wikihowlogin.css',
	'targets' => [ 'desktop' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/WikihowLogin',
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.login_responsive_styles'] = [
	'styles' => 'wikihowlogin.less',
	'targets' => [ 'desktop', 'mobile' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/WikihowLogin',
	'position' => 'top'
];

$wgAutoloadClasses['WikihowLogin'] = __DIR__ . '/WikihowLogin.body.php';
$wgAutoloadClasses['EmailLogin\\EmailPasswordAuthenticationProvider'] = __DIR__ . '/EmailLogin.php';

$wgMessagesDirs['WikihowLogin'] = __DIR__ . '/i18n';

$wgHooks['SpecialPage_initList'][] = ['WikihowLogin::onSpecialPage_initList'];
$wgHooks['AuthChangeFormFields'][] = ['WikihowLogin::onAuthChangeFormFields'];
$wgHooks['MobilePreRenderPreContent'][] = ['WikihowLogin::onMobilePreRenderPreContent'];
$wgHooks['BeforePageDisplay'][] = ['WikihowLogin::onBeforePageDisplay'];

$wgAuthManagerAutoConfig['primaryauth'] += [
	'EmailPasswordAuthenticationProvider' => [
		'class' => 'EmailLogin\\EmailPasswordAuthenticationProvider'
	]
];
