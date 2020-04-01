<?php

class SubmittedQuestion {
	var $id, $userId, $visitorId, $articleId, $email, $platform, $source, $text, $ignore,
		$flagged, $approved, $proposed, $sorted, $submittedTimestamp, $curated, $lastUpdatedTimestamp;

	static $blacklisted_emails = [
		'maxnaran@gmail.com'
	];

	const SOURCE_ARTICLE_PROMPT = 'article page prompt';
	const SOURCE_HELPFULNESS = 'page helpfulness';
	const MAX_FLAGGED_COUNT = 2;

	/**
	 * @return SubmittedQuestion
	 */
	public static function newFromDBRow($row) {
		$q = new SubmittedQuestion();
		$q->loadFromDBRow($row);
		return $q;
	}

	public function loadFromDBRow($row) {
		$this->id = $row['qs_id'];
		$this->userId = $row['qs_user_id'];
		$this->visitorId = $row['qs_visitor_id'];
		$this->articleId = $row['qs_article_id'];
		$this->email = $row['qs_email'];
		$this->platform = $row['qs_platform'];
		$this->source = $row['qs_source'];
		$this->text = $row['qs_question'];
		$this->ignore = empty($row['qs_ignore']) ? 0 : 1;
		$this->flagged = $row['qs_flagged'];
		$this->approved = empty($row['qs_approved']) ? 0 : 1;
		$this->proposed = empty($row['qs_proposed']) ? 0 : 1;
		$this->sorted = empty($row['qs_sorted']) ? 0 : 1;
		$this->submittedTimestamp = $row['qs_submitted_timestamp'];
		$this->curated = empty($row['qs_curated']) ? 0 : 1;
		$this->lastUpdatedTimestamp = $row['qs_updated_timestamp'];
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
	public function getUserId() {
		return $this->userId;
	}

	/**
	 * @return mixed
	 */
	public function getVisitorId() {
		return $this->visitorId;
	}

	/**
	 * @return mixed
	 */
	public function getArticleId() {
		return $this->articleId;
	}

	/**
	 * @return mixed
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @return mixed
	 */
	public function getPlatform() {
		return $this->platform;
	}

	/**
	 * @return mixed
	 */
	public function getSource() {
		return $this->source;
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
	public function getIgnore() {
		return $this->ignore;
	}

	/**
	 * @return mixed
	 */
	public function getFlagged() {
		return $this->flagged;
	}

	/**
	 * @return mixed
	 */
	public function getApproved() {
		return $this->approved;
	}

	/**
	 * @return mixed
	 */
	public function getProposed() {
		return $this->proposed;
	}

	/**
	 * @return mixed
	 */
	public function getSorted() {
		return $this->sorted;
	}

	/**
	 * @return mixed
	 */
	public function getSubmittedTimestamp() {
		return $this->submittedTimestamp;
	}

	/**
	 * @return mixed
	 */
	public function getCurated() {
		return $this->curated;
	}

	/**
	 * @return mixed
	 */
	public function getLastUpdatedTimestamp() {
		return $this->lastUpdatedTimestamp;
	}

	public static function isBlacklistedQuestionSubmitterEmail($email) {
		return in_array($email, self::$blacklisted_emails);
	}
}
