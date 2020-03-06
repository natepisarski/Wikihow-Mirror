<?php

class QuizImporter {
	var $methodInfo;

	const SHEET_ID = "1PEqxKugzmaWPRH8vQO4qLS2xA7r9gV0Y_qURs5ReI0s";

	function __construct() {
		$this->methodInfo = [];
	}

	public function importSpreadsheet() {
		global $wgIsDevServer;

		$worksheet = $wgIsDevServer ? 'testing - do not delete' : 'Quiz Importer';
		$data = GoogleSheets::getRowsAssoc(self::SHEET_ID, $worksheet);
		return $this->processSheetData($data);
	}

	private function processSheetData(Iterator $sheetData) {
		$quizzes = [];
		$quizzesToInsert = [];
		$badUrls = [];
		$totalCount = 0;
		foreach ($sheetData as $row) {
			$totalCount++;
			$articleUrl = $row['URL'];
			$title = Misc::getTitleFromText($articleUrl);

			if (!$title || !$title->exists()) {
				$badUrls[] = $articleUrl . " (Bad Url)";
				continue;
			}
			$aid = $title->getArticleID();
			$method = $row['Section'];
			$question = html_entity_decode($row['Question']);
			$answer = ord(strtolower($row['Correct answer'])) - ord("a");
			$data = ['options' => [], 'explanations' => []];

			if ($row['Answer A'] != "") {
				$data['options'][] = html_entity_decode($row['Answer A']);
				$data['explanations'][] = html_entity_decode($row['Response A']);
			}
			if ($row['Answer B'] != "") {
				$data['options'][] = html_entity_decode($row['Answer B']);
				$data['explanations'][] = html_entity_decode($row['Response B']);
			}
			if ($row['Answer C'] != "") {
				$data['options'][] = html_entity_decode($row['Answer C']);
				$data['explanations'][] = html_entity_decode($row['Response C']);
			}
			if ($row['Answer D'] != "") {
				$data['options'][] = html_entity_decode($row['Answer D']);
				$data['explanations'][] = html_entity_decode($row['Response D']);
			}
			if ($row['Answer E'] != "") {
				$data['options'][] = html_entity_decode($row['Answer E']);
				$data['explanations'][] = html_entity_decode($row['Response E']);
			}
			$author = $row['Author'];

			$isValid = $this->checkMethodName($title, $method);
			if (!$isValid) {
				$badUrls[] = $articleUrl . " -> " . $method . " (Bad Method Name)";
			}

			if (!array_key_exists($aid, $quizzes)) {
				$quizzes[$aid] = [];
			}
			$quizzes[$aid][md5($method)] = true;

			$quizzesToInsert[] = Quiz::createInsertArray($aid, $method, $question, $answer, $data, $author);
		}

		$this->replaceQuizzes($quizzesToInsert);

		//now clear memc for all those articles
		foreach ($quizzes as $aid => $value) {
			Quiz::clearMemc($aid);
		}

		$allQuizzes = Quiz::loadAllQuizzes();
		$numDeleted = 0;
		foreach ($allQuizzes as $quiz) {
			if (!array_key_exists($quiz->getArticleId(), $quizzes)) {
				$this->deleteQuiz($quiz);
				$numDeleted++;
				continue;
			}
			if (!array_key_exists($quiz->getHash(), $quizzes[$quiz->getArticleId()])) {
				$this->deleteQuiz($quiz);
				$numDeleted++;
			}
		}

		$info = [];
		$info['good'] = $totalCount - count($badUrls);
		$info['bad'] = count($badUrls);
		$info['errors'] = $badUrls;
		$info['deleted'] = $numDeleted;

		return $info;
	}

	private function replaceQuizzes($quizzes) {
		$dbw = wfGetDB( DB_MASTER );
		$row = $dbw->replace(
			Quiz::TABLE_NAME,
			['qz_aid', 'qz_hash'],
			$quizzes,
			__METHOD__
		);
	}

	private function deleteQuiz($quiz) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(Quiz::TABLE_NAME, ['qz_aid' => $quiz->getArticleId(), 'qz_hash' => $quiz->getHash()], __METHOD__);
		Quiz::clearMemc($quiz->getArticleId());
	}

	public static function getMethodCountForId($aid) {
		$title = Title::newFromID($aid);
		$importer = new QuizImporter();
		$articleQuizzes = new ArticleQuizzes($aid);
		$count = ['good' => 0, 'bad' => 0];
		foreach ($articleQuizzes::$quizzes as $quiz) {
			if ($importer->checkMethodHash($title, $quiz->getHash())) {
				$count['good']++;
			} else {
				$count['bad']++;
			}
		}

		return $count;
	}

	private function checkMethodName($title, $methodName) {
		$methodHash = md5($methodName);
		return $this->checkMethodHash($title, $methodHash);
	}

	private function checkMethodHash($title, $methodHash) {
		if (is_null($title)) {
			return false;
		}
		$id = $title->getArticleId();
		if (!array_key_exists($id, $this->methodInfo)) {
			$this->getAltMethods($title);
		}

		return array_key_exists($methodHash, $this->methodInfo[$id]);
	}

	public function getAltMethods($title) {
		$this->methodInfo[$title->getArticleID()] = [];

		$r = Revision::newFromTitle($title);
		if (is_null($r)) {
			mail('bebeth@wikihow.com', 'quiz issue', $title->getDBkey() . " can't find a revision");
			return;
		}
		$wikitext = ContentHandler::getContentText($r->getContent());

		$stepsSection = Wikitext::getStepsSection($wikitext, true);
		if ( !$stepsSection ) return;

		$stepsText = Wikitext::stripHeader($stepsSection[0]);
		if ( Wikitext::countAltMethods($stepsText) > 0 ) {
			$altMethods = Wikitext::splitAltMethods($stepsText);
			foreach ($altMethods as $i => $method) {
				$ret = preg_match('@===([^=]*)===@', $method, $matches);
				if ($ret) {
					$this->methodInfo[$title->getArticleID()][md5(trim($matches[1]))] = true;
				}
			}
		}
	}

	public static function getWorksheetURL() {
		return "https://docs.google.com/a/wikihow.com/spreadsheets/d/" . self::SHEET_ID;
	}

	public function getStats() {
		$quizzes = $this->getAllQuizzes();

		$data = ['quizCount' => 0, 'articleCount' => 0, 'mismatchCount' => 0];
		$lastId = 0;
		foreach ($quizzes as $row) {
			$title = Title::newFromID($row->qz_aid);
			$isValid = $this->checkMethodHash($title, $row->qz_hash);
			if ($isValid) {
				$data['quizCount']++;
			} else {
				$data['mismatchCount']++;
			}
			if ($lastId != $row->qz_aid) {
				$data['articleCount']++;
			}
			$lastId = $row->qz_aid;
		}

		return "Total questions: " . $data['quizCount'] . "<br/>Total articles with quizzes: " . $data['articleCount'] . "<br/> Total mismatched questions: " . $data['mismatchCount'];
	}

	private function getAllQuizzes() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(Quiz::TABLE_NAME, ['qz_aid', 'qz_hash'], [], __METHOD__, ['ORDER BY' => 'qz_aid']);

		$total = [];
		foreach ($res as $row) {
			$total[] = $row;
		}

		return $total;
	}
}
