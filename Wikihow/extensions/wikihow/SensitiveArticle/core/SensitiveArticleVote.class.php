<?php

/*
CREATE TABLE `sensitive_article_vote` (
	`sav_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`sav_page_id` INT(10) NOT NULL DEFAULT 0,
	`sav_job_id` INT(10) NOT NULL DEFAULT 0,
	`sav_vote_yes` INT(4) NOT NULL DEFAULT 0,
	`sav_vote_no` INT(4) NOT NULL DEFAULT 0,
	`sav_skip` INT(4) NOT NULL DEFAULT 0,
	`sav_complete` TINYINT(4) NOT NULL DEFAULT 0,
	UNIQUE KEY `page_reason_pair` (`sav_page_id`,`sav_job_id`),
	KEY (`sav_job_id`),
	KEY (`sav_complete`)
);
*/

namespace SensitiveArticle;

/**
 * Instances represent a set of rows in the `sensitive_article_vote` table
 */
class SensitiveArticleVote
{
	public $rowId; // int
	public $pageId; // int
	public $jobId; // int
	public $voteYes; // int
	public $voteNo; // int
	public $skip; //int
	public $complete; //bool

	const TABLE 						= 'sensitive_article_vote';
	const VOTE_POWER_VOTER 	= 2;
	const MAX_SKIPS					= 3;
	const VOTE_MAX_YES			= 2;
	const VOTE_MAX_NO				= 3;

	const STATUS_APPROVED		= 'approved';
	const STATUS_REJECTED		= 'rejected';
	const STATUS_RESOLVED		= 'resolved';

	protected static $dao = null; // SensitiveArticleDao

	public function __construct() {}

	/**
	 * Create a SensitiveArticleVote from the given values
	 */
	public static function newFromValues(int $pageId, int $jobId, int $voteYes = 0, int $voteNo = 0,
		int $skip = 0, bool $complete = false, int $rowId = 0): SensitiveArticleVote
	{
		$articleVote = new SensitiveArticleVote();
		$articleVote->rowId = $rowId;
		$articleVote->pageId = $pageId;
		$articleVote->jobId = $jobId;
		$articleVote->voteYes = $voteYes;
		$articleVote->voteNo = $voteNo;
		$articleVote->skip = $skip;
		$articleVote->complete = $complete;

		return $articleVote;
	}

	/**
	 * Load a SensitiveArticleVote from the database
	 */
	public static function newFromDB(int $pageId, int $jobId): SensitiveArticleVote
	{
		$res = static::getDao()->getSensitiveArticleVoteData($pageId, $jobId);

		$rowId = 0;
		$voteYes = 0;
		$voteNo = 0;
		$skip = 0;
		$complete = false;

		foreach ($res as $row) {
			$rowId = (int) $row->sav_id;
			$voteYes = (int) $row->sav_vote_yes;
			$voteNo = (int) $row->sav_vote_no;
			$skip = (int) $row->sav_skip;
			$complete = (int) $row->sav_complete;
		}

		return static::newFromValues($pageId, $jobId, $voteYes, $voteNo, $skip, $complete, $rowId);
	}

	public static function getNextArticleVote(int $jobId, array $skip_ids = [],
		int $userId, string $visitorId): SensitiveArticleVote
	{
		$res = static::getDao()->getNextSensitiveArticleVoteData($jobId, $skip_ids, $userId, $visitorId);

		$rowId = 0;
		$pageId = 0;
		$voteYes = 0;
		$voteNo = 0;
		$skip = 0;
		$complete = false;

		foreach ($res as $row) {
			$rowId = (int) $row->sav_id;
			$pageId = (int) $row->sav_page_id;
			$jobId = (int) $row->sav_job_id;
			$voteYes = (int) $row->sav_vote_yes;
			$voteNo = (int) $row->sav_vote_no;
			$skip = (int) $row->sav_skip;
			$complete = (int) $row->sav_complete;
		}

		return static::newFromValues($pageId, $jobId, $voteYes, $voteNo, $skip, $complete, $rowId);
	}

	public static function remainingCount(array $skip_ids = [], int $userId = 0, string $visitorId = ''): int {
		return static::getDao()->getSensitiveArticleVoteRemainingCount($skip_ids, $userId, $visitorId);
	}

	public static function getAllActiveByJobId(int $job_id): array {
		$rows = static::getDao()->getAllSensitiveArticleVotes($job_id);
		$savs = [];
		foreach ($rows as $r) {
			$savs[] = static::newFromValues(
				$r->sav_page_id,
				$r->sav_job_id,
				$r->sav_vote_yes,
				$r->sav_vote_no,
				$r->sav_skip,
				$r->sav_complete,
				$r->sav_id
			);
		}
		return $savs;
	}

	/**
	 * Store the object in the database
	 */
	public function save(): bool
	{
		$this->checkForComplete();
		return static::getDao()->upsertSensitiveArticleVoteData($this);
	}

	/**
	 * completed scenarios:
	 * 2 yes = ACCEPT
	 * 3 no; 0 yes = REJECT
	 * 3 no; > 0 yes = UNRESOLVED
	 * 3 skips = UNRESOLVED
	 */
	protected function checkForComplete() {
		if ($this->complete) return;

		$message_type = self::completeStatusMessage($this);

		if (!empty($message_type)) {
			$this->complete = true;
			TopicTagging::log($this->pageId, $this->jobId, $message_type);
		}
	}

	public static function completeStatusMessage(SensitiveArticleVote $sav): string {
		$status_message = '';

		if (self::isApproved($sav)) {
			$status_message = self::STATUS_APPROVED;
		}
		elseif (self::isRejected($sav)) {
			$status_message = self::STATUS_REJECTED;
		}
		elseif (self::isUnresolved($sav)) {
			$status_message = self::STATUS_RESOLVED;
		}

		return $status_message;
	}

	public static function isApproved(SensitiveArticleVote $sav): bool {
		return $sav->voteYes >= self::VOTE_MAX_YES;
	}

	public static function isRejected(SensitiveArticleVote $sav): bool {
		return $sav->voteNo >= self::VOTE_MAX_NO && $sav->voteYes == 0;
	}

	public static function isUnresolved(SensitiveArticleVote $sav): bool {
		return ($sav->voteNo >= self::VOTE_MAX_NO && $sav->voteYes > 0) || $sav->skip >= self::MAX_SKIPS;
	}

	/**
	 * Access a single instance of SensitiveArticleDao
	 */
	protected static function getDao(): SensitiveArticleDao
	{
		if (!static::$dao) {
			static::$dao = new SensitiveArticleDao();
		}
		return static::$dao;
	}

}
