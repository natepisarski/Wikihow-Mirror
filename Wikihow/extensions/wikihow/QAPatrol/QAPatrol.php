<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'QA Patrol',
    'author' => 'Scott Cushman',
    'description' => 'Tool to patrol recently-submitted questions and answers for article Q&As',
);

$wgSpecialPages['QAPatrol'] = 'QAPatrol';
$wgAutoloadClasses['QAPatrol'] = __DIR__ . '/QAPatrol.body.php';
$wgAutoloadClasses['QAPatrolStats'] = __DIR__ . '/QAPatrolStats.class.php';
$wgAutoloadClasses['QAPatrolItem'] = __DIR__ . '/model/QAPatrolItem.php';
$wgExtensionMessagesFiles['QAPatrol'] = __DIR__ . '/QAPatrol.i18n.php';

$wgLogTypes[] = 'qa_patrol';
$wgLogNames['qa_patrol'] = 'qa_patrol';
$wgLogHeaders['qa_patrol'] = 'qa_patrol';

$wgResourceModules['ext.wikihow.qa_patrol'] = array(
	'scripts' => 'qa_patrol.js',
	'styles' => 'qa_patrol.css',
	'messages' => array(
		'qap_txt',
		'qap_txt_edit',
		'qap_qid',
		'qap_answer_lf_err',
		'qap_flag_great',
		'qap_flag_thanks'
	),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/QAPatrol',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.qa_patrol_stats'] = [
	'scripts' => 'qa_patrol_stats.js',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/QAPatrol',
	'targets' => ['desktop'],
	'dependencies' => [
		'jquery.ui.datepicker'
	]
];

$wgResourceModules['ext.wikihow.qa_patrol_stats.style'] = [
	'styles' => 'qa_patrol_stats.css',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/QAPatrol',
	'position' => 'top',
	'targets' => ['desktop'],
];
