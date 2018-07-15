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

$wgAutoloadClasses['SocialLogin'] = dirname(__FILE__) . '/SocialLogin.body.php';
$wgAutoloadClasses['SocialLoginUtil'] = dirname(__FILE__) . '/SocialLoginUtil.class.php';
// $wgAutoloadClasses['SocialLoginUsernames'] = dirname(__FILE__) . '/SocialLoginUsernames.body.php';

$wgExtensionMessagesFiles['SocialLogin'] = dirname(__FILE__) . '/SocialLogin.i18n.php';

$wgResourceModules['ext.wikihow.sociallogin.buttons'] = [
	'styles'        => 'sociallogin.buttons.less',
	'localBasePath' => dirname(__FILE__),
	'remoteExtPath' => 'wikihow/SocialLogin',
	'targets'       => ['desktop', 'mobile'],
];

$wgResourceModules['ext.wikihow.sociallogin'] = [
	'scripts'       => 'sociallogin.js',
	'dependencies'  => [ 'ext.wikihow.sociallogin.buttons' ],
	'localBasePath' => dirname(__FILE__),
	'remoteExtPath' => 'wikihow/SocialLogin',
	'targets'       => ['desktop', 'mobile'],
];
