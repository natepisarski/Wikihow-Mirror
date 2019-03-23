<?php

// Disable KnowledgeBox articles with too many submissions
require_once(__DIR__ . '/../Maintenance.php');
class PruneKnowledgeBoxArticles extends Maintenance {
	const MAINTENANCE_MODE = false;

	public function __construct() {
		parent::__construct();
		$this->addOption(
			'threshold',
			'Maximum number of submissions',
			true,
			true,
			't'
		);
	}

	public function execute() {
		if (self::MAINTENANCE_MODE) {
			print "KnowledgeBox is currently in maintenance mode. pruneKnowledgeBoxArticles will not run\n";
			return;
		}
		$dbr = wfGetDB(DB_REPLICA);

		$threshold = $this->getOption('threshold');
		$thresholdSafe = $dbr->addQuotes($threshold);
		$ts = wfTimestampNow();
		$tsSafe = $dbr->addQuotes($ts);

		// George 2015-07-01: Added a whitelist of articles that will NOT be pruned,
		// per Alissa and John's request.
		// TODO: Make sure to remove after their testing is done.
		$whitelist = array(
			1384053,1695400,1420038,14973,2331978,2904213,403142,1558104,
			1454214,209415,14093,1789038,286729,164603,366730
		);

		$whitelist = '(' . implode(',', array_map(array($dbr, 'addQuotes'), $whitelist)) . ')';

		$innerSelectSql = <<<INNERSQL
    SELECT
      `kbc_kbid`,
      COUNT(`kbc_kbid`) AS `subcount`
    FROM
      `knowledgebox_contents`
    GROUP BY
      `kbc_kbid`
INNERSQL;

		$where = <<<WHERESQL
  `kbc`.`subcount` > $thresholdSafe AND
  `kba`.`kba_active` = 1 AND
  `kba`.`kba_aid` NOT IN $whitelist;
WHERESQL;

		$selectSql = <<<SELECTSQL
SELECT
  `kba`.`kba_id`
FROM
  `knowledgebox_articles` `kba`
  LEFT JOIN (
$innerSelectSql
  ) `kbc` ON `kbc`.`kbc_kbid` = `kba`.`kba_id`
WHERE
$where
SELECTSQL;
		
		$updateSql = <<<UPDATESQL
UPDATE
  `knowledgebox_articles` `kba`
  LEFT JOIN (
$innerSelectSql
  ) `kbc` ON `kbc`.`kbc_kbid` = `kba`.`kba_id`
SET
  `kba`.`kba_active` = 0,
  `kba`.`kba_modified` = $tsSafe
WHERE
$where
UPDATESQL;

		// Fetch the IDs of rows to be disabled
		$res = $dbr->query($selectSql);

		// ... then disable them.
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query($updateSql);

		$ids = array();

		foreach ($res as $row) {
			$ids[] = $row->kba_id;
		}

		print "Pruned " . count($ids) . " topics with >$threshold submissions on $ts\n"
			. implode("\n", preg_filter('/^/', '    ', $ids)) . "\n"
			. str_repeat("=", 48) . "\n";
	}
}

$maintClass = 'PruneKnowledgeBoxArticles';
require_once(RUN_MAINTENANCE_IF_MAIN);

