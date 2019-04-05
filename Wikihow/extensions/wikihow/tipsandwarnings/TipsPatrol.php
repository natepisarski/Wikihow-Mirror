<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Tips Guardian Tool',
	'author' => 'Bebeth Steudel',
	'description' => '',
);

$wgSpecialPages['TipsPatrol'] = 'TipsPatrol';
$wgAutoloadClasses['TipsPatrol'] = __DIR__ . '/TipsPatrol.body.php';
$wgExtensionMessagesFiles['TipsPatrol'] = __DIR__ . '/TipsPatrol.i18n.php';
$wgExtensionMessagesFiles['TipsPatrolAliases'] = __DIR__ . '/TipsPatrol.alias.php';

$wgLogTypes[] = 'newtips';
$wgLogNames['newtips'] = 'newtips';
$wgLogHeaders['newtips'] = 'newtips';

$wgResourceModules['ext.wikihow.tips_patrol'] = [
    'styles' => ['tipspatrol.css'],
    'scripts' => ['tipspatrol.js'],
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/tipsandwarnings',
    'position' => 'top',
    'targets' => ['desktop', 'mobile'],
    'dependencies' => ['ext.wikihow.common_top'],
];

/*****

 CREATE TABLE IF NOT EXISTS `tipsandwarnings` (
   `tw_id` int(10) unsigned NOT NULL auto_increment,
   `tw_page` int(10) unsigned NOT NULL,
   `tw_tip` varchar(200) collate utf8_unicode_ci default NULL,
   `tw_user` int(5) NOT NULL default 0,
   `tw_checkout` varchar(14) collate utf8_unicode_ci NOT NULL,
   `tw_checkout_user` int(5) NOT NULL,
   PRIMARY KEY  (`tw_id`),
   UNIQUE KEY `tw_id` (`tw_id`),
   UNIQUE KEY `tw_page` (`tw_page`)
 ) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE IF NOT EXISTS `tipsandwarnings_log` (
  twl_id int(10) unsigned DEFAULT NOT NULL AUTO_INCREMENT,
  `tw_id` int(10) unsigned default NULL,
  `tw_page` int(10) unsigned NOT NULL,
  `tw_tip` text collate utf8_unicode_ci,
  `tw_user` int(5) NOT NULL default '0',
  `tw_checkout` varchar(14) collate utf8_unicode_ci NOT NULL,
  `tw_checkout_user` int(5) NOT NULL,
  `tw_timestamp` varchar(14) collate utf8_unicode_ci NOT NULL default '',
  `tw_action` tinyint(3) unsigned NOT NULL default '0',
  `tw_rev_this` int(8) DEFAULT NULL,
  `tw_qc_id` int(8) DEFAULT NULL,
  PRIMARY KEY (twl_id),
  KEY `tw_timestamp` (`tw_timestamp`),
  KEY `tw_id` (`tw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

*****/
