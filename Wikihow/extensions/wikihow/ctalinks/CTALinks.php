<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/*    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CTALinks',
	'author' => 'Jordan Small', 
	'description' => 'Display a call to action (CTA) in the right rail for anon users',
);

$wgSpecialPages['CTALinks'] = 'CTALinks';
*/
$wgAutoloadClasses['CTALinks'] = dirname( __FILE__ ) . '/CTALinks.body.php';
$wgExtensionMessagesFiles['CTALinks'] = dirname(__FILE__) . '/CTALinks.i18n.php';
