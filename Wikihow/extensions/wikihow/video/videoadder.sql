CREATE TABLE `videoadder` (
  `va_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `va_page` int(8) unsigned NOT NULL,
  `va_page_touched` varchar(14) NOT NULL DEFAULT '',
  `va_inuse` varchar(14) DEFAULT NULL,
  `va_skipped_accepted` tinyint(3) unsigned DEFAULT NULL,
  `va_template_ns` tinyint(3) unsigned DEFAULT NULL,
  `va_src` varchar(16) NOT NULL DEFAULT '',
  `va_vid_id` varchar(32) NOT NULL DEFAULT '',
  `va_user` int(8) unsigned DEFAULT NULL,
  `va_user_text` varchar(255) NOT NULL DEFAULT '',
  `va_timestamp` varchar(14) DEFAULT '',
  `va_page_counter` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`va_id`),
  UNIQUE KEY `va_page` (`va_page`)
);
