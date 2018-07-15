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
$wgAutoloadClasses['SocialAuth\SocialAuthDao'] = dirname( __FILE__ ) . '/model/SocialAuthDao.class.php';
$wgAutoloadClasses['SocialAuth\SocialUser'] = dirname( __FILE__ ) . '/model/SocialUser.class.php';
$wgAutoloadClasses['SocialAuth\FacebookSocialUser'] = dirname( __FILE__ ) . '/model/FacebookSocialUser.class.php';
$wgAutoloadClasses['SocialAuth\CivicSocialUser'] = dirname( __FILE__ ) . '/model/CivicSocialUser.class.php';
$wgAutoloadClasses['SocialAuth\GoogleSocialUser'] = dirname( __FILE__ ) . '/model/GoogleSocialUser.class.php';

$wgResourceModules['ext.wikihow.socialauth'] = [
    'scripts'       => 'social_auth.js',
    'messages'		=> [
		"socialauth-fblogin-login-failed",
		"socialauth-gpluslogin-login-failed",
		"socialauth-civiclogin-login-failed"
	],
    'localBasePath' => dirname(__FILE__),
    'remoteExtPath' => 'wikihow/SocialAuth',
    'targets'       => ['desktop', 'mobile'],
];
