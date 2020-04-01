<?php

class CuratedAnswer {
	var $id, $questionId, $text, $updatedTimestamp;

	private function __construct() {}

	/**
	 * @return mixed
	 */
	public function getLastUpdatedTimestamp() {
		return $this->updatedTimestamp;
	}

	/**
	 * @return mixed
	 */
	public function getUpdatedTimestamp() {
		return $this->updatedTimestamp;
	}

	public static function newFromDBRow($row) {
		$a = new CuratedAnswer();
		$a->loadFromDBRow($row);
		return $a;
	}

	public static function newFromWeb($data) {
		$a = new CuratedAnswer();
		$a->loadFromWeb($data);
		return $a;
	}

	public function loadFromDBRow($row) {
		$this->id = $row['qn_id'];
		$this->questionId = $row['qn_question_id'];
		$this->text = $row['qn_answer'];
		$this->updatedTimestamp = $row['qn_updated_timestamp'];
	}

	public function loadFromWeb($data) {
		$this->id = $data['caid'];
		$this->questionId = $data['cqid'];
		$this->text = QAUtil::sanitizeCuratedInput($data['answer']);
	}

	/**
	 * @return mixed
	 */
	public function getQuestionId() {
		return $this->questionId;
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
	public function getId() {
		return $this->id;
	}
}
