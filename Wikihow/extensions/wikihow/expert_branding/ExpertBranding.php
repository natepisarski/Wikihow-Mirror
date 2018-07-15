<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['ExpertBranding'] = dirname(__FILE__) . '/ExpertBranding.class.php';

$wgExtensionMessagesFiles['ExpertBranding'] = dirname(__FILE__) . '/ExpertBranding.i18n.php';

$wgHooks['ProcessArticleHTMLAfter'][] = array('ExpertBranding::onProcessArticleHTMLAfter');

$wgResourceModules['ext.wikihow.expert_branding'] = array(
	'scripts' => 'expert_branding.js',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/expert_branding',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);


