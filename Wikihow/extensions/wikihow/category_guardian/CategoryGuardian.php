<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CategoryGuardian',
	'author' => 'David Morrow',
	'description' => 'Tool to help users find incorrectly categorized articles',
);

$wgSpecialPages['CategoryGuardian'] = 'CategoryGuardian';
$wgAutoloadClasses['CategoryGuardianStandingsGroup'] = __DIR__ . '/CategoryGuardianStandingsGroup.php';
$wgAutoloadClasses['CategoryGuardianStandingsIndividual'] = __DIR__ . '/CategoryGuardianStandingsIndividual.php';

$wgAutoloadClasses['PostVote'] = __DIR__ . '/requests/PostVote.php';
$wgAutoloadClasses['GetArticles'] = __DIR__ . '/requests/GetArticles.php';
$wgAutoloadClasses['CategoryGuardian'] = __DIR__ . '/CategoryGuardian.body.php';
$wgExtensionMessagesFiles['CategoryGuardian'] = __DIR__ . '/CategoryGuardian.i18n.php';

$wgLogTypes[] = 'category_guardian';
$wgLogNames['category_guardian'] = 'category-guardian';
$wgLogHeaders['category_guardian'] = 'category-guardian-log-description';

$wgHooks["PageContentSaveComplete"][] = "CategoryGuardian::onArticleChange";
$wgHooks['ArticleDelete'][] = 'CategoryGuardian::onArticleChange';

$wgResourceModules['ext.wikihow.CategoryGuardian'] = array(
	'scripts' => array(
		'../common/mustache.js',
		'../ext-utils/stats_updater.js',
		'../ext-utils/anon_throttle.js',
		'../push-load/push-load.js',
		'modules/category_guardian.js'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/category_guardian',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'dependencies' => array('mediawiki.page.ready')
);

$wgResourceModules['ext.wikihow.CategoryGuardian.styles'] = array(
	'styles' => array(
		'modules/category_guardian.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css',
		'../push-load/push-load.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/category_guardian',
	'position' => 'top',
	'targets' => array('desktop', 'mobile')
);

$wgResourceModules['ext.wikihow.CategoryGuardian.styles.mobile'] = array(
	'styles' => array('modules/category_guardian.mobile.less'),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/category_guardian',
	'position' => 'top',
	'targets' => array('mobile')
);

$wgResourceModules['ext.wikihow.mobile.CategoryGuardian'] = $wgResourceModules['ext.wikihow.CategoryGuardian'];
$wgResourceModules['ext.wikihow.mobile.CategoryGuardian']['dependencies'] = array('mobile.wikihow');
$wgResourceModules['ext.wikihow.CategoryGuardian']['dependencies'] = array('mediawiki.page.ready');
