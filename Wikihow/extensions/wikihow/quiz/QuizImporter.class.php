<?php

class QuizImporter {
	var $methodInfo;

	const SHEETS_URL = "https://docs.google.com/spreadsheets/d/";

	const FEED_LINK = "https://spreadsheets.google.com/feeds/list/";
	const FEED_LINK_2 = "/private/values?alt=json&access_token=";
	const SHEET_ID = "1PEqxKugzmaWPRH8vQO4qLS2xA7r9gV0Y_qURs5ReI0s";

	const WORKSHEET = "/od6";
	const DEV_WORKSHEET = "/oc0s99h";

	function __construct() {
		$this->methodInfo = [];
	}

	public function importSpreadsheet () {
		global $wgIsDevServer;

		$worksheet = $wgIsDevServer ? self::DEV_WORKSHEET : self::WORKSHEET;
		$data = $this->getSpreadsheetData(self::SHEET_ID, $worksheet);
		return $this->processSheetData($data);
	}

	private function getSpreadsheetData($sheetId, $worksheetId) {
		global $IP;
		require_once("$IP/extensions/wikihow/docviewer/SampleProcess.class.php");

		$service = SampleProcess::buildService();
		if ( !isset($service) ) {
			return;
		}

		$client = $service->getClient();
		$token = $client->getAccessToken();
		$token = json_decode($token);
		$token = $token->access_token;

		$feedLink = self::FEED_LINK . $sheetId . $worksheetId . self::FEED_LINK_2;
		$sheetData = file_get_contents($feedLink . $token);
		$sheetData = json_decode($sheetData);
		$sheetData = $sheetData->{'feed'}->{'entry'};

		return $sheetData;
	}

	private function processSheetData($sheetData) {
		$quizzes = [];
		$quizzesToInsert = [];
		$badUrls = [];

		foreach($sheetData as $row) {
			$articleUrl = $row->{'gsx$url'}->{'$t'};
			$title = Misc::getTitleFromText($articleUrl);

			if(!$title || !$title->exists()) {
				$badUrls[] = $articleUrl . " (Bad Url)";
				continue;
			}
			$aid = $title->getArticleID();
			$method = $row->{'gsx$section'}->{'$t'};
			$question = html_entity_decode($row->{'gsx$question'}->{'$t'});
			$answer = ord(strtolower($row->{'gsx$correctanswer'}->{'$t'})) - ord("a");
			$data = ['options' => [], 'explanations' => []];

			if($row->{'gsx$answera'}->{'$t'} != "") {
				$data['options'][] = html_entity_decode($row->{'gsx$answera'}->{'$t'});
				$data['explanations'][] = html_entity_decode($row->{'gsx$responsea'}->{'$t'});
			}
			if($row->{'gsx$answerb'}->{'$t'} != "") {
				$data['options'][] = html_entity_decode($row->{'gsx$answerb'}->{'$t'});
				$data['explanations'][] = html_entity_decode($row->{'gsx$responseb'}->{'$t'});
			}
			if($row->{'gsx$answerc'}->{'$t'} != "") {
				$data['options'][] = html_entity_decode($row->{'gsx$answerc'}->{'$t'});
				$data['explanations'][] = html_entity_decode($row->{'gsx$responsec'}->{'$t'});
			}
			if($row->{'gsx$answerd'}->{'$t'} != "") {
				$data['options'][] = html_entity_decode($row->{'gsx$answerd'}->{'$t'});
				$data['explanations'][] = html_entity_decode($row->{'gsx$responsed'}->{'$t'});
			}
			if($row->{'gsx$answere'}->{'$t'} != "") {
				$data['options'][] = html_entity_decode($row->{'gsx$answere'}->{'$t'});
				$data['explanations'][] = html_entity_decode($row->{'gsx$responsee'}->{'$t'});
			}
			$author = $row->{'gsx$author'}->{'$t'};

			$isValid = $this->checkMethodName($title, $method);
			if(!$isValid) {
				$badUrls[] = $articleUrl . " -> " . $method . " (Bad Method Name)";
			}

			if(!array_key_exists($aid, $quizzes)) {
				$quizzes[$aid] = [];
			}
			$quizzes[$aid][md5($method)] = true;

			$quizzesToInsert[] = Quiz::createInsertArray($aid, $method, $question, $answer, $data, $author);
		}

		$this->replaceQuizzes($quizzesToInsert);

		//now clear memc for all those articles
		foreach($quizzes as $aid => $value) {
			Quiz::clearMemc($aid);
		}

		$allQuizzes = Quiz::loadAllQuizzes();
		$numDeleted = 0;
		foreach($allQuizzes as $quiz) {
			if(!array_key_exists($quiz->getArticleId(), $quizzes)) {
				$this->deleteQuiz($quiz);
				$numDeleted++;
				continue;
			}
			if(!array_key_exists($quiz->getHash(), $quizzes[$quiz->getArticleId()])) {
				$this->deleteQuiz($quiz);
				$numDeleted++;
			}
		}

		$info = [];
		$info['good'] = count($sheetData) - count($badUrls);
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
		foreach($articleQuizzes::$quizzes as $quiz) {
			if($importer->checkMethodHash($title, $quiz->getHash())) {
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
		$id = $title->getArticleId();
		if(!array_key_exists($id, $this->methodInfo)) {
			$this->getAltMethods($title);
		}

		return array_key_exists($methodHash, $this->methodInfo[$id]);
	}

	public function getAltMethods($title) {
		$this->methodInfo[$title->getArticleID()] = [];

		$r = Revision::newFromTitle($title);
		$wikitext = ContentHandler::getContentText($r->getContent());

		$stepsSection = Wikitext::getStepsSection($wikitext, true);
		if ( !$stepsSection ) return;

		$stepsText = Wikitext::stripHeader($stepsSection[0]);
		if ( Wikitext::countAltMethods($stepsText) > 0 ) {
			$altMethods = Wikitext::splitAltMethods($stepsText);
			foreach ($altMethods as $i => $method) {
				$ret = preg_match('@===([^=]*)===@', $method, $matches);
				if($ret) {
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
		foreach($quizzes as $row) {
			$title = Title::newFromID($row->qz_aid);
			$isValid = $this->checkMethodHash($title, $row->qz_hash);
			if($isValid) {
				$data['quizCount']++;
			} else {
				$data['mismatchCount']++;
			}
			if($lastId != $row->qz_aid) {
				$data['articleCount']++;
			}
			$lastId = $row->qz_aid;
		}

		return "Total questions: " . $data['quizCount'] . "<br/>Total articles with quizzes: " . $data['articleCount'] . "<br/> Total mismatched questions: " . $data['mismatchCount'];
	}

	private function getAllQuizzes() {
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select(Quiz::TABLE_NAME, ['qz_aid', 'qz_hash'], [], __METHOD__, ['ORDER BY' => 'qz_aid']);

		$total = [];
		foreach($res as $row) {
			$total[] = $row;
		}

		return $total;
	}
}