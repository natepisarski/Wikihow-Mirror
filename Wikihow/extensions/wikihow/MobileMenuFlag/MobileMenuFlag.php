<?php

$wgExtensionCredits['MobileMenuFlag'][] = array(
	'name' => 'Mobile Menu Flag',
	'author' => 'Scott Cushman',
	'description' => 'Lightweight extension to add flags to the mobile menu so we can drive traffic to different features.',
);

$wgResourceModules['mobile.wikihow.mmf'] = array(
	'styles' => ['mobile_menu_flag.css'],
	'scripts' => ['mobile_menu_flag.js'],
	'messages' => [
		'mobile_menu_flag',
		'mobile_menu_percent'
		],
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/MobileMenuFlag',
	'dependencies' => ['ext.wikihow.common_top'],
	'targets' => ['mobile']
);

$wgHooks['BeforePageDisplay'][] = ['addMobileMenuFlags'];

//add the mobile menu flag module for anonymous English mobile page users
function addMobileMenuFlags(&$out) {
	if ($out->getLanguage()->getCode() == 'en'
		&& Misc::isMobileMode()
		&& $out->getUser()->isAnon())
	{
		$out->addModules('mobile.wikihow.mmf');
	}
	return true;
}
