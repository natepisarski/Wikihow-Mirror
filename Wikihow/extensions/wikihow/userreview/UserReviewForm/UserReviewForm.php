<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'UserReviewForm',
	'author' => 'Wilson Restrepo',
	'description' => 'Tool for embedding youtube videos in a page',
);

$wgSpecialPages['UserReviewForm'] = 'UserReviewForm';
$wgAutoloadClasses['UserReviewForm'] = __DIR__ . '/SpecialUserReviewForm.php';
$wgAutoloadClasses['UserReviewImporter'] = __DIR__ . '/../UserReviewImporter.class.php';
$wgAutoloadClasses['UserReview'] = __DIR__ . '/../UserReview.class.php';
$wgAutoloadClasses['UserReviewTool'] = __DIR__ . '/../UserReviewTool.body.php';
$wgAutoloadClasses['SubmittedUserReview'] = __DIR__ . '/model/SubmittedUserReview.php';
$wgAutoloadClasses['URDB'] = __DIR__ . '/model/URDB.php';

$wgHooks['UnitTestsList'][] = array( 'SubmittedUserReview::onUnitTestsList');

$wgExtensionMessagesFiles['UserReviewForm'] = __DIR__ . '/UserReviewForm.i18n.php';

$wgResourceModules['ext.wikihow.UserReviewForm'] = [
    'localBasePath' => __DIR__ . '/',
    'scripts' => [ 'UserReviewForm.js'],
    'styles' => [ 'UserReviewFormDesktop.css', 'UserReviewForm.css'],
    'targets' => [ 'desktop' ],
    'remoteExtPath' => 'wikihow/userreview/UserReviewForm',
    'position' => 'top',
    'dependencies' => [
        'ext.wikihow.magnificpopup',
        'ext.wikihow.common_top',
        'ext.wikihow.common_bottom',
        'ext.wikihow.socialauth',
        'ext.wikihow.sociallogin.buttons'
    ]
 ];

$wgResourceModules['ext.wikihow.UserReviewForm.mobile'] = [
    'localBasePath' => __DIR__ . '/',
    'scripts' => [ 'UserReviewForm.js'],
    'styles' => [ 'UserReviewFormMobile.css', 'UserReviewForm.css'],
    'targets' => [ 'mobile' ],
    'remoteExtPath' => 'wikihow/userreview/UserReviewForm',
    'position' => 'top',
    'dependencies' => [
        'ext.wikihow.socialauth',
        'ext.wikihow.magnificpopup',
        'ext.wikihow.common_top',
        'ext.wikihow.common_bottom',
        'ext.wikihow.sociallogin.buttons'
    ]
];

