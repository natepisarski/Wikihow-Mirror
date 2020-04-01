CREATE TABLE `newarticlepatrol` (
  `nap_page` int(10) unsigned NOT NULL DEFAULT '0',
  `nap_patrolled` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `nap_user_co` int(10) unsigned NOT NULL DEFAULT '0',
  `nap_timestamp_co` varchar(14) NOT NULL DEFAULT '',
  `nap_user_ci` int(10) unsigned NOT NULL DEFAULT '0',
  `nap_timestamp_ci` varchar(14) NOT NULL DEFAULT '',
  `nap_timestamp` varchar(14) NOT NULL DEFAULT '',
  `nap_newbie` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `nap_demote` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `nap_atlas_score` tinyint(4) NOT NULL DEFAULT '-1',
  UNIQUE KEY `nap_page` (`nap_page`),
  KEY `nap_timestamp` (`nap_timestamp`)
);

CREATE TABLE `nab_atlas` (
  `na_page_id` int(10) unsigned NOT NULL,
  `na_atlas_score` tinyint(4) NOT NULL DEFAULT '-1',
  `na_atlas_revision` int(10) unsigned NOT NULL DEFAULT '0',
  `na_atlas_score_updated` varbinary(14) NOT NULL DEFAULT '',
  PRIMARY KEY (`na_page_id`),
  KEY `h_atlas_score` (`na_atlas_score`)
);
