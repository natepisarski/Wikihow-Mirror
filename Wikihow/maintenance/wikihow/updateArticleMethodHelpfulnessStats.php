<?php

define('WH_USE_BACKUP_DB', true);

require_once __DIR__ . '/../Maintenance.php';

class UpdateArticleMethodHelpfulnessStats extends Maintenance {
	const FORCE_DRY_RUN = false;

	// Default days from which to gather data
	const DEFAULT_FROM_DAY = '-1 day';
	const DEFAULT_DAYS = 1;

	// If true, do not perform update and print to stdout instead
	private $dry_run = false;

	public function __construct() {
		$this->mDescription =
			'Update aggregated statistics for the Method Helpfulness display table';

		$this->addOption(
			'dryrun', // long form
			'Perform a dry run, and print results to stdout. Do not update actual table',
			false, // is option required?
			false, // does option take argument?
			'd'
		);

		$this->addOption(
			'clear',
			'Delete all rows in aggregation table before updating (overrides -r)',
			false,
			false,
			'c'
		);

		$this->addOption(
			'replace',
			'Replace existing rows instead of updating count',
			false,
			false,
			'r'
		);

		$this->addOption(
			'all',
			'Perform update for all data in method_helpfulness (overrides -d and -e, forces -r and -c)',
			false,
			false,
			'a'
		);

		$this->addOption(
			'all-except-today',
			'Perform update for all data in method_helpfulness except current date (overrides -d, forces -r and -c)',
			false,
			false,
			'e'
		);

		$this->addOption(
			'from',
			'Lower-bound day to gather data. Default: "' . self::DEFAULT_FROM_DAY . '"',
			false,
			true,
			'f'
		);

		$this->addOption(
			'days',
			'Number of days of data to gather past start-date. Default: ' . self::DEFAULT_DAYS,
			false,
			true,
			'n'
		);

		$this->addOption(
			'methods',
			'Compute statistics for article method aggregation table (if neither -s or -m are provided, both are assumed true)',
			false,
			false,
			'm'
		);

		$this->addOption(
			'summarized',
			'Compute statistics for summarized aggregation table (if neither -s or -m are provided, both are assumed true)',
			false,
			false,
			's'
		);
	}


	public function execute() {
		$this->dry_run = self::FORCE_DRY_RUN || $this->getOption('dryrun');

		$doMethods = $this->getOption('methods') || !$this->getOption('summarized');
		$doSummarized = $this->getOption('summarized') || !$this->getOption('methods');

		$replace = $this->getOption('replace');
		$clear = $this->getOption('clear');

		if ($doMethods) {
			if ($this->getOption('all') || $this->getOption('all-except-today')) {
				$clear = true;
				$upperTS = false;

				if ($this->getOption('all-except-today')) {
					$upperTS = wfTimestamp(
						TS_MW,
						strtotime(date('Ymd'))
					);
				}

				$methodQuery = $this->getFullHelpfulnessStatsQuery($upperTS);
			} else {
				$fromDay = $this->getOption('from') ?: self::DEFAULT_FROM_DAY;
				$today = date('Ymd');
				$lowerTime = strtotime($fromDay, strtotime($today));
				$lowerTS = wfTimestamp(TS_MW, $lowerTime);
				$days = $this->getOption('days') ?: self::DEFAULT_DAYS;
				$upperTime = strtotime("+$days days", $lowerTime);
				$upperTS = wfTimestamp(TS_MW, $upperTime);

				$methodQuery = $this->getTSBoundHelpfulnessStatsQuery($lowerTS, $upperTS);
			}

			// There's nothing to replace if we clear the table
			$replace = $replace && !$clear;

			if ($clear) {
				print "Clearing display table...\n";
				if ($this->dry_run) {
					print "... but not really, since this is a dry run.\n";
				} else {
					$this->clearDisplayTable();
				}
			}

			$methodUpsertQuery = $this->getUpsertQuery($methodQuery, $replace);
		}

		if ($doSummarized) {
			$summarizedQuery = $this->getSummarizedQuery();
			$summarizedReplaceQuery = $this->getSummarizedReplaceQuery($summarizedQuery);
		}

		if ($this->dry_run) {
			$dbr = wfGetDB(DB_REPLICA);

			if ($doMethods) {
				print "Method upsert query:\n$methodUpsertQuery\n";

				$res = $dbr->query($methodQuery, __METHOD__);

				print 'Values that will be ' . ($replace ? 'replaced' : 'updated') . ":\n\n";
				
				foreach ($res as $row) {
					print implode("\t", get_object_vars($row)) . "\n";
				}
			}

			if ($doSummarized) {
				print "Summarized replace query:\n$summarizedReplaceQuery\n";

				$res = $dbr->query($summarizedQuery, __METHOD__);

				print "Summarized values that will be updated:\n\n";

				foreach ($res as $row) {
					print implode("\t", get_object_vars($row)) . "\n";
				}
			}
		} else {
			$dbw = wfGetDB(DB_MASTER);

			if ($doMethods) {
				print "Updating display table...\n";
				$dbw->query($methodUpsertQuery, __METHOD__);
			}

			if ($doSummarized) {
				print "Updating summarized table...\n";
				$dbw->query($summarizedReplaceQuery, __METHOD__);
			}
		}

		print "Done.\n";

		return;
	}

