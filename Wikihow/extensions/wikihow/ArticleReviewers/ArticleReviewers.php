<?php
if ( ! defined( 'MEDIAWIKI' ) )
die();

$wgExtensionCredits['specialpage'][] = array(
'name' => 'Article Reviewers',
'author' => 'Bebeth Steudel',
);

$wgAutoloadClasses['ArticleReviewers'] = __DIR__ . '/ArticleReviewers.body.php';

$wgSpecialPages['ArticleReviewers'] = 'ArticleReviewers';
$wgExtensionMessagesFiles['ArticleReviewers'] = __DIR__ . '/ArticleReviewers.i18n.php';
$wgExtensionMessagesFiles['ArticleReviewersAliases'] = __DIR__ . '/ArticleReviewers.alias.php';

$wgResourceModules['ext.wikihow.articlereviewers_styles'] = [
	'styles' => ['articlereviewers.css'],
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'wikihow/ArticleReviewers/resources',
	'position' => 'top',
	'targets' => [ 'desktop' ],
];

$wgResourceModules['ext.wikihow.articlereviewers_script'] = [
	'scripts' => ['articlereviewers.js'],
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'wikihow/ArticleReviewer/resources',
	'position' => 'bottom',
	'targets' => [ 'desktop', 'mobile' ],
];

$wgResourceModules['ext.wikihow.mobilearticlereviewers'] = [
	'styles' => ['mobilearticlereviewers.less'],
	'group' => 'prio2', // This RL group says load after main css bundle
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'wikihow/ArticleReviewers/resources',
	'position' => 'top',
	'targets' => [ 'mobile' ],
];

if (Misc::isIntl()) {
	return;
}

$wgSpecialPages['AdminArticleReviewers'] = 'AdminArticleReviewers';
$wgAutoloadClasses['AdminArticleReviewers'] = __DIR__ . '/AdminArticleReviewers.body.php';

$wgHooks['FileUpload'][] = ['AdminArticleReviewers::onFileUpload'];
$wgHooks['WebRequestPathInfoRouter'][] = ['ArticleReviewers::onWebRequestPathInfoRouter'];

$wgResourceModules['ext.wikihow.adminarticlereviewers'] = [
	'styles' => ['../../common/uploadify/uploadify.css'],
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'wikihow/ArticleReviewers/resources',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ],
	'scripts' => [
		'../../common/uploadify/jquery.uploadify.min.js',
		'adminarticlereviewers.js',
	],
];

/*********

CREATE TABLE `verifier_info` (
`vi_name` varchar(10) NOT NULL DEFAULT '',
`vi_info` blob NOT NULL,
PRIMARY KEY (`vi_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1

******/
