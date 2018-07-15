<?php

define('WH_USE_BACKUP_DB', true);
require_once __DIR__ . '/../Maintenance.php';


/**
 * Delete blank Articles Questions.  Script used to clean up blank AQs inserted by QA Patrol
 */
class QADeleteBlankQuestions extends Maintenance {


	public function __construct() {
		parent::__construct();
		$this->mDescription = "Delete blank Articles Questions";
	}


	/**
	 * Called command line.
	 */
	public function execute() {
		error_reporting(E_ERROR);

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select(
			QADB::TABLE_ARTICLES_QUESTIONS,
			['*'],
			['qa_question_id not in (select qq_id from qa_curated_questions)'],
			__METHOD__
		);

		$aqids = [];
		$questionIds = [];

		foreach ($res as $row) {
			$aqids [] = $row->qa_id;
			$questionIds [] = $row->qa_question_id;
		}

		if (false && count($questionIds)) {
			$dbw->delete(
				QADB::TABLE_CURATED_ANSWERS,
				'qn_question_id IN (' . $dbw->makeList($questionIds) . ')',
				__METHOD__
			);

			$dbw->delete(
				QADB::TABLE_ARTICLES_QUESTIONS,
				'qa_id IN (' . $dbw->makeList($aqids) . ')',
				__METHOD__
			);
		}

		echo count($aqids) . " qa_articles_questions rows \n\n";
		echo "aqids: \n\n" . implode(",", $aqids) . "\n\n";
	}
}

$maintClass = "QADeleteBlankQuestions";
require_once RUN_MAINTENANCE_IF_MAIN;
