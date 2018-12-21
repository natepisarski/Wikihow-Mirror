<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['SocialProofStats'] = dirname(__FILE__) . '/SocialProofStats.php';
$wgExtensionMessagesFiles['SocialProof'] = dirname(__FILE__) . '/SocialProof.i18n.php';

$wgSpecialPages['SocialProof'] = 'SocialProof';
$wgAutoloadClasses['SocialProof'] = dirname(__FILE__) . '/SocialProof.body.php';
$wgAutoloadClasses['StaffReviewed'] = dirname(__FILE__) . '/StaffReviewed.class.php';

$wgHooks['ArticlePurge'][] = array('SocialProofStats::onArticlePurge');
$wgHooks['SensitiveArticleEdited'][] = 'StaffReviewed::handleSensitiveArticleEdit';
$wgHooks['BylineStamp'][] = ['SocialProofStats::setBylineInfo'];
$wgHooks['BylineStamp'][] = ['StaffReviewed::setBylineInfo'];

$wgResourceModules['ext.wikihow.socialproof.special'] = array(
	'styles' => array('socialproof.css'),
	'scripts' => 'socialproofspecial.js',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'dependencies' => array(
		'jquery.spinner'
	),
	'remoteExtPath' => 'wikihow/socialproof',
);

$wgResourceModules['ext.wikihow.socialproof'] = array(
	'styles' => array('socialproof.css'),
	'scripts' => 'socialproof.js',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'dependencies' => array(
		'wikihow.common.jquery.dateformat',
		'ext.wikihow.common_top',
	),
	'remoteExtPath' => 'wikihow/socialproof',
	'messages' => [
		'sp_updated',
		'sp_votethanks',
		'sp_star_label_1',
		'sp_star_label_2',
		'sp_star_label_3',
		'sp_star_label_4',
		'sp_star_label_5'
	]
);

$wgResourceModules['mobile.wikihow.socialproof'] = array(
	'styles' => array( 'socialproof.css','mobilesocialproof.css' ),
	'scripts' => 'socialproof.js',
	'targets' => array( 'desktop', 'mobile' ),
	'localBasePath' => __DIR__,
	'dependencies' => array(
		'wikihow.common.jquery.dateformat',
		'ext.wikihow.common_top',
	),
	'remoteExtPath' => 'wikihow/socialproof',
	'messages' => [
		'sp_updated',
		'sp_votethanks',
		'sp_star_label_1',
		'sp_star_label_2',
		'sp_star_label_3',
		'sp_star_label_4',
		'sp_star_label_5'
	]
);