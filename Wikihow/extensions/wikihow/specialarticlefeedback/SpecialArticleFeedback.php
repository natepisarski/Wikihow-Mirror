<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Special Article Feedback',
	'author' => 'aaron',
	'description' => 'tool to vote on feedback of articles',
);

$wgSpecialPages['SpecialArticleFeedback'] = 'SpecialArticleFeedback';
$wgAutoloadClasses['SpecialArticleFeedback'] = __DIR__ . '/SpecialArticleFeedback.body.php';
$wgMessagesDirs['SpecialArticleFeedback'] = __DIR__ . '/i18n';

$wgLogTypes[] = 'article_feedback_tool';
$wgLogNames['article_feedback_tool'] = 'article_feedback_tool';
$wgLogHeaders['article_feedback_tool'] = 'article_feedback_tool';

$wgResourceModules['ext.wikihow.specialarticlefeedback'] = array(
	'scripts' => array('specialarticlefeedback.js'),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialarticlefeedback',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.specialarticlefeedback.styles'] = array(
	'styles' => array(
		'specialarticlefeedback.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialarticlefeedback',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

$wgExtensionMessagesFiles['SpecialArticleFeedbackAliases'] = __DIR__ . '/SpecialArticleFeedback.alias.php';

$wgHooks['RatingsToolRatingReasonAdded'][] = 'SpecialArticleFeedback::onRatingsToolRatingReasonAdded';
