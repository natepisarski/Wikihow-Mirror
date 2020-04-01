CREATE TABLE IF NOT EXISTS `titus_query_vault` (
	`qv_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, /* Internal ID */
	`qv_user` INT(11) UNSIGNED NOT NULL, /* User ID */
	`qv_user_text` VARBINARY(255) NOT NULL DEFAULT '', /* Username */
	`qv_name` VARBINARY(255) NOT NULL DEFAULT '', /* Name of query */
	`qv_description` TEXT, /* Optional description */
	`qv_timestamp` VARBINARY(14) NOT NULL DEFAULT '', /* MW timestamp */
	`qv_query` TEXT NOT NULL, /* The query */

	PRIMARY KEY (`qv_id`),
	UNIQUE KEY `qv_repr` (
		`qv_user_text`,
		`qv_name`
	),
	KEY `user_id` (`qv_user`),
	KEY `user_text` (`qv_user_text`),
	KEY `timestamp` (`qv_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

