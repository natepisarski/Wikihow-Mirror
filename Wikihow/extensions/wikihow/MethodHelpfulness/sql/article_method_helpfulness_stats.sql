CREATE TABLE IF NOT EXISTS `article_method_helpfulness_stats` (
	`amhs_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, /* Internal ID */

	`amhs_am_id` INT(11) UNSIGNED NOT NULL DEFAULT 0, /* article_method foreign key */

	`amhs_source` VARBINARY(64) NOT NULL DEFAULT '', /* Source of input */
	`amhs_vote` VARBINARY(64) NOT NULL DEFAULT '', /* vote_yes, vote_no, not_selected, ... */

	`amhs_count` INT(11) UNSIGNED NOT NULL DEFAULT 0, /* Amount of entries */

	PRIMARY KEY (`amhs_id`),
	UNIQUE KEY `amhs_unique` (
		`amhs_am_id`,
		`amhs_source`,
		`amhs_vote`
	),
	FOREIGN KEY (`amhs_am_id`)
		REFERENCES article_method(`am_id`)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
