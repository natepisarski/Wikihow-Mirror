<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Special Tech Feedback',
	'author' => 'aaron',
	'description' => 'tool to vote on feedback of tech articles',
);

$wgSpecialPages['SpecialTechFeedback'] = 'SpecialTechFeedback';
$wgAutoloadClasses['SpecialTechFeedback'] = __DIR__ . '/SpecialTechFeedback.body.php';
$wgMessagesDirs['SpecialTechFeedback'] = __DIR__ . '/i18n';

$wgLogTypes[] = 'tech_update_tool';
$wgLogNames['tech_update_tool'] = 'tech_update_tool';
$wgLogHeaders['tech_update_tool'] = 'tech_update_tool';

$wgResourceModules['ext.wikihow.specialtechfeedback'] = array(
	'scripts' => array('specialtechfeedback.js'),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialtechfeedback',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.specialtechfeedback.styles'] = array(
	'styles' => array(
		'specialtechfeedback.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialtechfeedback',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

$wgExtensionMessagesFiles['SpecialTechFeedbackAliases'] = __DIR__ . '/SpecialTechFeedback.alias.php';

$wgHooks['RatingsToolRatingReasonAdded'][] = 'SpecialTechFeedback::onRatingsToolRatingReasonAdded';
// $wgHooks['RatingReasonAfterGetRatingReasonResponse'][] = 'SpecialTechFeedback::onRatingReasonAfterGetRatingReasonResponse';
