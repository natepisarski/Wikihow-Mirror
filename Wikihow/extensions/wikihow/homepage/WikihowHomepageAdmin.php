<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['WikihowHomepageAdmin'] = 'WikihowHomepageAdmin';
$wgAutoloadClasses['WikihowHomepageAdmin'] = __DIR__ . '/WikihowHomepageAdmin.body.php';

$wgResourceModules['ext.wikihow.WikihowHomepageAdmin'] = [
	'scripts' => [
		'../common/jquery-ui-1.12.1/jquery-ui.min.js',
		'wikihowhomepageadmin.js',
	],
	'styles' => [ 'wikihowhomepageadmin.css' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/homepage',
	'targets' => ['desktop', 'mobile'],
];

$wgHooks['FileUpload'][] = array('WikihowHomepageAdmin::onFileUpload');

/*******
 *

CREATE TABLE IF NOT EXISTS `homepage` (
`hp_id` int(10) unsigned NOT NULL auto_increment,
`hp_page` int(10) unsigned NOT NULL,
`hp_image` text collate utf8_unicode_ci default NULL,
`hp_active` TINYINT (3) NOT NULL DEFAULT '0',
PRIMARY KEY  (`hp_id`),
UNIQUE KEY `ama_id` (`hp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

 *
 ******/
