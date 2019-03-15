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

$wgLogTypes[] = 'newtips';
$wgLogNames['newtips'] = 'newtips';
$wgLogHeaders['newtips'] = 'newtips';

$wgLogTypes[] = 'addedtip';
$wgLogNames['addedtip'] = 'addedtip';
$wgLogHeaders['addedtip'] = 'addedtip';

$wgHooks["IsEligibleForMobileSpecial"][] = array("wfTipsIsEligibleForMobile");

function wfTipsIsEligibleForMobile(&$isEligible) {
	global $wgTitle;
	if ($wgTitle && strrpos($wgTitle->getText(), "TipsAndWarnings") === 0) {
		$isEligible = true;
	}

	return true;
}
