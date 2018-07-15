<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Flagged Answers',
	'author' => 'Scott Cushman',
	'description' => 'A tool (Fix Flagged Answers) & class for managing live Q&A answers that have been flagged.'
);

$wgSpecialPages['FixFlaggedAnswers'] = 'FixFlaggedAnswers';

$wgAutoloadClasses['FixFlaggedAnswers'] = dirname(__FILE__) . '/FixFlaggedAnswers.body.php';
$wgAutoloadClasses['FlaggedAnswers'] = dirname(__FILE__) . '/FlaggedAnswers.class.php';

$wgMessagesDirs['FlaggedAnswers'] = __DIR__ . '/i18n';

$wgHooks['InsertArticleQuestion'][] = ['FlaggedAnswers::onInsertArticleQuestion'];
$wgHooks['DeleteArticleQuestion'][] = ['FlaggedAnswers::onDeleteArticleQuestion'];

$wgLogTypes[] = 'fix_flagged_answers';
$wgLogNames['fix_flagged_answers_log'] = 'fix_flagged_answers_log';
$wgLogHeaders['fix_flagged_answers'] = 'fix_flagged_answers';

$wgResourceModules['ext.wikihow.fix_flagged_answers'] = [
	'scripts' => 'modules/fix_flagged_answers.js',
	'messages' => [
		'ffa_q_cl',
		'ffa_a_cl',
		'ffa_too_short',
		'ffa_url',
		'ffa_err'
	],
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/FlaggedAnswers',
	'targets' => ['desktop'],
	'dependencies' => [
		'wikihow.common.string_validator'
	]
];

$wgResourceModules['ext.wikihow.fix_flagged_answers.styles'] = [
	'styles' => 'modules/fix_flagged_answers.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/FlaggedAnswers',
	'targets' => ['desktop'],
	'position' => 'top'
];