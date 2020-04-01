CREATE TABLE `socialproof_stats` (
  `sps_page_id` varchar(255) NOT NULL,
  `sps_action` varchar(255) NOT NULL,
  `sps_expert_name` varchar(255) NOT NULL,
  `sps_target` varchar(255) NOT NULL,
  `sps_click_count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`sps_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1