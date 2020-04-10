<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Tips/Warnings CTA',
	'author' => 'Bebeth Steudel',
	'description' => '',
);

$wgSpecialPages['TipsAndWarnings'] = 'TipsAndWarnings';
$wgAutoloadClasses['TipsAndWarnings'] = __DIR__ . '/TipsAndWarnings.body.php';
$wgMessagesDirs['TipsAndWarnings'] = __DIR__ . '/i18n/';

$wgLogTypes[] = 'newtips';
$wgLogNames['newtips'] = 'newtips';
$wgLogHeaders['newtips'] = 'newtips';

$wgLogTypes[] = 'addedtip';
$wgLogNames['addedtip'] = 'addedtip';
$wgLogHeaders['addedtip'] = 'addedtip';

$wgHooks["IsEligibleForMobileSpecial"][] = array("wfTipsIsEligibleForMobile");
$wgHooks['BeforePageDisplay'][] = ['TipsAndWarnings::onBeforePageDisplay'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['TipsAndWarnings::onMobileProcessArticleHTMLAfter'];

$wgResourceModules['ext.wikihow.submit_a_tip'] = [
	'scripts' => [ 'submit_a_tip.js' ],
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'wikihow/tipsandwarnings/resources',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.submit_a_tip.styles'] = [
	'styles' => [ 'submit_a_tip.less' ],
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'wikihow/tipsandwarnings/resources',
	'targets' => [ 'desktop', 'mobile' ],
];

function wfTipsIsEligibleForMobile(&$isEligible) {
	global $wgTitle;
	if ($wgTitle && strrpos($wgTitle->getText(), "TipsAndWarnings") === 0) {
		$isEligible = true;
	}

	return true;
}
