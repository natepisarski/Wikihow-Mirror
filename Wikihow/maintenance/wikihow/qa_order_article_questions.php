<?php

define('WH_USE_BACKUP_DB', true);
require_once __DIR__ . '/../Maintenance.php';


/**
 * Orders ArticleQuestions (Answered Questions) on articles
 * based on several scoring factors including votes up/down
 * and whether the ArticleQuestion has a verifier id
 */
class QAOrderArticleQuestions extends Maintenance {

	const KEY_LAST_ORDERED_ARTICLE_ID = 'qa_last_ordered_article_id';
	const LIMIT_QUERY_ARTICLES = 1000;
	const LIMIT_ARTICLES_TO_PROCESS = self::LIMIT_QUERY_ARTICLES * 50;
	const EXPERT_FACTOR = 100000;


	public function __construct() {
		parent::__construct();
		$this->mDescription = "Nightly script to update ordering for Q&A Answered questions";
	}


	/**
	 * Called from command line.
	 */
	public function execute() {
		global $argv;
		$command = $argv[0] ?: 0;
		$this->output("Start QA Ordering: " . $command . " " . date('G:i:s:u'));

		switch ($command) {
			case "all":
				$this->processAll();
				break;
			case "nightly":
				$this->processNightly();
				break;
			case "article":
				break;

		}

		$this->output("Finish QA Ordering: " . $command . " " . date('G:i:s:u'));
	}

	protected function processArticle() {
		global $argv;
		$aid = intVal($argv[1]);
		if ($aid > 0) {
			$this->orderArticle($aid);
		}
	}

	protected function orderArticles($aids) {
		foreach ($aids as $aid) {
			$this->orderArticle($aid);
		}
	}

	protected function orderArticle($aid) {
		$qadb = QADB::newInstance();
		$aqs = $qadb->getArticleQuestions([$aid], true);
		$rows = [];
		foreach ($aqs as $aq) {
			$score = WilsonConfidenceInterval::getScore($aq->getVotesUp(), $aq->getVotesDown());
			$score = round($score * 1000);
			// Rank expert verified answers higher than
			if (!empty($aq->getVerifierId())) {
				$score += self::EXPERT_FACTOR;
			}
			$rows []= ['qa_article_id' => $aq->getArticleId(), 'qa_id' => $aq->getId(), 'qa_score' => $score];
		}
		$rows = $this->sortScores($rows);
		//var_dump("Sorted: ", $rows);
		$rows = $this->makeScoresUnique($rows);
		//var_dump("Unique: ", $rows);
		$this->updateArticleQuestionScores($rows);
		QAWidgetCache::clearArticleQuestionsPagingCache($aid, count($aqs));

		$t = Title::newFromId($aid);
		if ($t && $t->exists()) {
			$t->purgeSquid();
		}
	}

	/*
	 * Sort scores by ascending order
	 */
	protected function sortScores($rows) {
		$buildSorter = function($key) {
			return function ($a, $b) use ($key) {
				return $a[$key] > $b[$key] ? 1 : -1;
			};
		};

		usort($rows, $buildSorter('qa_score'));
		return $rows;
	}

	/**
	 * Create a unique score value for each ArticleQuestion. Ordering is done such that the lowest scored ArticleQuestion
	 * will have the lowest score value. Ordering starts at 1.  We do this to prevent duplicate scores. Duplicate scores
	 * will mess up pagination of ArticleQuestions.
	 * @param $rows
	 * @return array $rows
	 */
	protected function makeScoresUnique($rows) {
		foreach ($rows as $i => $row) {
			$rows[$i]['qa_score'] = $i + 1;
		}
		return $rows;
	}

	protected function updateArticleQuestionScores($rows) {
		$dbw = wfGetDB(DB_MASTER);
		$success = $dbw->upsert(
			QADB::TABLE_ARTICLES_QUESTIONS,
			$rows,
			['qa_id'],
			['qa_score = VALUES(qa_score)'],
			__METHOD__
		);
		if (!$success) {
			$this->output("Failed storing new scores:");
			$this->output($dbw->lastError());
			$this->output("Failed query:");
			$this->output($dbw->lastQuery());
		}
	}

	protected function getLastUpdatedAid() {
		return ConfigStorage::dbGetConfig(self::KEY_LAST_ORDERED_ARTICLE_ID);
	}

	protected function setLastUpdatedeAid($aid) {
		$error = "";
		// don't log these changes to the history table
		ConfigStorage::dbStoreConfig(self::KEY_LAST_ORDERED_ARTICLE_ID, $aid, false, $error, true, 0, false);
		if (!empty($error)) {
			$this->output("Error when storing dbconfig: $error");
		}
	}

	protected function output($str, $includeNewline = true) {
		$newLine = $includeNewline ? "\n" : "";
		echo "$str$newLine";
	}

	protected function processNightly() {
		$lastUpdatedAid = $startingAid = ConfigStorage::dbGetConfig(self::KEY_LAST_ORDERED_ARTICLE_ID);
		$loopedAround = false;
		$processed = 0;
		do {
			$aids = $this->getArticleIds($lastUpdatedAid);
			if (count($aids) == 0) {
				$lastUpdatedAid = 0;
				$loopedAround = true;
			} else {
				$this->orderArticles($aids);
				$lastUpdatedAid = $aids[count($aids) - 1];
			}
			$this->setLastUpdatedeAid($lastUpdatedAid);
			$processed += count($aids);

			$this->output("Last updated aid: $lastUpdatedAid");
			$this->output("Articles processed: $processed");

		} while ($processed < self::LIMIT_ARTICLES_TO_PROCESS &&
			!($loopedAround && $lastUpdatedAid >= $startingAid));
	}

	protected function processAll() {
		$lastUpdatedAid = 0;
		$processed = 0;
		do {
			$aids = $this->getArticleIds($lastUpdatedAid);
			$numAids = count($aids);
			if (!empty($aids)) {
				$this->orderArticles($aids);
				$lastUpdatedAid = $aids[$numAids - 1];
			}
			$processed += $numAids;

			$this->output("Last updated aid: $lastUpdatedAid");
			$this->output("Articles processed: $processed");
		} while (!empty($aids));
	}

	/**
	 * @param $lastUpdatedAid
	 * @return array
	 */
	protected function getArticleIds($lastUpdatedAid) {
		$aids = [];
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			QADB::TABLE_ARTICLES_QUESTIONS,
			['distinct(qa_article_id)'],
			["qa_article_id > $lastUpdatedAid"],
			__METHOD__,
			[
				"ORDER BY" => 'qa_article_id',
				"LIMIT" => self::LIMIT_QUERY_ARTICLES,
			]
		);

		foreach ($res as $row) {
			$aids [] = $row->qa_article_id;
		}
		return $aids;
	}
}

$maintClass = "QAOrderArticleQuestions";
require_once RUN_MAINTENANCE_IF_MAIN;
