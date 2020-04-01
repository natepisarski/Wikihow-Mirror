<?php

/**
 * Get a snapshot of articles with unsorted questions
 * for the Approve Questions tool (/Special:SortQuestions)
 **/

require_once __DIR__ . '/../Maintenance.php';

class ArticlesWithUnsortedQuestions extends Maintenance {

	const ARTICLE_LIMIT = 25000;

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Get a snapshot of articles with unsorted questions.";
	}

	public function execute() {
		$rows = $this->getArticleIDs();
		$this->clearTable();
		$this->updateTable($rows);
	}

	private function clearTable() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(
			SortQuestions::SQ_QUEUE_TABLE,
			'*',
			__METHOD__
		);
	}

	private function getArticleIDs() {
		$dbr = wfGetDB(DB_REPLICA);
		$rows = [];

		$res = $dbr->select(
			[
				QADB::TABLE_SUBMITTED_QUESTIONS,
				'titus_copy'
			],
			'qs_article_id',
			[
				'qs_article_id = ti_page_id',
				'qs_curated' => 0,
				'qs_proposed' => 0,
				'qs_ignore' => 0,
				'qs_sorted' => 0,
				'qs_approved' => 0,
				'ti_language_code' => 'en',
				'ti_qa_show_unanswered' => 1
			],
			__METHOD__,
			[
				'GROUP BY' => 'qs_article_id',
				'HAVING' => 'count(*) >= '. SortQuestions::MIN_NUM_QUESTIONS,
				'ORDER BY' => ['ti_qa_questions_answered','ti_qa_questions_unanswered_approved'],
				'LIMIT' => self::ARTICLE_LIMIT
			]
		);

		foreach ($res as $row) {
			$rows[] = [
				'sqq_page_id' => $row->qs_article_id
			];
		}

		return $rows;
	}

	private function updateTable($rows) {
		$dbw = wfGetDB(DB_MASTER);
		$chunks = array_chunk($rows,500);

		foreach ($chunks as $rows_chunk) {
			$dbw->insert(
				SortQuestions::SQ_QUEUE_TABLE,
				$rows_chunk,
				__METHOD__
			);
		}
	}
}

$maintClass = 'ArticlesWithUnsortedQuestions';
require_once RUN_MAINTENANCE_IF_MAIN;
