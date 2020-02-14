<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['SocialProofStats'] = __DIR__ . '/SocialProofStats.php';
$wgExtensionMessagesFiles['SocialProof'] = __DIR__ . '/SocialProof.i18n.php';

$wgSpecialPages['SocialProof'] = 'SocialProof';
$wgAutoloadClasses['SocialProof'] = __DIR__ . '/SocialProof.body.php';
$wgAutoloadClasses['StaffReviewed'] = __DIR__ . '/StaffReviewed.class.php';
$wgAutoloadClasses['ExpertAdviceSection'] = __DIR__ . '/ExpertAdviceSection.class.php';

$wgHooks['ArticlePurge'][] = ['SocialProofStats::onArticlePurge'];
$wgHooks['BylineStamp'][] = ['SocialProofStats::setBylineInfo'];
$wgHooks['BylineStamp'][] = ['StaffReviewed::setBylineInfo'];
$wgHooks['ProcessArticleHTMLAfter'][]  = ['ExpertAdviceSection::onProcessArticleHTMLAfter'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['ExpertAdviceSection::onProcessArticleHTMLAfter'];
$wgHooks['BeforePageDisplay'][]  = ['ExpertAdviceSection::onBeforePageDisplay'];

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
	'targets' => array( 'desktop', 'mobile' ),
	'localBasePath' => __DIR__,
	'dependencies' => array(
		'wikihow.common.jquery.dateformat',
		'ext.wikihow.common_top',
		'ext.wikihow.userreview'
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
	'styles' => array( 'socialproof.css', 'mobilesocialproof.css', 'noamp-mobilesocialproof.css' ),
	'scripts' => 'socialproof.js',
	'targets' => array( 'desktop', 'mobile' ),
	'localBasePath' => __DIR__,
	'dependencies' => array(
		'wikihow.common.jquery.dateformat',
		'ext.wikihow.common_top',
		'mobile.wikihow.userreview'
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

$wgResourceModules['ext.wikihow.expertadvicesection.styles'] = [
	'styles' => ['expertadvicesection.less'],
	'targets' => [ 'desktop', 'mobile' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/socialproof',
	'position' => 'bottom'
];
