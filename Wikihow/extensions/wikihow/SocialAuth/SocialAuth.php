<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['other'][] = array(
	'name' => 'SocialAuth',
	'author' => 'Alberto Burgos',
	'description' => "Classes for shared social authentication features",
);

$wgMessagesDirs['SocialAuth'] = __DIR__ . '/i18n/';
$wgAutoloadClasses['SocialAuth\SocialAuthDao'] = __DIR__ . '/model/SocialAuthDao.class.php';
$wgAutoloadClasses['SocialAuth\SocialUser'] = __DIR__ . '/model/SocialUser.class.php';
$wgAutoloadClasses['SocialAuth\FacebookSocialUser'] = __DIR__ . '/model/FacebookSocialUser.class.php';
$wgAutoloadClasses['SocialAuth\CivicSocialUser'] = __DIR__ . '/model/CivicSocialUser.class.php';
$wgAutoloadClasses['SocialAuth\GoogleSocialUser'] = __DIR__ . '/model/GoogleSocialUser.class.php';

$wgResourceModules['ext.wikihow.socialauth'] = [
    'scripts'       => 'social_auth.js',
    'messages'		=> [
		"socialauth-fblogin-login-failed",
		"socialauth-gpluslogin-login-failed",
		"socialauth-civiclogin-login-failed"
	],
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/SocialAuth',
    'targets'       => ['desktop', 'mobile'],
];
