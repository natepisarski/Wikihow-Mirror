CREATE TABLE `sensitive_reason` (
  `sr_id` int(10) unsigned NOT NULL,
  `sr_name` varbinary(255) NOT NULL,
  `sr_enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`sr_id`)
);

CREATE TABLE `sensitive_article` (
  `sa_page_id` int(10) unsigned NOT NULL,
  `sa_reason_id` int(10) unsigned NOT NULL,
  `sa_rev_id` int(10) unsigned NOT NULL,
  `sa_user_id` int(10) unsigned NOT NULL,
  `sa_date` varchar(14) NOT NULL,
  PRIMARY KEY (`sa_page_id`,`sa_reason_id`)
);

/* Test data

INSERT INTO sensitive_reason (sr_id, sr_name, sr_enabled) VALUES
(1, 'Sex and nudity', 1),
(2, 'Mental health', 1),
(3, 'Troll and joke magnet', 1),
(4, 'Spam magnet', 1),
(5, 'Ethically questionable', 1),
(6, 'Not real', 1);

INSERT INTO sensitive_article (sa_page_id, sa_reason_id, sa_rev_id, sa_user_id, sa_date) VALUES
(175304, 2, 19895222, 2749020, '20161219010203'),
(175304, 4, 19895222, 2749020, '20161219010203'),
(175304, 6, 19895222, 2749020, '20161219010203');

*/
