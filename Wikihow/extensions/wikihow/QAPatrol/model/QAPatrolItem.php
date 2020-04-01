<?php

class QAPatrolItem {
	var $qapid, $submitterName, $submitterUserId, $verifierId, $verifierData, $articleId, $question, $answer, $qapsqid, $qapTimestamp, $isTopAnswerer, $submitterDisplayName, $submitterAvatar;

	/**
	 * @return mixed
	 */
	public function getSubmitterName() {
		return $this->submitterName;
	}

	/**
	 * @return mixed
	 */
	public function getSubmitterDisplayName() {
		return $this->submitterDisplayName;
	}

	/**
	 * @return mixed
	 */
	public function getSubmitterUserId() {
		return $this->submitterUserId;
	}

	private function __construct() {}

	public static function newFromDBRow($row) {
		$aq = new QAPatrolItem();
		$aq->loadFromDBRow($row);
		return $aq;
	}

	public function loadFromDBRow($row) {
		$this->qapid = $row['qap_id'];
		$this->articleId = $row['qa_article_id'];
		$this->verifierId = intVal($row['qap_verifier_id']);
		$this->question = $row['qap_question'];
		$this->answer = $row['qap_answer'];
		$this->submitterUserId = $row['qap_submitter_user_id'];
		$this->qapsqid = $row['qap_sqid'];
		$this->qapTimestamp = $row['qap_timestamp'];
		$this->isTopAnswerer = !empty($row['ta_user_id']) && !$row['ta_is_blocked'];

		if ($this->verifierId > 0) {
			$this->verifierData = VerifyData::newVerifierFromRow($row);
		} else {
			$this->verifierData = null;
		}

		$this->submitterDisplayName = '';
		$this->submitterAvatar = '';
	}

	public function setProfileDisplayData($display_data) {
		$name = $display_data['display_name'] ?: wfMessage('qa_generic_username')->text();
		$avatar_url = $display_data['avatar_url'] ?: '';

		$this->submitterDisplayName = $name;
		$this->submitterAvatar = $avatar_url;
		return;
	}

}
