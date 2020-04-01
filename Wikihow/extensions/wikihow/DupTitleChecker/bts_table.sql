CREATE TABLE `bing_title_search` (
  `bts_id` int(11) NOT NULL AUTO_INCREMENT,
  `bts_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `bts_query` mediumblob,
  `bts_final` int(10) NOT NULL DEFAULT '0',
  `bts_match` blob,
  PRIMARY KEY (`bts_id`),
  KEY `text` (`bts_query`(20))
);
