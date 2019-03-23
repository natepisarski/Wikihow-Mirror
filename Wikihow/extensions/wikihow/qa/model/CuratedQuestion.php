<?php

class CuratedQuestion {
	var $id, $submittedId, $text, $updatedTimestamp, $curatedAnswer;

	/**
	 * @return mixed
	 */
	public function getLastUpdatedTimestamp() {
		return $this->updatedTimestamp;
	}

	public static function newFromDBRow($row) {
		$q = new CuratedQuestion();
		$q->loadFromDBRow($row);
		return $q;
	}

	public static function newFromWeb($data) {
		$q = new CuratedQuestion();
		$q->loadFromWeb($data);
		return $q;
	}

	public function loadFromWeb($data) {
		$this->id = $data['cqid'];
		$this->submittedId =  empty($data['sqid']) ? null : $data['sqid'];
		$this->text = QAUtil::sanitizeCuratedInput($data['question']);
		$this->curatedAnswer = CuratedAnswer::newFromWeb($data);
	}

	public function loadFromDBRow($row) {
		$this->id = $row['qq_id'];
		$this->submittedId =  empty($row['qq_submitted_id']) ? null : $row['qq_submitted_id'];
		$this->text =  $row['qq_question'];
		$this->curatedAnswer = CuratedAnswer::newFromDBRow($row);
		$this->updatedTimestamp = $row['qq_updated_timestamp'];
	}

	/**
	 * @return mixed
	 */
	public function getSubmittedId() {
		return $this->submittedId;
	}

	/**
	 * @return mixed
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * @return mixed
	 */
	public function getUpdatedTimestamp() {
		return $this->updatedTimestamp;
	}

	/**
	 * @return CuratedAnswer
	 */
	public function getCuratedAnswer() {
		return $this->curatedAnswer;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	private function __construct() {

	}
}
