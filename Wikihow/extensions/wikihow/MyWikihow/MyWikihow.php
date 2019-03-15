<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'My wikiHow',
	'author' => 'Scott Cushman',
	'description' => 'Choose your favorite categories and get suggestions',
);

$wgSpecialPages['MyWikihow'] = 'MyWikihow';
$wgAutoloadClasses['MyWikihow'] = __DIR__ . '/MyWikihow.body.php';
$wgExtensionMessagesFiles['MyWikihow'] = __DIR__ . '/MyWikihow.i18n.php';

$wgHooks["IsEligibleForMobileSpecial"][] = array("wfMWIsEligibleForMobile");

$wgResourceModules['ext.wikihow.my_wikihow'] = array(
	'scripts' => 'my_wikihow.js',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/MyWikihow',
	'position' => 'bottom',
	'dependencies' => array('mobile.wikihow', 'ext.wikihow.MobileToolCommon'),
	'messages' => array('mywikihow_hdr2','mywikihow_response'),
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.my_wikihow_styles'] = array(
	'styles' => 'my_wikihow.css',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/MyWikihow',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

function wfMWIsEligibleForMobile(&$isEligible) {
	global $wgTitle;
	if ($wgTitle && strrpos($wgTitle->getText(), "MyWikihow") === 0) {
		$isEligible = true;
	}

	return true;
}
