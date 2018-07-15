<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'user completed images patrol tool',
	'author' => 'Aaron G',
	'description' => 'special page to review user completion images for articles',
);

$wgSpecialPages['UCIPatrol'] = 'UCIPatrol';
$wgAutoloadClasses['UCIPatrol'] = dirname(__FILE__) . '/UCIPatrol.body.php';
$wgExtensionMessagesFiles['UCIPatrol'] = dirname(__FILE__) . '/UCIPatrol.i18n.php';
$wgExtensionMessagesFiles['UCIPatrolAliases'] = __DIR__ . '/UCIPatrol.alias.php';

$wgAutoloadClasses['MobileUCIPatrol'] = dirname(__FILE__) . '/MobileUCIPatrol.body.php';
$wgHooks["IsEligibleForMobileSpecial"][] = array("MobileUCIPatrol::onIsEligibleForMobileSpecial");

$wgLogTypes[] = 'ucipatrol';
$wgLogNames['ucipatrol'] = 'ucipatrol';
$wgLogHeaders['ucipatrol'] = 'ucipatrol';

$wgAvailableRights[] = 'ucipatrol';

$wgResourceModules['ext.wikihow.ucipatrol'] = array(
	'scripts' => 'ucipatrol.js',
	'styles' => array( 'ucipatrol.css' ),
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/ucipatrol',
	'messages' => array(),
	'position' => 'bottom',
	'targets' => array( 'mobile', 'desktop' ),
	'dependencies' => array('mediawiki.user')
);
$wgResourceModules['ext.wikihow.mobile.ucipatrol'] = $wgResourceModules['ext.wikihow.ucipatrol'];
$wgResourceModules['ext.wikihow.mobile.ucipatrol']['dependencies'] = array('mediawiki.user', 'mobile.wikihow', 'ext.wikihow.MobileToolCommon');
$wgResourceModules['ext.wikihow.mobile.ucipatrol']['styles'][] = 'mobileucipatrol.css';
$wgResourceModules['ext.wikihow.mobile.ucipatrol']['position'] = 'top';

/*
CREATE TABLE `image_votes` (
  `iv_pageid` int(8) unsigned NOT NULL,
  `iv_userid` int(8) NOT NULL,  
  `iv_vote` int(8) NOT NULL,
  `iv_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iv_pageid`,`iv_userid`)
);

CREATE TABLE `user_completed_images` (
  `uci_image_name` varchar(255) NOT NULL DEFAULT '',
  `uci_image_url` varchar(255) NOT NULL DEFAULT '',
  `uci_user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `uci_user_text` varchar(255) NOT NULL DEFAULT '',
  `uci_timestamp` varchar(14) NOT NULL DEFAULT '',
  `uci_article_id` int(8) unsigned NOT NULL DEFAULT '0',
  `uci_article_name` varchar(255) NOT NULL DEFAULT '',
  `uci_is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `uci_copyright_checked` tinyint(1) NOT NULL DEFAULT '0',
  `uci_copyright_error` tinyint(1) NOT NULL DEFAULT '0',
  `uci_copyright_matches` int(8) unsigned NOT NULL DEFAULT '0',
  `uci_copyright_violates` tinyint(1) NOT NULL DEFAULT '0',
  `uci_copyright_top_hits` blob,
  `uci_upvotes` int(8) DEFAULT '0',
  `uci_downvotes` int(8) DEFAULT '0',
  `uci_on_whitelist` tinyint(1) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `uci_image_name` (`uci_image_name`),
  KEY `uci_article_id` (`uci_article_id`),
  KEY `uci_article_name` (`uci_article_name`)
);
*/
