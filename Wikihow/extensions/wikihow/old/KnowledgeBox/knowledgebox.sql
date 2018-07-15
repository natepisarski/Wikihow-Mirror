/*
 * Holds data submitted by users for the raw content solicitation tool.
 */
CREATE TABLE IF NOT EXISTS `knowledgebox_contents` (
    `kbc_id` int(10) unsigned NOT NULL auto_increment,          # Internal ID
    `kbc_aid` int(10) unsigned NOT NULL,                        # Article ID
    `kbc_user_id` int(10) unsigned NOT NULL default 0,
    `kbc_user_text` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',
    `kbc_content` text collate utf8_unicode_ci default NULL,
    `kbc_timestamp` varchar(14) collate utf8_unicode_ci NOT NULL,
    `kbc_checkout` varchar(14) collate utf8_unicode_ci NOT NULL default '',
    `kbc_checkout_user` int(10) unsigned NOT NULL default 0,
    `kbc_patrolled` tinyint(3) NOT NULL default 0,
    `kbc_email` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',

    PRIMARY KEY (`kbc_id`),
    UNIQUE KEY `kbc_id` (`kbc_id`),
    KEY `kbc_timestamp` (`kbc_timestamp`),
    KEY `kbc_email` (`kbc_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*
 * Provides the articles, topics (e.g. "jump") and phrases (e.g. "how to jump")
 * for the raw content solicitation tool.
 */
CREATE TABLE IF NOT EXISTS `knowledgebox_articles` (
    `kba_aid` int(10) unsigned NOT NULL,                        # Article ID
    `kba_topic` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',
    `kba_phrase` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',

    PRIMARY KEY (`kba_aid`),
    UNIQUE KEY `kba_aid` (`kba_aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
