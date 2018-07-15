USE classify_titles;

--
-- Table structure for table `ctjobs`
--

CREATE TABLE `ctjobs` (
  `ct_id` int(11) NOT NULL AUTO_INCREMENT,
  `ct_status` int(1) DEFAULT NULL,
  `ct_filename` varbinary(255) DEFAULT NULL,
  `ct_error` longtext,
  `ct_lasttimestamp` varbinary(14) DEFAULT NULL,
  `ct_newjobs` int(1) DEFAULT NULL,
  `ct_jobname` varbinary(255) DEFAULT NULL,
  `ct_jobmessage` varbinary(255) DEFAULT NULL,
  `ct_user` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`ct_id`)
) CHARSET=utf8;

--
-- Table structure for table `ctresults`
--

CREATE TABLE `ctresults` (
  `ct_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ct_batchid` int(10) unsigned NOT NULL DEFAULT '0',
  `ct_text` varbinary(255) DEFAULT NULL,
  `ct_rest` blob,
  `ct_result` varchar(45) DEFAULT NULL,
  `ct_confidence` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`ct_id`),
  KEY `fetch` (`ct_batchid`)
) CHARSET=utf8;
