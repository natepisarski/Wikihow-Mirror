CREATE TABLE `thumb_ratings` (
  `tr_page_id` int(8) unsigned NOT NULL,
  `tr_hash` varchar(32) NOT NULL DEFAULT '',
  `tr_up` int(10) unsigned NOT NULL DEFAULT '0',
  `tr_down` int(10) unsigned NOT NULL DEFAULT '0',
  `tr_type` tinyint(3) unsigned NOT NULL,
  `tr_last_ranked` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`tr_page_id`,`tr_hash`),
  KEY `tr_hash` (`tr_hash`),
  KEY `tr_last_ranked` (`tr_last_ranked`)
);
