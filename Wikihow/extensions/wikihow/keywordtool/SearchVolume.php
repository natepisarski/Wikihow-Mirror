<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['SearchVolume'] = dirname(__FILE__) . '/SearchVolume.class.php';
$wgHooks['PageContentInsertComplete'][] = ['SearchVolume::onPageContentInsertComplete'];

/****
CREATE TABLE `search_volume` (
	`sv_page_id` int(10) unsigned NOT NULL,
	`sv_volume` int(10) NOT NULL default -1,
	UNIQUE KEY `sv_page_id` (`sv_page_id`),
	KEY `sv_volume` (`sv_volume`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
****/