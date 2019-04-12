<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['UserReviewImporter'] = __DIR__ . '/UserReviewImporter.class.php';
$wgAutoloadClasses['UserReviewTool'] = __DIR__ . '/UserReviewTool.body.php';
$wgAutoloadClasses['UserReview'] = __DIR__ . '/UserReview.class.php';
$wgAutoloadClasses['AdminUserReview'] = __DIR__ . '/admin/AdminUserReview.body.php';
$wgExtensionMessagesFiles['UserReview'] = __DIR__ . '/UserReview.i18n.php';
$wgExtensionMessagesFiles['AdminUserReview'] = __DIR__ . '/admin/AdminUserReview.i18n.php';

$wgSpecialPages['UserReviewTool'] = 'UserReviewTool';
$wgSpecialPages['UserReviewImporter'] = 'UserReviewImporter';
$wgSpecialPages['AdminUserReview'] = 'AdminUserReview';

$wgHooks['BeforePageDisplay'][] = 'UserReview::onBeforePageDisplay';
$wgHooks['PicturePatrolResolved'][] = 'UserReview::handlePicturePatrol';
$wgHooks['SensitiveArticleEdited'][] = 'UserReview::handSensitiveArticleEdit';

$wgResourceModules['ext.wikihow.userreviewtool'] = array(
	'scripts' => array('userreviewtool.js'),
	'styles' => array('userreviewtool.css'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/userreview',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.wikihow.userreviewimporter'] = array(
	'scripts' => array('userreviewimporter.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/userreview',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
);

$wgResourceModules['ext.wikihow.userreview'] = array(
	'scripts' => array('userreview.js'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/userreview',
	'position' => 'top',
	'targets' => array( 'desktop' ),
);

$wgResourceModules['mobile.wikihow.userreview'] = array(
	'scripts' => array('userreview.js'),
	'styles' => array('userreview_mobile.css'),
	'localBasePath' => __DIR__ . '/',
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
$wgHooks['BylineStamp'][] = 'UserReview::setBylineInfo';
