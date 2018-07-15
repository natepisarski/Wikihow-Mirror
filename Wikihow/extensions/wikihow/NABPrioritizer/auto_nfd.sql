CREATE TABLE `auto_nfd` (
  `an_page_title` varchar(255) default NULL,
  `an_page_id` int(11) NOT NULL default '0',
  `an_revision_id` int(11) NOT NULL,
  `an_day` date NOT NULL default '0000-00-00',
  `an_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `an_dscore` decimal(5,2) default NULL,
  `an_ndscore` decimal(5,2) default NULL,
  `an_sscore` decimal(5,2) default NULL,
  `an_reason` varchar(3) default NULL,
  `an_algorithm` int(11) NOT NULL default '0',
  PRIMARY KEY  (`an_page_id`,`an_algorithm`),
  INDEX idx_timestamp(an_timestamp) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
