<?php

class ArticleQuestion {
	var $id, $articleId, $curatedQuestionId, $curatedQuestion, $inactive, $updatedTimestamp, $updatedUser,
		$votesUp, $votesDown, $score, $verifierId, $verifierData, $submitterUserId, $submitterName,
		$submitterDisplayName, $submitterAvatar, $altDomain, $isTopAnswerer, $staffInfo;

	/**
	 * @return mixed
	 */
	public function getSubmitterName() {
		return $this->submitterName;
	}

	/**
	 * @return mixed
	 */
	public function getSubmitterUserId() {
		return $this->submitterUserId;
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
	public function getSubmitterAvatar() {
		return $this->submitterAvatar;
	}

	const VOTE_TYPE_UP = 'up';
	const VOTE_TYPE_DOWN = 'down';

	/**
	 * @return mixed
	 */
	public function getVerifierId() {
		return $this->verifierId;
	}

	/**
	 * @return mixed
	 */
	public function getUpdatedTimestamp() {
		return $this->updatedTimestamp;
	}

	/**
	 * @return mixed
	 */
	public function getVotesUp() {
		return $this->votesUp;
	}

	/**
	 * @return mixed
	 */
	public function getVotesDown() {
		return $this->votesDown;
	}


	/**
	 * @return mixed
	 */
	public function getHelpfulnessScore() {
		return $this->score;
	}

	private function __construct() {}

	/**
	 * @return mixed
	 */
	public function getLastUpdatedTimestamp() {
		return $this->updatedTimestamp;
	}

	/**
	 * @return CuratedQuestion
	 */
	public function getCuratedQuestion() {
		return $this->curatedQuestion;
	}

	public static function newFromDBRow($row) {
		$aq = new ArticleQuestion();
		$aq->loadFromDBRow($row);
		return $aq;
	}

	/*
	 * IMPORTANT: Only use this for creating brand new ArticleQuestions
	 */
	public static function newFromWeb($data) {
		$aq = new ArticleQuestion();
		$aq->loadFromWeb($data);
		return $aq;
	}


	public function loadFromWeb($data) {
		$this->articleId = $data['aid'];
		$this->id = $data['aqid'];
		$this->inactive = intVal($data['inactive']);
		$this->votesUp = $data['votes_up'];
		$this->votesDown = $data['votes_down'];
		$this->score = $data['score'];
		$this->curatedQuestion = CuratedQuestion::newFromWeb($data);
		$this->verifierId = intVal($data['vid']);

		// These fields are optional, meaning not all views (including the article page admin question UI) will be
		// getting/setting this data.  Don't convert to intVal because we need the default value to be null.  If the
		// field is null QADB::doInsertArticleQuestion will exclude it from the insert which prevents existing values
		// from being overridden
		$this->submitterUserId = $data['submitter_user_id'];
		$this->submitterName = $data['submitter_name'];

	}

	public function loadFromDBRow($row) {
		$this->id = $row['qa_id'];
		$this->articleId = $row['qa_article_id'];
		$this->curatedQuestionId = $row['qa_question_id'];
		$this->curatedQuestion = CuratedQuestion::newFromDBRow($row);
		$this->inactive = intVal($row['qa_inactive']);
		$this->updatedTimestamp = $row['qa_updated_timestamp'];
		$this->updatedUser = $row['qa_uid'];
		$this->votesUp = $row['qa_votes_up'];
		$this->votesDown = $row['qa_votes_down'];
		$this->score = $row['qa_score'];
		$this->submitterUserId = $row['qa_submitter_user_id'];
		$this->submitterName = $row['qa_submitter_name'];
		$this->verifierId = intVal($row['vi_id']);
		$this->altDomain = $row['qa_alt_site'];
		$this->isTopAnswerer = !empty($row['ta_user_id']) && !$row['ta_is_blocked'];
		$this->staffInfo = $this->getStaffInfo();

		if ($this->verifierId > 0) {
			$this->verifierData = VerifyData::newVerifierFromRow($row);
		} else {
			$this->verifierData = null;
		}

		$this->submitterDisplayName = '';
		$this->submitterAvatar = '';
	}

	/**
	 * @param mixed $id
	 */
	protected function setId($id) {
		$this->id = $id;
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
	public function getArticleId() {
		return $this->articleId;
	}

	/**
	 * @return mixed
	 */
	public function getCuratedQuestionId() {
		return $this->curatedQuestionId;
	}

	/**
	 * @return mixed
	 */
	public function getInactive() {
		return $this->inactive;
	}


	public static function onUnitTestsList( &$files ) {
		global $IP;
		$files = array_merge( $files, glob( "$IP/extensions/wikihow/qa/tests/model/*Test.php" ) );
		return true;
	}

	private function getPercent($up, $down) {
		$total = $up + $down;
		if (!$total) return 0;

		$percent = ($up / $total) * 100;
		return round($percent);
	}

	public function setProfileDisplayData($display_data) {
		$name = $display_data['display_name'] ?: wfMessage('qa_generic_username')->text();
		$avatar_url = $display_data['avatar_url'] ?: '';

		$this->submitterDisplayName = $name;
		$this->submitterAvatar = $avatar_url;
		return;
	}

	public function getAltDomain() {
		return $this->altDomain;
	}

	public function getStaffInfo(): string {
		if (empty($this->updatedTimestamp) || empty($this->updatedUser)) return '';

		$unixTS = wfTimestamp(TS_UNIX, $this->updatedTimestamp);
		$date = DateTime::createFromFormat('U', $unixTS)->format('n/j/y');

		$user = User::newFromId($this->updatedUser);
		if (empty($user)) return '';

		return wfMessage('qa_staff_info', $date, $user->getName())->text();
	}

}
