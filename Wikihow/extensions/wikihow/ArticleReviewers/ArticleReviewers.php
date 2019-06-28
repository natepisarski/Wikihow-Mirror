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

$wgResourceModules['ext.wikihow.articlereviewers'] = array(
	'styles' => array('articlereviewers.css'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/ArticleReviewers',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.wikihow.articlereviewers_script'] = array(
	'scripts' => array('articlereviewers.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/ArticleReviewers',
	'position' => 'bottom',
	'targets' => array( 'desktop' ),
);

$wgResourceModules['ext.wikihow.mobilearticlereviewers'] = array(
	'styles' => array('mobilearticlereviewers.css'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/ArticleReviewers',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
);

if (Misc::isIntl()) {
	return;
}

$wgSpecialPages['AdminArticleReviewers'] = 'AdminArticleReviewers';
$wgAutoloadClasses['AdminArticleReviewers'] = __DIR__ . '/AdminArticleReviewers.body.php';

$wgResourceModules['ext.wikihow.adminarticlereviewers'] = array(
	'styles' => array('../common/uploadify/uploadify.css'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/ArticleReviewers',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
	'scripts' => array('adminarticlereviewers.js'),
);

/*********

CREATE TABLE `verifier_info` (
`vi_name` varchar(10) NOT NULL DEFAULT '',
`vi_info` blob NOT NULL,
PRIMARY KEY (`vi_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1

******/
