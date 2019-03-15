<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Tips Guardian',
	'author' => 'Scott Cushman',
	'description' => 'A mobile-only tool to guard against bad tips (simplified version of QG)',
);

$wgSpecialPages['TipsGuardian'] = 'TipsGuardian';
$wgAutoloadClasses['TipsGuardian'] = __DIR__ . '/TipsGuardian.body.php';
$wgExtensionMessagesFiles['TipsGuardian'] = __DIR__ . '/TipsGuardian.i18n.php';

$wgHooks["IsEligibleForMobileSpecial"][] = array("wfTGIsEligibleForMobile");

$wgResourceModules['mobile.tipsguardian.styles'] = array(
	'styles' => 'tipsguardian.css',
	'localBasePath' => __DIR__,
	'position' => 'top',
	'remoteExtPath' => 'wikihow/qc',
	'targets' => array('mobile'),
);

$wgResourceModules['mobile.tipsguardian.scripts'] = array(
	'scripts' => array('../ext-utils/anon_throttle.js',
					'tipsguardian.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/qc',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
	'dependencies' => array('mobile.wikihow', 'ext.wikihow.MobileToolCommon'),
);


function wfTGIsEligibleForMobile(&$isEligible) {
	global $wgTitle;
	if ($wgTitle && strrpos($wgTitle->getText(), "TipsGuardian") === 0) {
		$isEligible = true;
	}

	return true;
}
