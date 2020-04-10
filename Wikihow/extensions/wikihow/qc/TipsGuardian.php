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

$wgResourceModules['mobile.tipsguardian.styles'] = [
	'styles' => 'tipsguardian.css',
	'localBasePath' => __DIR__,
	'position' => 'top',
	'remoteExtPath' => 'wikihow/qc',
	'targets' => [ 'desktop', 'mobile' ]
];

$wgResourceModules['mobile.tipsguardian.scripts'] = [
	'scripts' => [
		'../ext-utils/anon_throttle.js',
		'tipsguardian.js'
	],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/qc',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [
		'mobile.wikihow',
		'ext.wikihow.MobileToolCommon'
	]
];
