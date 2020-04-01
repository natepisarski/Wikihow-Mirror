<?php

/**
 * one-time script to mark as bad all old QA Questions (not answered and older than 6 months)
 **/

require_once __DIR__ . '/../../Maintenance.php';

class deleteOldQAQuestions extends Maintenance {

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$sixMonthsAgo = wfTimestamp(TS_MW, strtotime("6 months ago"));

		$res = DatabaseHelper::batchSelect(
			QADB::TABLE_SUBMITTED_QUESTIONS,
			['qs_id'],
			['qs_proposed' => 0, 'qs_ignore' => 0, "qs_submitted_timestamp < $sixMonthsAgo"],
			__FILE__
		);

		$dbw = wfGetDB(DB_MASTER);
		$ids = [];
		foreach($res as $row) {
			$ids[] = $row->qs_id;

			if(count($ids) >= 1000) {
				$dbw->update(QADB::TABLE_SUBMITTED_QUESTIONS, ['qs_ignore' => 1], ['qs_id IN (' . $dbw->makeList($ids) . ')']);
				$ids = [];
			}
		}
		if(count($ids) > 0) {
			$dbw->update(QADB::TABLE_SUBMITTED_QUESTIONS, ['qs_ignore' => 1], ['qs_id IN (' . $dbw->makeList($ids) . ')']);
		}

		echo "Updated " . count($res) . " rows\n";
	}
}

$maintClass = 'deleteOldQAQuestions';
require_once RUN_MAINTENANCE_IF_MAIN;
