<?php

$wgHooks['BeforePageDisplay'][] = 'ArticleQuizzes::onBeforePageDisplay';
$wgHooks['DesktopTopStyles'][] = ['ArticleQuizzes::addDesktopCSS'];
$wgHooks['MobileEmbedStyles'][] = ['ArticleQuizzes::addMobileCSS'];

$wgAutoloadClasses['Quiz'] = dirname( __FILE__ ) . '/Quiz.class.php';
$wgAutoloadClasses['ArticleQuizzes'] = dirname( __FILE__ ) . '/ArticleQuizzes.class.php';
$wgAutoloadClasses['QuizImporter'] = dirname( __FILE__ ) . '/QuizImporter.class.php';
$wgAutoloadClasses['AdminQuiz'] = dirname( __FILE__ ) . '/AdminQuiz.body.php';
$wgSpecialPages['AdminQuiz'] = 'AdminQuiz';

$wgResourceModules['ext.wikihow.quiz_js'] = [
	'scripts' => ['quiz.js'],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/quiz',
	'position' => 'bottom',
	'targets' => ['desktop', 'mobile'],
];

$wgResourceModules['ext.wikihow.quiz_css'] = [
	'styles' => ['quiz.css'],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/quiz',
	'position' => 'bottom',
	'targets' => ['desktop', 'mobile'],
];

$wgResourceModules['ext.wikihow.adminquiz'] = [
	'scripts' => ['adminquiz.js'],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/quiz',
	'position' => 'top',
	'targets' => ['desktop'],
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
];