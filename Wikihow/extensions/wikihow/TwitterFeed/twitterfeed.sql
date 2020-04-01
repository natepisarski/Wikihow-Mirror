CREATE TABLE `twitterfeedaccounts` (
  `tws_username` varchar(16) DEFAULT NULL,
  `tws_token` varchar(255) DEFAULT NULL,
  `tws_verifier` varchar(255) DEFAULT NULL,
  `tws_secret` varchar(255) DEFAULT NULL,
  `tws_password` varchar(255) DEFAULT NULL,
  UNIQUE KEY `tws_username` (`tws_username`)
);

CREATE TABLE `twitterfeedcatgories` (
  `tfc_username` varchar(255) DEFAULT NULL,
  `tfc_category` varchar(255) NOT NULL DEFAULT '',
  UNIQUE KEY `name_cat` (`tfc_username`,`tfc_category`)
);

CREATE TABLE `twitterfeedlog` (
  `tfl_user` int(5) unsigned NOT NULL DEFAULT '0',
  `tfl_user_text` varchar(255) NOT NULL DEFAULT '',
  `tfl_message` varchar(140) DEFAULT NULL,
  `tfl_twitteraccount` varchar(16) DEFAULT NULL,
  `tfl_timestamp` varchar(14) NOT NULL DEFAULT ''
);

CREATE TABLE `twitterfeedusers` (
  `tfu_user` int(5) unsigned NOT NULL DEFAULT '0',
  `tfu_user_text` varchar(255) NOT NULL DEFAULT '',
  `tfu_token` varchar(255) DEFAULT NULL,
  `tfu_secret` varchar(255) DEFAULT NULL,
  `tfu_settings` text
);
