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

