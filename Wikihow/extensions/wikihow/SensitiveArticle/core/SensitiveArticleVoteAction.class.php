<?php

/*
CREATE TABLE `sensitive_article_vote_action` (
	`sava_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`sava_sav_id` INT(10) NOT NULL DEFAULT 0,
	`sava_user_id` INT(10) NOT NULL DEFAULT 0,
	`sava_visitor_id` VARBINARY(20) NOT NULL DEFAULT '',
	`sava_vote` TINYINT(4) NOT NULL DEFAULT 0,
	`sava_timestamp` VARBINARY(14) NOT NULL DEFAULT '',
	KEY (`sava_sav_id`),
	KEY (`sava_user_id`),
	KEY (`sava_visitor_id`)
);
*/

namespace SensitiveArticle;

/**
 * Instances represent a set of rows in the `sensitive_article_vote_action` table
 */
class SensitiveArticleVoteAction
{
	public $savId; //int
	public $userId; //int
	public $visitorId; //string
	public $vote; //bool
	public $date; //string in TS_MW format

	const TABLE = 'sensitive_article_vote_action';

	protected static $dao = null; // SensitiveArticleDao

	public function __construct() {}

	/**
	 * Create a SensitiveArticleVoteAction from the given values
	 */
	public static function newFromValues(int $savId, int $userId, string $visitorId, bool $vote, string $date): SensitiveArticleVoteAction
	{
		$voteAction = new SensitiveArticleVoteAction();
		$voteAction->savId = $savId;
		$voteAction->userId = $userId;
		$voteAction->visitorId = $visitorId;
		$voteAction->vote = $vote;
		$voteAction->date = $date;

		return $voteAction;
	}

	public static function markVoted(int $savId, int $vote): bool {
		$userId = \RequestContext::getMain()->getUser()->getID();
		$visitorId = empty($userId) ? \WikihowUser::getVisitorId() : '';

		$sava = static::newFromValues($savId, $userId, $visitorId, $vote, '');
		return $sava->save();
	}

	/**
	 * Store the object in the database
	 */
	public function save(): bool
	{
		return static::getDao()->insertSensitiveArticleVoteActionData($this);
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
