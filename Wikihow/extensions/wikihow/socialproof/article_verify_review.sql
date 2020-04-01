CREATE TABLE `article_verify_review` (
  `avr_page_id` int(10) unsigned NOT NULL,
  `avr_rev_id` int(10) unsigned NOT NULL,
  `avr_cleared` tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`avr_page_id`, `avr_rev_id`),
  KEY `avr_rev_id` (`avr_rev_id`)
);
