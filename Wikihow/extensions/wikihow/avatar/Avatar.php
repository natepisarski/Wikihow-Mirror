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
$wgAutoloadClasses['Avatar'] = dirname( __FILE__ ) . '/Avatar.body.php';
