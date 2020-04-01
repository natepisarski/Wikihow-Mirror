<?php

/**
 * Used to update qa_patrol with the number of live Q&As an article has
 **/

require_once __DIR__ . '/../Maintenance.php';

class QAPatrolUpdate extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Update the number of live Q&As for the Q&A Patrol table.";
	}

	public function execute() {
		$starttime = microtime(true);
		$count = 0;

		$dbw = wfGetDB(DB_MASTER);

		//get article ids & # of questions (from articles & from QAPatrol)
		$aq_articles = self::getArticles($dbw);
		$qap_articles = self::getQAPArticles($dbw);

		//get the ones that need updated
		$articles = self::diffIt($aq_articles, $qap_articles);

		//update those numbers
		foreach ($articles as $article_id => $qaq) {
			$res = $dbw->update(
				QADB::TABLE_QA_PATROL,
				[
					'qap_articles_questions' => $qaq
				],
				[
					'qap_page_id' => $article_id
				],
				__METHOD__
			);
			if ($res) $count++;
		}

		echo "Updated $count rows for the qa_patrol table in " . (microtime(true)-$starttime) . " seconds.\n";
	}

	private static function getArticles($dbw) {
		$articles = [];

		$res = $dbw->select(
			QADB::TABLE_ARTICLES_QUESTIONS,
			[
				'qa_article_id',
				'count(*) AS questions'
			],
			[
				'qa_alt_site' => 0
			],
			__METHOD__,
			[
				'GROUP BY' => ['qa_article_id']
			]
		);

		foreach ($res as $row) {
			$articles[$row->qa_article_id] = $row->questions;
		}

		return $articles;
	}

	private static function getQAPArticles($dbw) {
		$articles = [];

		$res = $dbw->select(
			QADB::TABLE_QA_PATROL,
			[
				'qap_page_id',
				'qap_articles_questions'
			],
			'',
			__METHOD__
		);

		foreach ($res as $row) {
			$articles[$row->qap_page_id] = $row->qap_articles_questions;
		}

		return $articles;
	}

	private static function diffIt($aq_articles, $qap_articles) {
		$articles = [];
		if (empty($aq_articles) || empty($qap_articles)) return $articles;

		foreach ($qap_articles as $a => $n) {
			if (isset($aq_articles[$a]) && $aq_articles[$a] != $n) {
				$articles[$a] = $aq_articles[$a];
			}
		}

		return $articles;
	}

}

$maintClass = 'QAPatrolUpdate';
require_once RUN_MAINTENANCE_IF_MAIN;
