<?php

/*
CREATE TABLE `sensitive_article_vote` (
	`sav_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`sav_page_id` INT(10) NOT NULL DEFAULT 0,
	`sav_reason_id` INT(10) NOT NULL DEFAULT 0,
	`sav_vote_yes` INT(4) NOT NULL DEFAULT 0,
	`sav_vote_no` INT(4) NOT NULL DEFAULT 0,
	`sav_skip` INT(4) NOT NULL DEFAULT 0,
	`sav_complete` TINYINT(4) NOT NULL DEFAULT 0,
	`sav_created` VARBINARY(14) NOT NULL DEFAULT '',
	UNIQUE KEY `page_reason_pair` (`sav_page_id`,`sav_reason_id`),
	KEY (`sav_reason_id`),
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
	public $reasonId; // int
	public $voteYes; // int
	public $voteNo; // int
	public $skip; //int
	public $complete; //bool
	public $dateCreated; // string in TS_MW format

	const TABLE 						= 'sensitive_article_vote';
	const VOTE_POWER_VOTER 	= 2;
	const VOTE_MAX_VOTES 		= 15;
	const VOTE_RESOLVE_DIFF	= 3;
	const MAX_SKIPS					= 5;

	const STATUS_APPROVED		= 'approved';
	const STATUS_REJECTED		= 'rejected';
	const STATUS_RESOLVED		= 'resolved';

	protected static $dao = null; // SensitiveArticleDao

	public function __construct() {}

	/**
	 * Create a SensitiveArticleVote from the given values
	 */
	public static function newFromValues(int $pageId, int $reasonId, int $voteYes = 0, int $voteNo = 0,
		int $skip = 0, bool $complete = false, string $dateCreated = '', int $rowId = 0): SensitiveArticleVote
	{
		$articleVote = new SensitiveArticleVote();
		$articleVote->rowId = $rowId;
		$articleVote->pageId = $pageId;
		$articleVote->reasonId = $reasonId;
		$articleVote->voteYes = $voteYes;
		$articleVote->voteNo = $voteNo;
		$articleVote->skip = $skip;
		$articleVote->complete = $complete;
		$articleVote->dateCreated = $dateCreated;

		return $articleVote;
	}

	/**
	 * Load a SensitiveArticleVote from the database
	 */
	public static function newFromDB(int $pageId, int $reasonId): SensitiveArticleVote
	{
		$res = static::getDao()->getSensitiveArticleVoteData($pageId, $reasonId);

		$rowId = 0;
		$voteYes = 0;
		$voteNo = 0;
		$skip = 0;
		$complete = false;
		$dateCreated = '';

		foreach ($res as $row) {
			$rowId = (int) $row->sav_id;
			$voteYes = (int) $row->sav_vote_yes;
			$voteNo = (int) $row->sav_vote_no;
			$skip = (int) $row->sav_skip;
			$complete = (int) $row->sav_complete;
			$dateCreated = $row->sav_created;
		}

		return static::newFromValues($pageId, $reasonId, $voteYes, $voteNo, $skip, $complete, $dateCreated, $rowId);
	}

	public static function getNextArticleVote(int $reasonId, array $skip_ids = [],
		int $userId, string $visitorId): SensitiveArticleVote
	{
		$res = static::getDao()->getNextSensitiveArticleVoteData($reasonId, $skip_ids, $userId, $visitorId);

		$rowId = 0;
		$pageId = 0;
		$voteYes = 0;
		$voteNo = 0;
		$skip = 0;
		$complete = false;
		$dateCreated = '';

		foreach ($res as $row) {
			$rowId = (int) $row->sav_id;
			$pageId = (int) $row->sav_page_id;
			$reasonId = (int) $row->sav_reason_id;
			$voteYes = (int) $row->sav_vote_yes;
			$voteNo = (int) $row->sav_vote_no;
			$skip = (int) $row->sav_skip;
			$complete = (int) $row->sav_complete;
			$dateCreated = $row->sav_created;
		}

		return static::newFromValues($pageId, $reasonId, $voteYes, $voteNo, $skip, $complete, $dateCreated, $rowId);
	}

	public static function remainingCount(array $skip_ids = [], int $userId = 0, string $visitorId = ''): int {
		return static::getDao()->getSensitiveArticleVoteRemainingCount($skip_ids, $userId, $visitorId);
	}

	public static function getAllActiveByReasonId(int $reason_id): array {
		$rows = static::getDao()->getAllSensitiveArticleVotes($reason_id);
		$savs = [];
		foreach ($rows as $r) {
			$savs[] = static::newFromValues(
				$r->sav_page_id,
				$r->sav_reason_id,
				$r->sav_vote_yes,
				$r->sav_vote_no,
				$r->sav_skip,
				$r->sav_complete,
				$r->sav_created,
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
	 * - 3 more yes than no = approved
	 * - 3 more no than yes = rejected
	 * - 15 votes w/o resolution = resolved without clear consensus
	 */
	protected function checkForComplete() {
		if ($this->complete) return;

		$message_type = self::completeStatusMessage($this);

		if (!empty($message_type)) {
			$this->complete = true;
			TopicTagging::log($this->pageId, $this->reasonId, $message_type);
		}

		if ($message_type == self::STATUS_APPROVED) $this->addSensitiveTopic();
	}

	public static function completeStatusMessage(SensitiveArticleVote $sav): string {
		$status_message = '';

		if ($sav->voteYes - $sav->voteNo >= self::VOTE_RESOLVE_DIFF) {
			$status_message = self::STATUS_APPROVED;
		}
		elseif ($sav->voteNo - $sav->voteYes >= self::VOTE_RESOLVE_DIFF) {
			$status_message = self::STATUS_REJECTED;
		}
		elseif ($sav->voteYes + $sav->voteNo >= self::VOTE_MAX_VOTES || $sav->skip >= self::MAX_SKIPS) {
			$status_message = self::STATUS_RESOLVED;
		}

		return $status_message;
	}

	protected function addSensitiveTopic() {
		$title = \Title::newFromId($this->pageId);
		if ($title) $rev = \Revision::newFromTitle($title);
		$revId = $rev ? $rev->getId() : 0;

		$reasonIds = $sa->reasonIds;
		if (empty($reasonIds)) $reasonIds = [];
		$reasonIds[] = $this->reasonId;

		$userId = \RequestContext::getMain()->getUser()->getId();

		$sa = SensitiveArticle::newFromDB($this->pageId);
		$sa->revId = $revId;
		$sa->userId = $userId;
		$sa->reasonIds = $reasonIds;
		$sa->date = wfTimestampNow();
		$sa->save();
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
