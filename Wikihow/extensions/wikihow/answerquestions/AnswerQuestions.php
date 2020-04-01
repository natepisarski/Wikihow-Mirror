<?php

$wgExtensionCredits['AnswerQuestions'][] = array(
	'name' => 'QA Answering Tool',
	'author' => 'Bebeth Steudel',
	'description' => 'Tool for getting questions answered',
);

$wgSpecialPages['AnswerQuestions'] = 'AnswerQuestions';
$wgAutoloadClasses['AnswerQuestions'] = __DIR__ . '/AnswerQuestions.body.php';
$wgExtensionMessagesFiles['AnswerQuestions'] = __DIR__ . '/AnswerQuestions.i18n.php';
$wgAutoloadClasses['CategoryQuestions'] = __DIR__ . '/CategoryQuestions.class.php';
$wgSpecialPages['AdminAnswerQuestions'] = 'AdminAnswerQuestions';
$wgAutoloadClasses['AdminAnswerQuestions'] = __DIR__ . '/admin/AdminAnswerQuestions.body.php';

$wgResourceModules['wikihow.answerquestions_css'] = array(
	'styles' => array('answerquestions.css'),
	'localBasePath' => __DIR__ ,
	'remoteExtPath' => 'wikihow/answerquestions',
	'position' => 'top',
	'targets' => array('desktop'),
);

$wgResourceModules['wikihow.answerquestions_js'] = array(
	'scripts' => array('answerquestions.js'),
	'localBasePath' => __DIR__ ,
	'remoteExtPath' => 'wikihow/answerquestions',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'wikihow.common.string_validator'
	)
);

$wgResourceModules['wikihow.adminanswerquestions'] = array(
	'styles' => array('adminanswerquestions.css'),
	'scripts' => array('adminanswerquestions.js'),
	'localBasePath' => __DIR__ . '/admin',
	'remoteExtPath' => 'wikihow/answerquestions/admin',
	'position' => 'bottom',
	'targets' => array('desktop')
);
