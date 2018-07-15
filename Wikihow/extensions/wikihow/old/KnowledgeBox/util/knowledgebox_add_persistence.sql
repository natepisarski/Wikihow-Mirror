ALTER TABLE
	knowledgebox_articles
ADD COLUMN
	kba_active tinyint unsigned NOT NULL default 0,
ADD COLUMN
	kba_modified varchar(14) collate utf8_unicode_ci NOT NULL;

UPDATE
	knowledgebox_articles
SET
	kba_active=1,
	kba_modified=kba_timestamp;

INSERT IGNORE INTO
	knowledgebox_articles (
		kba_aid,
		kba_topic,
		kba_phrase,
		kba_timestamp,
		kba_active,
		kba_modified
	)
SELECT
	kbc.aid,
	"N/A",
	"N/A",
	kbc.oldest,
	0,
	kbc.oldest
FROM (
	SELECT
		DISTINCT(kbc_aid) aid,
		MIN(kbc_timestamp) oldest
	FROM
		knowledgebox_contents
	LEFT JOIN
		knowledgebox_articles
	ON
		kba_aid=kbc_aid
	WHERE
		kba_aid IS NULL
	GROUP BY
		kbc_aid
	) kbc;

