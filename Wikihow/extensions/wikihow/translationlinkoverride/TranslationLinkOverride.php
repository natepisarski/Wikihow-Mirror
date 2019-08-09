<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'TranslationLinkOverride',
	'author' => 'Gershon Bialer',
	'description' => 'Manually add translation links'
);

$wgSpecialPages['TranslationLinkOverride'] = 'TranslationLinkOverride';
$wgAutoloadClasses['TranslationLinkOverride'] = __DIR__ . '/TranslationLinkOverride.body.php';

$wgResourceModules['ext.wikihow.translationlinkoverride_styles'] = [
	'styles' => [ 'translationlinkoverride.css' ],
	'targets' => [ 'desktop' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/translationlinkoverride',
];

$wgResourceModules['ext.wikihow.translationlinkoverride'] = [
	'scripts' => [
		'../common/download.jQuery.js',
		'webtoolkit.aim.js',
		'translationlinkoverride.js'
	],
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery', 'ext.wikihow.common_bottom' ],
	'remoteExtPath' => 'wikihow/translationlinkoverride',
	'position' => 'top'
];
