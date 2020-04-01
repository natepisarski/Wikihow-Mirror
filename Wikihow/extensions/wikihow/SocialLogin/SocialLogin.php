<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'SocialLogin',
	'author' => 'Alberto Burgos',
	'description' => "Endpoint to perform automated social logins/signups",
);

$wgSpecialPages['SocialLogin'] = 'SocialLogin';
// $wgSpecialPages['SocialLoginUsernames'] = 'SocialLoginUsernames';

$wgAutoloadClasses['SocialLogin'] = __DIR__ . '/SocialLogin.body.php';
$wgAutoloadClasses['SocialLoginUtil'] = __DIR__ . '/SocialLoginUtil.class.php';
// $wgAutoloadClasses['SocialLoginUsernames'] = __DIR__ . '/SocialLoginUsernames.body.php';

$wgExtensionMessagesFiles['SocialLogin'] = __DIR__ . '/SocialLogin.i18n.php';

$wgResourceModules['ext.wikihow.sociallogin.buttons'] = [
	'styles'        => 'sociallogin.buttons.less',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/SocialLogin',
	'targets'       => ['desktop', 'mobile'],
];

$wgResourceModules['ext.wikihow.sociallogin'] = [
	'scripts'       => 'sociallogin.js',
	'dependencies'  => [ 'ext.wikihow.sociallogin.buttons' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/SocialLogin',
	'targets'       => ['desktop', 'mobile'],
];
