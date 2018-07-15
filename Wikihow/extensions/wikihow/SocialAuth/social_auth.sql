/* `social_auth` table in the wiki_shared database */

CREATE TABLE `social_auth` (
  `sa_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sa_wh_user_id` int(10) unsigned NOT NULL,
  `sa_external_id` varbinary(255) NOT NULL,
  `sa_type` varbinary(31) NOT NULL,
  PRIMARY KEY (`sa_id`),
  UNIQUE KEY `sa_external_id` (`sa_external_id`,`sa_type`),
  UNIQUE KEY `sa_wh_user_id` (`sa_wh_user_id`,`sa_type`)
);
