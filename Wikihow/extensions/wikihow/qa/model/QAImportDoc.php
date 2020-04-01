<?php

class QAImportDoc {
	const STATUS_NEW = 0;
	const STATUS_PENDING = 1;
	const STATUS_COMPLETE = 2;

	var $id, $createdTimestamp, $status, $url, $completedTimestamp;

	private function __construct() {}

	public static function newFromDBRow($row) {
		$doc = new QAImportDoc();
		$doc->loadFromDBRow($row);

		return $doc;
	}

	public function loadFromDBRow($row) {
		$row = get_object_vars($row);
		$this->id = $row['qi_id'];
		$this->createdTimestamp = $row['qi_created_timestamp'];
		$this->status = $row['qi_status'];
		$this->url = $row['qi_url'];
		$this->completedTimestamp = $row['qi_completed_timestamp'];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getCreatedTimestamp() {
		return $this->createdTimestamp;
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return mixed
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return mixed
	 */
	public function getCompletedTimestamp() {
		return $this->completedTimestamp;
	}
}
