<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'TranslationLinkOverride',
	'author' => 'Gershon Bialer',
	'description' => 'Manually add translation links' 
);

$wgSpecialPages['TranslationLinkOverride'] = 'TranslationLinkOverride';
$wgAutoloadClasses['TranslationLinkOverride'] = dirname( __FILE__ ) . '/TranslationLinkOverride.body.php';

