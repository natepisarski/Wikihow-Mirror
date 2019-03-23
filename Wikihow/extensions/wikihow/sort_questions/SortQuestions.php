<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Sort Questions',
	'author' => 'Scott Cushman',
	'description' => 'Tool to prune our submitted questions for article Q&As.',
);

$wgSpecialPages['SortQuestions'] = 'SortQuestions';
$wgAutoloadClasses['SortQuestions'] = __DIR__ . '/SortQuestions.body.php';
$wgMessagesDirs['SortQuestions'] = __DIR__ . '/i18n';

$wgLogTypes[] = 'sort_questions_tool';
$wgLogNames['sort_questions_tool'] = 'sort_questions_tool';
$wgLogHeaders['sort_questions_tool'] = 'sort_questions_tool';

$wgResourceModules['ext.wikihow.sort_questions'] = array(
	'scripts' => array('sort_questions.js'),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/sort_questions',
	'targets' => array('mobile')
);

$wgResourceModules['ext.wikihow.sort_questions.styles'] = array(
	'styles' => array(
		'sort_questions.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/sort_questions',
	'position' => 'top',
	'targets' => array('mobile')
);
