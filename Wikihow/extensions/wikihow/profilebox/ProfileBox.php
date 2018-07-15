<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ProfileBox',
    'author' => 'Vu <vu@wikihow.com>',
    'description' => 'Magic word used in profile to display user data and stats',
);

$wgSpecialPages['ProfileBox'] = 'ProfileBox';
$wgAutoloadClasses['ProfileBox'] = dirname( __FILE__ ) . '/ProfileBox.body.php';
$wgAutoloadClasses['ProfileStats'] = dirname( __FILE__ ) . '/ProfileBox.body.php';
$wgExtensionMessagesFiles['ProfileBox'] = dirname( __FILE__ ) . "/ProfileBox.i18n.php";

$wgHooks['AddNewAccount'][] = 'ProfileBox::onInitProfileBox';

$wgResourceModules['ext.wikihow.profile_box'] = array(
    'styles' => array('profilebox.css'),
    'scripts' => array('profilebox.js'),
    'messages' => array(
      'profilebox_name',
      'pb-viewmore',
      'pb-viewless'
    ),
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/profilebox',
    'position' => 'top',
    'targets' => array('desktop'),
    'dependencies' => array('ext.wikihow.common_top'),
);

/*
CREATE TABLE `profilebox` (
  `pb_user` int(8) unsigned NOT NULL DEFAULT '0',
  `pb_started` int(11) unsigned NOT NULL DEFAULT '0',
  `pb_edits` int(11) unsigned NOT NULL DEFAULT '0',
  `pb_patrolled` int(11) unsigned NOT NULL DEFAULT '0',
  `pb_viewership` int(10) unsigned NOT NULL DEFAULT '0',
  `pb_lastUpdated` varchar(14) DEFAULT NULL,
  `pb_thumbs_given` int(11) NOT NULL DEFAULT '0',
  `pb_thumbs_received` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pb_user`)
);
*/
