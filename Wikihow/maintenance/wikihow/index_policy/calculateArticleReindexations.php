<?php

/* New tables:

CREATE TABLE index_info_yesterday LIKE index_info;

CREATE TABLE `article_reindexed` (
  `ar_page` int(10) unsigned NOT NULL,
  `ar_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `ar_revision` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ar_page`)
);

*/

require_once __DIR__ . '/../WHMaintenance.php';

/**
 * Compares the data in the `index_info` table against yesterday's, in order
 * to calculate which articles became reindexed in the last 24 hours.
 */
class CalculateArticleReindexations extends WHMaintenance {

	protected $emailRecipients = 'alberto@wikihow.com, reuben@wikihow.com';

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Calculates which articles were reindexed in the last 24 hours";
	}

	public function execute() {
		parent::execute();
		try {
			$this->echo('Updating the article_reindexed table');
			$this->updateArticleReindexedTable();
			$this->echo('Updating the index_info_yesterday table');
			$this->updateIndexInfoYesterdayTable();
		} catch (Exception $e) {
			$this->fail("Did the schema of `index_info` change? Exception:\n---\n$e\n---");
		}
		$this->echo('Done');
	}

	private function updateArticleReindexedTable() {
		$removeArticlesThatAreNoLongerIndexed = <<<'EOD'
DELETE FROM article_reindexed
 WHERE ar_page NOT IN (SELECT ii_page FROM index_info WHERE ii_policy IN (1, 4))
    OR ar_page NOT IN (SELECT page_id FROM page WHERE page_namespace = 0 AND page_is_redirect = 0)
EOD;
		$upsertArticlesThatBecameIndexed = <<<'EOD'
REPLACE INTO article_reindexed
 SELECT ii_page, ii_timestamp, ii_revision
   FROM index_info
  WHERE ii_policy IN (1, 4)
    AND ii_page NOT IN (SELECT ii_page FROM index_info_yesterday WHERE ii_policy IN (1, 4))
    AND ii_page NOT IN (SELECT rc_cur_id FROM recentchanges WHERE rc_new = 1)
EOD;
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query($removeArticlesThatAreNoLongerIndexed);
		$dbw->query($upsertArticlesThatBecameIndexed);
	}

	private function updateIndexInfoYesterdayTable() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query('DELETE FROM index_info_yesterday');
		$dbw->query('INSERT INTO index_info_yesterday SELECT * FROM index_info');
	}

}

$maintClass = "CalculateArticleReindexations";
require_once RUN_MAINTENANCE_IF_MAIN;
