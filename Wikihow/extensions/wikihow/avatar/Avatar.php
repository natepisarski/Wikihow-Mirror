<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Avatar',
    'author' => 'Vu <vu@wikihow.com>',
    'description' => 'Avatar profile picture for user page',
);

/*
 * schema:
CREATE TABLE `avatar` (
  `av_user` int(10) unsigned NOT NULL DEFAULT '0',
  `av_image` varchar(255) NOT NULL DEFAULT '',
  `av_patrol` tinyint(2) NOT NULL DEFAULT '0',
  `av_rejectReason` varchar(255) NOT NULL DEFAULT '',
  `av_patrolledBy` int(10) unsigned NOT NULL DEFAULT '0',
  `av_patrolledDate` varchar(14) NOT NULL DEFAULT '',
  `av_dateAdded` varchar(14) NOT NULL DEFAULT '',
  PRIMARY KEY (`av_user`)
);
 */

$wgSpecialPages['Avatar'] = 'Avatar';
$wgAutoloadClasses['Avatar'] = __DIR__ . '/Avatar.body.php';

$wgResourceModules['ext.wikihow.avatar_styles'] = [
	'styles' => [ 'avatar.css' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/avatar',
	'targets' => [ 'desktop', 'mobile' ],
];

$wgResourceModules['ext.wikihow.avatar'] = [
	'scripts' => [
		'../common/jquery.md5.js',
		'avatar.js',
	],
	'dependencies' => ['jquery', 'ext.wikihow.common_bottom'],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/avatar',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.avatar_cropper.styles'] = [
	'styles' => [ 'cropper.css' ],
	'localBasePath' => __DIR__ . '/../common/cropper',
	'remoteExtPath' => 'wikihow/common/cropper',
	'targets' => [ 'desktop', 'mobile' ]
];
