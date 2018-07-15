<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['UserReviewImporter'] = dirname( __FILE__ ) . '/UserReviewImporter.class.php';
$wgAutoloadClasses['UserReviewTool'] = dirname( __FILE__ ) . '/UserReviewTool.body.php';
$wgAutoloadClasses['UserReview'] = dirname(__FILE__) . '/UserReview.class.php';
$wgAutoloadClasses['AdminUserReview'] = dirname(__FILE__) . '/admin/AdminUserReview.body.php';
$wgExtensionMessagesFiles['UserReview'] = __DIR__ . '/UserReview.i18n.php';
$wgExtensionMessagesFiles['AdminUserReview'] = __DIR__ . '/admin/AdminUserReview.i18n.php';

$wgSpecialPages['UserReviewTool'] = 'UserReviewTool';
$wgSpecialPages['UserReviewImporter'] = 'UserReviewImporter';
$wgSpecialPages['AdminUserReview'] = 'AdminUserReview';

$wgHooks['BeforePageDisplay'][] = 'UserReview::onBeforePageDisplay';
//$wgHooks['VerifyImportComplete'][] = 'UserReview::handleNewExpertImport';
$wgHooks['PicturePatrolResolved'][] = 'UserReview::handlePicturePatrol';
$wgHooks['SensitiveArticleEdited'][] = 'UserReview::handSensitiveArticleEdit';

$wgResourceModules['ext.wikihow.userreviewtool'] = array(
	'scripts' => array('userreviewtool.js'),
	'styles' => array('userreviewtool.css'),
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/userreview',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.wikihow.userreviewimporter'] = array(
	'scripts' => array('userreviewimporter.js'),
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/userreview',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.wikihow.userreview'] = array(
	'scripts' => array('userreview.js'),
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/userreview',
	'position' => 'top',
	'targets' => array( 'desktop' ),
);

$wgResourceModules['mobile.wikihow.userreview'] = array(
	'scripts' => array('userreview.js'),
	'styles' => array('userreview_mobile.css'),
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/userreview',
	'targets' => array( 'mobile' ),
);

$wgResourceModules['ext.wikihow.adminuserreview'] = array(
	'styles' => ['adminuserreview.css'],
	'scripts' => ['adminuserreview.js'],
	'localBasePath' => __DIR__ . "/admin" ,
	'remoteExtPath' => 'wikihow/userreview/admin',
	'position' => 'bottom',
	'targets' => ['desktop'],
	'messages' => [],
	'dependencies' => [
		'ext.wikihow.common_bottom',
		'jquery.ui.datepicker',
	]
);

$wgHooks['MakeGlobalVariablesScript'][] = array('UserReview::onMakeGlobalVariablesScript');
$wgHooks['ProcessArticleHTMLAfter'][] = array('UserReview::addIntroIcon');
$wgHooks['BeforeRenderPageActionsMobile'][] = ['UserReview::onBeforeRenderPageActionsMobile'];
