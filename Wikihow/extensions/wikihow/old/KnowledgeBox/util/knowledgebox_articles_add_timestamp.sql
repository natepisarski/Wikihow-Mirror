ALTER TABLE knowledgebox_articles ADD COLUMN  kba_timestamp varchar(14) collate utf8_unicode_ci NOT NULL;
UPDATE knowledgebox_articles SET kba_timestamp="20150114000000";

