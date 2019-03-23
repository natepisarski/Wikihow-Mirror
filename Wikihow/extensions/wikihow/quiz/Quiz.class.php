<?php

class Quiz {
	private $articleId;
	private $methodHash;
	private $question;
	private $answer;
	private $options;
	private $explanations;
	private $quizNum;

	var $wrongOptions = ['Guess again!', 'Try again...', 'Choose another answer!', 'Try another answer...', 'Pick another answer!', 'There’s a better option out there!', 'Click on another answer to find the right one...'];
	var $wrongOptionsTwo = ['Guess again!', 'Try again...', 'Choose another answer!', 'Try another answer...', 'Pick another answer!'];
	var $rightResponse = "Read on for another quiz question.";

	const MAX_CHOICES = 5;
	const TABLE_NAME = "quiz";
	const MEMC_KEY = "quizzes2";

	public function getArticleId() {
		return $this->articleId;
	}

	public function getHash() {
		return $this->methodHash;
	}

	public static function loadFromDBRow($row, $quizNum) {
		$quiz = new Quiz;

		$quiz->articleId = $row['qz_aid'];
		$quiz->question = $row['qz_question'];
		$quiz->answer = intval($row['qz_answer']);
		$info = get_object_vars(json_decode($row['qz_blob']));
		$quiz->explanations = $info['explanations'];
		$quiz->options = $info['options'];
		$quiz->quizNum = $quizNum;
		$quiz->methodHash = $row['qz_hash'];

		return $quiz;
	}

	public static function createInsertArray($aid, $name, $question, $answer, $data, $author) {
		return [
			'qz_aid' => $aid,
			'qz_hash' => md5($name),
			'qz_question' => $question,
			'qz_answer' => $answer,
			'qz_blob' => json_encode( $data ),
			'qz_author' => $author
		];
	}

	public static function loadAllQuizzesForArticle($aid) {
		global $wgMemc;

		if ($aid <= 0) {
			return [];
		}

		$key = wfMemcKey(self::MEMC_KEY, $aid);
		$val = $wgMemc->get($key);

		if (is_array($val)) {
			return $val;
		}
		$dbr = wfGetDB(DB_REPLICA);
		$quizzes = [];

		$res = $dbr->select(self::TABLE_NAME, ['*'], ['qz_aid' => $aid], __METHOD__);
		foreach ($res as $index => $row) {
			$quizzes[$row->qz_hash] = Quiz::loadFromDBRow(get_object_vars($row), $index);
		}

		$wgMemc->set($key, $quizzes);

		return $quizzes;
	}

	public static function loadAllQuizzes() {
		$dbr = wfGetDB(DB_REPLICA);
		$quizzes = [];

		$res = $dbr->select(self::TABLE_NAME, ['*'], [], __METHOD__);
		foreach ($res as $index => $row) {
			$quizzes[] = Quiz::loadFromDBRow(get_object_vars($row), $index);
		}

		return $quizzes;
	}

	public function getQuizHtml($methodType, $showFirstAtTop = false){
		global $wgOut;

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		$data = $this->getData();
		$data['methodType'] = $methodType;
		$data['ampClass'] = GoogleAmp::isAmpMode($wgOut)?'qz_amp':'';
		$data['platform'] = Misc::isMobileMode()?'qz_mobile':'qz_desktop';
		if ($showFirstAtTop) {
			$data['quizTopInfo'] = "Take your best guess, or read below to learn the answer.";
		}
		return $m->render('quiz', $data);
	}

	public function getData() {
		$data = [];
		$data['question'] = $this->question;
		$data['quizNum'] = $this->quizNum;
		$data['options'] = [];
		$wrongOptions = count($this->options) > 2 ? $this->wrongOptions : $this->wrongOptionsTwo;
		foreach ($this->options as $index => $option) {
			$info = ['option' => $option, 'explanation' => $this->explanations[$index], 'optionNum' => $index];
			if ($index == $this->answer) {
				$info['class'] = "correct";
				$info['addon'] = $this->rightResponse;
			} else {
				$info['class'] = "incorrect";
				$info['addon'] = $wrongOptions[rand(0, count($wrongOptions)-1)];
			}
			$data['options'][] = $info;
		}

		return $data;
	}

	public static function clearMemc($aid) {
		global $wgMemc;

		$key = wfMemcKey(self::MEMC_KEY, $aid);
		$wgMemc->delete($key);
	}
}
/********
CREATE TABLE `quiz` (
`qz_aid` int(8) unsigned NOT NULL,
`qz_hash` varchar(32),
`qz_question` text,
`qz_answer` int(2) unsigned NOT NULL default 0,
`qz_blob` blob NOT NULL,
`qz_author` varchar(32),
UNIQUE KEY id_method (`qz_aid`, `qz_hash`),
KEY `qz_aid` (`qz_aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
****/