	protected function getSummarizedQuery() {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectSQLText(
			array(
				'mhe' => 'method_helpfulness_event',
				'am' => 'article_method'
			),
			array(
				'mhe_aid',
				'mhe_source',
				'COUNT(*)'
			),
			array(
				'am_active' => '1'
			),
			__METHOD__,
			array(
				'GROUP BY' => array(
					'mhe_aid',
					'mhe_source'
				)
			),
			array(
				'am' => array(
					'INNER JOIN',
					array(
						'am_aid=mhe_aid'
					)
				)
			)
		);
	}

	protected function getSummarizedReplaceQuery($selectQuery) {
		$replaceClause = <<<SQL
REPLACE INTO article_method_helpfulness_summarized_stats (
	amhss_aid,
	amhss_source,
	amhss_count
)
SQL;

		return "$replaceClause\n$selectQuery";
	}

	protected function getFullHelpfulnessStatsQuery($upperTS=false) {
		$dbr = wfGetDB(DB_REPLICA);

		$query = $dbr->selectSQLText(
			$this->getHelpfulnessStatsTables(),
			$this->getHelpfulnessStatsFields(),
			array_merge(
				$this->getHelpfulnessStatsBaseConds(),
				$upperTS
					? $this->getHelpfulnessStatsUpperTSConds($dbr, $upperTS)
					: array()
			),
			__METHOD__,
			$this->getHelpfulnessStatsOpts(),
			$this->getHelpfulnessStatsJoinConds()
		);

		return $query;
	}

	protected function getTSBoundHelpfulnessStatsQuery($lowerTS, $upperTS) {
		$dbr = wfGetDB(DB_REPLICA);

		$query = $dbr->selectSQLText(
			$this->getHelpfulnessStatsTables(),
			$this->getHelpfulnessStatsFields(),
			array_merge(
				$this->getHelpfulnessStatsBaseConds(),
				$this->getHelpfulnessStatsTSConds($dbr, $lowerTS, $upperTS)
			),
			__METHOD__,
			$this->getHelpfulnessStatsOpts(),
			$this->getHelpfulnessStatsJoinConds()
		);

		return $query;
	}

	protected function getUpsertQuery($selectQuery, $replace=false) {
		$insertClause = $this->getHelpfulnessStatsInsertClause();
		$selectQuery = "SELECT * FROM ($selectQuery) t";
		$updateClause = $this->getHelpfulnessStatsUpdateClause($replace);

		return "$insertClause\n$selectQuery\n$updateClause";
	}

	protected function getHelpfulnessStatsTables() {
		return array(
			'mhe' => 'method_helpfulness_event',
			'am' => 'article_method',
			'mhv' => 'method_helpfulness_vote'
		);
	}

	protected function getHelpfulnessStatsFields() {
		return array(
			'method_id' => 'am.am_id',
			'source' => 'mhe.mhe_source',
			'vote' => 'mhv.mhv_vote',
			'entries' => 'COUNT(*)'
		);
	}

	protected function getHelpfulnessStatsBaseConds() {
		return array(
			'am_active' => '1'
		);
	}

	protected function getHelpfulnessStatsUpperTSConds(&$dbr, $upperTS) {
		return array(
			'mhe.mhe_timestamp < ' . $dbr->addQuotes($upperTS)
		);
	}

	protected function getHelpfulnessStatsTSConds(&$dbr, $lowerTS, $upperTS) {
		return array(
			'mhe.mhe_timestamp >= ' . $dbr->addQuotes($lowerTS),
			'mhe.mhe_timestamp < ' . $dbr->addQuotes($upperTS)
		);
	}

	protected function getHelpfulnessStatsOpts() {
		return array(
			'GROUP BY' => array(
				'am.am_aid',
				'am.am_title_hash',
				'mhe.mhe_source',
				'mhv.mhv_vote'
			),
			'ORDER BY' => array('am.am_timestamp DESC')
		);
	}

	protected function getHelpfulnessStatsJoinConds() {
		return array(
			'am' => array(
				'INNER JOIN',
				array('am.am_aid=mhe.mhe_aid')
			),
			'mhv' => array(
				'INNER JOIN',
				array(
					'mhv.mhv_mhe_id=mhe.mhe_id',
					'mhv.mhv_am_id=am.am_id'
				)
			)
		);
	}

	protected function getHelpfulnessStatsInsertClause() {
		$table = 'article_method_helpfulness_stats';
		$fields = implode(',', $this->getHelpfulnessStatsInsertFields());
		return <<<SQL
INSERT INTO $table ($fields)
SQL;
	}

	protected function getHelpfulnessStatsUpdateClause($replace=false) {
		$updateExpr = implode(',', array(
			'amhs_am_id=t.method_id',
			'amhs_source=t.source',
			'amhs_vote=t.vote',
			$replace ? 'amhs_count=t.entries' : 'amhs_count=amhs_count+t.entries'
		));

		return "ON DUPLICATE KEY UPDATE $updateExpr";
	}

	protected function getHelpfulnessStatsInsertFields() {
		return array(
			'amhs_am_id',
			'amhs_source',
			'amhs_vote',
			'amhs_count'
		);
	}

	protected function clearDisplayTable() {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->delete(
			'article_method_helpfulness_stats',
			'*',
			__METHOD__
		);
	}
}

$maintClass = 'UpdateArticleMethodHelpfulnessStats';
require_once RUN_MAINTENANCE_IF_MAIN;

