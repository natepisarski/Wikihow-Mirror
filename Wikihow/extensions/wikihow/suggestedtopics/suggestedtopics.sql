CREATE TABLE `suggested_links` (
  `sl_sugg` int(8) unsigned NOT NULL,
  `sl_page` int(8) unsigned NOT NULL,
  `sl_sort` double(5,4) unsigned DEFAULT '0.0000',
  KEY `sl_sugg_2` (`sl_sugg`,`sl_page`),
  KEY `sl_page` (`sl_page`)
);
 
CREATE TABLE `suggested_notify` (
  `sn_page` int(8) unsigned NOT NULL,
  `sn_notify` varchar(255) DEFAULT '',
  `sn_timestamp` varchar(14) DEFAULT ''
);
 
CREATE TABLE `suggested_titles` (
  `st_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `st_title` varchar(255) NOT NULL DEFAULT '',
  `st_key` varchar(255) NOT NULL DEFAULT '',
  `st_used` tinyint(4) DEFAULT '0',
  `st_hastraffic_v` varchar(32) DEFAULT '',
  `st_sv` tinyint(4) DEFAULT '-1',
  `st_created` varchar(14) NOT NULL DEFAULT '',
  `st_source` varchar(4) DEFAULT '',
  `st_group` tinyint(3) unsigned DEFAULT '0',
  `st_patrolled` tinyint(3) unsigned DEFAULT '0',
  `st_category` varchar(255) DEFAULT '',
  `st_isrequest` tinyint(3) unsigned DEFAULT '0',
  `st_suggested` varchar(14) DEFAULT '',
  `st_notify` varchar(255) DEFAULT '',
  `st_user` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `st_user_text` varchar(255) NOT NULL DEFAULT '',
  `st_random` double unsigned NOT NULL DEFAULT '0',
  `st_traffic_volume` tinyint(4) DEFAULT '-1',
  PRIMARY KEY (`st_id`),
  UNIQUE KEY `st_title` (`st_title`),
  KEY `st_key` (`st_key`),
  KEY `st_group` (`st_group`),
  KEY `st_used` (`st_used`,`st_patrolled`,`st_group`,`st_category`),
  KEY `st_random` (`st_random`),
  KEY `suggested_recommendations` (`st_category`,`st_used`,`st_traffic_volume`,`st_random`)
);
