<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Admin Verify Review',
    'author' => 'Aaron',
    'description' => 'review pages that have been expert verified that have since had edits',
);

$wgExtensionMessagesFiles['AdminVerifyReview'] = dirname(__FILE__) . '/SocialProof.i18n.php';
$wgExtensionMessagesFiles['AdminVerifyRewviewAliases'] = __DIR__ . '/AdminVerifyReview.alias.php';
$wgSpecialPages['AdminVerifyReview'] = 'AdminVerifyReview';
$wgAutoloadClasses['AdminVerifyReview'] = dirname(__FILE__) . '/AdminVerifyReview.body.php';

$wgResourceModules['ext.wikihow.adminverifyreview'] = array(
	'scripts' => array( 'adminverifyreview.js', ),
	'styles' => array( 'adminverifyreview.css' ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/socialproof',
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
);

/*
 * sql table used by this tool
 * this tool will use a table that stores a page id and a revision number and whether this
 * key of two values has been reviewed/cleared or not
 *
 * CREATE TABLE `article_verify_review` (
 * `avr_page_id` int(10) unsigned NOT NULL,
 * `avr_rev_id` int(10) unsigned NOT NULL,
 * `avr_cleared` tinyint(2) unsigned NOT NULL DEFAULT '0',
 * PRIMARY KEY (`avr_page_id`, `avr_rev_id`),
 * KEY `avr_rev_id` (`avr_rev_id`)
 * );
 *
 */


