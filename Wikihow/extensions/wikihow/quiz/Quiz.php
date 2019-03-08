<?php

$wgHooks['BeforePageDisplay'][] = 'ArticleQuizzes::onBeforePageDisplay';
$wgHooks['DesktopTopStyles'][] = ['ArticleQuizzes::addDesktopCSS'];
$wgHooks['MobileEmbedStyles'][] = ['ArticleQuizzes::addMobileCSS'];
$wgHooks['BeforePageDisplay'][] = ['QuizYourselfCTA::onBeforePageDisplay'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['QuizYourselfCTA::onProcessArticleHTMLAfter'];

$wgAutoloadClasses['Quiz'] = dirname( __FILE__ ) . '/Quiz.class.php';
$wgAutoloadClasses['ArticleQuizzes'] = dirname( __FILE__ ) . '/ArticleQuizzes.class.php';
$wgAutoloadClasses['QuizImporter'] = dirname( __FILE__ ) . '/QuizImporter.class.php';
$wgAutoloadClasses['AdminQuiz'] = dirname( __FILE__ ) . '/AdminQuiz.body.php';
$wgAutoloadClasses['QuizYourself'] = __DIR__ . '/QuizYourself/QuizYourself.body.php';
$wgAutoloadClasses['QuizYourselfCTA'] = __DIR__ . '/QuizYourself/QuizYourselfCTA.class.php';

$wgSpecialPages['AdminQuiz'] = 'AdminQuiz';
$wgSpecialPages['QuizYourself'] = 'QuizYourself';

$wgMessagesDirs['QuizYourself'] = __DIR__ . '/QuizYourself/i18n';

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

$wgResourceModules['ext.wikihow.quiz_yourself'] = [
	'styles' => [ 'quiz_yourself.less' ],
	'scripts' => [ 'quiz_yourself.js' ],
	'localBasePath' => __DIR__.'/QuizYourself/resources',
	'remoteExtPath' => 'wikihow/quiz/QuizYourself/resources',
	'targets' => [ 'mobile' ],
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.quiz_yourself_cta'] = [
	'styles' => [ 'quiz_yourself_cta.less' ],
	'scripts' => [ 'quiz_yourself_cta.js' ],
	'localBasePath' => __DIR__.'/QuizYourself/resources',
	'remoteExtPath' => 'wikihow/quiz/QuizYourself/resources',
	'targets' => [ 'mobile' ],
	'position' => 'bottom'
];
