CREATE TABLE `quizzes` (
  `quiz_name` varchar(255) NOT NULL,
  `quiz_active` tinyint(1) NOT NULL DEFAULT '0',
  `quiz_icon` varchar(255) NOT NULL DEFAULT '',
  `quiz_data` longtext NOT NULL,
  `quiz_stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`quiz_name`)
);

CREATE TABLE `quiz_links` (
  `ql_id` int(11) NOT NULL AUTO_INCREMENT,
  `ql_page` int(10) DEFAULT NULL,
  `ql_name` varchar(255) NOT NULL,
  PRIMARY KEY (`ql_id`)
);
