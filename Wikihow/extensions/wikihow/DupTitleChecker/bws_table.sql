CREATE TABLE `bing_web_search` (
  `bws_id` int(11) NOT NULL AUTO_INCREMENT,
  `bws_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `bws_query` mediumblob NOT NULL,
  `bws_rank` int(2) NOT NULL DEFAULT '0',
  `bws_url` mediumblob NOT NULL,
  PRIMARY KEY (`bws_id`),
  KEY `text` (`bws_query`(20))
);
