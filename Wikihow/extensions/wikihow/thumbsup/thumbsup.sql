CREATE TABLE `thumbs` (
  `thumb_giver_id` int(10) unsigned NOT NULL,
  `thumb_giver_text` varchar(255) NOT NULL,
  `thumb_recipient_id` int(10) unsigned NOT NULL,
  `thumb_recipient_text` varchar(255) NOT NULL,
  `thumb_rev_id` int(8) unsigned NOT NULL,
  `thumb_page_id` int(8) unsigned NOT NULL DEFAULT '0',
  `thumb_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `thumb_notified` int(1) DEFAULT '0',
  `thumb_exclude` int(1) DEFAULT '0',
  PRIMARY KEY (`thumb_rev_id`,`thumb_giver_id`),
  KEY `thumb_page_id` (`thumb_page_id`),
  KEY `thumb_timestamp` (`thumb_timestamp`),
  KEY `thumb_recipient_id` (`thumb_recipient_id`),
  KEY `thumb_recipient_text` (`thumb_recipient_text`,`thumb_timestamp`)
);
