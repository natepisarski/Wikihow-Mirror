<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Admin Social Proof',
    'author' => 'Aaron',
    'description' => 'can import spreadsheets from gdrive for social proof',
);

$wgSpecialPages['AdminSocialProof'] = 'AdminSocialProof';
$wgAutoloadClasses['AdminSocialProof'] = dirname(__FILE__) . '/AdminSocialProof.body.php';
$wgExtensionMessagesFiles['AdminSocialProof'] = dirname(__FILE__) . '/AdminSocialProof.i18n.php';
$wgResourceModules['ext.wikihow.adminsocialproof'] = array(
	'scripts' => array( 'adminsocialproof.js', ),
	'styles' => array( 'adminsocialproof.css' ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/socialproof',
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
);


$wgSpecialPages['AdminExpertNameChange'] = 'AdminExpertNameChange';
$wgAutoloadClasses['AdminExpertNameChange'] = dirname(__FILE__) . '/AdminExpertNameChange.body.php';
$wgResourceModules['ext.wikihow.adminexpertnamechange'] = array(
	'scripts' => array( 'adminexpertnamechange.js', ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/socialproof',
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
);

/*
CREATE TABLE `article_verifier` (
  `av_id` int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `av_name` varchar(255) binary NOT NULL,
  `av_info` blob NOT NULL,
  UNIQUE KEY `av_name` (`av_name`)
);
*/
/* for historical data
CREATE TABLE `verified_revision` (
  `vr_page_id` int(10) unsigned NOT NULL,
  `vr_rev_id` int(10) unsigned NOT NULL,
  `vr_article_verifier_id` int(10) unsigned NOT NULL,
  PRIMARY KEY(`vr_page_id`, `vr_rev_id`, `vr_article_verifier_id`),
  KEY(`vr_page_id`, `vr_article_verifier_id`)
);
*/

