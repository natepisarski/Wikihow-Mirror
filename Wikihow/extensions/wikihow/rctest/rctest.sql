CREATE TABLE `rctest_quizzes` (
  `rq_id` int(11) NOT NULL AUTO_INCREMENT,
  `rq_page_id` int(10) unsigned NOT NULL,
  `rq_rev_old` int(10) unsigned NOT NULL,
  `rq_rev_new` int(10) unsigned NOT NULL,
  `rq_ideal_responses` varchar(50) DEFAULT NULL,
  `rq_acceptable_responses` varchar(50) DEFAULT NULL,
  `rq_incorrect_responses` varchar(50) DEFAULT NULL,
  `rq_explanation` text NOT NULL,
  `rq_coaching` text NOT NULL,
  `rq_difficulty` int(10) unsigned NOT NULL,
  `rq_author` varchar(255) DEFAULT NULL,
  `rq_deleted` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rq_id`)
);
 
CREATE TABLE `rctest_responses` (
  `rr_id` tinyint(4) DEFAULT NULL,
  `rr_response_button` varchar(50) DEFAULT NULL
);
 
CREATE TABLE `rctest_scores` (
  `rs_user_id` int(10) unsigned NOT NULL,
  `rs_user_name` varchar(255) NOT NULL,
  `rs_quiz_id` int(11) NOT NULL,
  `rs_correct` int(1) NOT NULL,
  `rs_response` tinyint(4) NOT NULL,
  `rs_timestamp` varchar(14) NOT NULL,
  PRIMARY KEY (`rs_user_id`,`rs_quiz_id`),
  KEY `rs_timestamp` (`rs_timestamp`)
);
 
CREATE TABLE `rctest_users` (
  `ru_user_id` int(10) unsigned NOT NULL,
  `ru_user_name` varchar(255) NOT NULL,
  `ru_base_patrol_count` mediumint(8) unsigned NOT NULL,
  `ru_quiz_ids` text,
  `ru_next_test_patrol_count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ru_user_id`)
);
