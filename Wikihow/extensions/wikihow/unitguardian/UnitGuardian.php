<?php
if ( !defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Unit Guardian',
	'author' => 'Bebeth Steudel',
	'description' => 'Guardian Tool for Unit Conversions',
);

$wgSpecialPages['UnitGuardian'] = 'UnitGuardian';
$wgAutoloadClasses['UnitGuardian'] = dirname(__FILE__) . '/UnitGuardian.body.php';
$wgExtensionMessagesFiles['UnitGuardian'] = dirname(__FILE__) . '/UnitGuardian.i18n.php';
$wgAutoloadClasses['UnitConverter'] = dirname(__FILE__) . '/UnitConverter.class.php';
$wgSpecialPages['AdminUnitGuardian'] = 'AdminUnitGuardian';
$wgAutoloadClasses['AdminUnitGuardian'] = dirname(__FILE__) . '/UnitGuardian.body.php';
$wgAutoloadClasses['UnitGuardianContents'] = dirname(__FILE__) . '/UnitGuardian.body.php';

$wgLogTypes[] = 'unitguardian';
$wgLogNames['unitguardian'] = 'unitguardian';
$wgLogHeaders['unitguardian'] = 'unitguardian_log';

$wgHooks["IsEligibleForMobileSpecial"][] = array("UnitGuardian::onIsEligibleForMobileSpecial");
$wgHooks["PageContentSaveComplete"][] = array("UnitGuardian::onPageContentSaveComplete");
$wgHooks['ArticleDelete'][] = array('UnitGuardian::onArticleDelete');

$wgResourceModules['ext.wikihow.mobile.unitguardian'] = array(
	'scripts' => 'unitguardian.js',
	'styles' => 'unitguardian.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/unitguardian',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
	'messages' => array(
		'ug-yes',
		'ug-no',
		'ug-unsure',
		'ug-waiting-initial-heading',
		'ug-waiting-initial-sub',
		'ug-waiting-yes-heading',
		'ug-waiting-yes-sub',
		'ug-waiting-maybe-heading',
		'ug-waiting-maybe-sub',
		'ug-waiting-no-heading',
		'ug-waiting-no-sub',
	),
	'dependencies' =>  array('mobile.wikihow', 'ext.wikihow.MobileToolCommon'),
);




/**********
CREATE TABLE `unitguardian` (
	`ug_page` int(8) unsigned NOT NULL,
	`ug_dirty` int(2) unsigned NOT NULL default 0,
	`ug_whitelist` int(2) unsigned NOT NULL default 0,
	UNIQUE KEY (`ug_page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `unitguardian_conversions` (
	`ugc_id` int(8) unsigned NOT NULL auto_increment,
	`ugc_page` int (8) unsigned NOT NULL,
	`ugc_hash` varchar(32) NOT NULL,
	`ugc_original` varchar(255) NOT NULL,
	`ugc_template` varchar(255) NOT NULL,
	`ugc_converted` varchar (255) NOT NULL,
	`ugc_dirty` int(2) unsigned NOT NULL default 0,
	`ugc_up` int(2) unsigned NOT NULL default 0,
	`ugc_down` int(2) unsigned NOT NULL default 0 ,
	`ugc_resolved` int(2) unsigned NOT NULL default 0,
	PRIMARY KEY  (`ugc_id`),
	KEY `ug_page` (`ugc_page`),
	UNIQUE KEY `ug_hash_page` (`ugc_page`, `ugc_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
**********/
