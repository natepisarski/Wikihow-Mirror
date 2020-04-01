<?php

/*
CREATE TABLE `sensitive_topic_job` (
	`stj_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`stj_topic` VARBINARY(255) NOT NULL DEFAULT '',
	`stj_question` VARBINARY(255) NOT NULL DEFAULT '',
	`stj_description` VARBINARY(255) NOT NULL DEFAULT '',
	`stj_enabled` TINYINT(4) NOT NULL DEFAULT 0,
	`stj_created` VARBINARY(14) NOT NULL DEFAULT '',
	KEY (`stj_topic`)
);
*/

namespace SensitiveArticle;

/**
 * Instances represent a set of rows in the `sensitive_topic_job` table
 */
class SensitiveTopicJob
{
	public $id; // int
	public $topic; // string
	public $question; // string
	public $description; // string
	public $enabled; // boolean
	public $dateCreated; // string in TS_MW format

	//the following only added after calling addCounts()
	public $article_count 		= 0; // int
	public $yes_count 				= 0; // int
	public $no_count 					= 0; // int
	public $unresolved_count 	= 0; // int

	const TABLE = 'sensitive_topic_job';

	protected static $dao = null; // SensitiveArticleDao

	public function __construct() {}

	/**
	 * Create a SensitiveTopicJob from the given values
	 */
	public static function newFromValues(int $id = 0, string $topic = '', string $question = '',
		string $description = '', bool $enabled = false, string $dateCreated = ''): SensitiveTopicJob
	{
		$job = new SensitiveTopicJob();
		$job->id = $id;
		$job->topic = $topic;
		$job->question = $question;
		$job->description = $description;
		$job->enabled = $enabled;
		$job->dateCreated = $dateCreated;

		return $job;
	}

	/**
	 * Load a SensitiveTopicJob from the database
	 */
	public static function newFromDB(int $id): SensitiveTopicJob
	{
		$res = static::getDao()->getTopicJob($id);

		$id = 0;
		$topic = '';
		$question = '';
		$description = '';
		$enabled = false;
		$dateCreated = '';

		foreach ($res as $row) {
			$id = (int) $row->stj_id;
			$topic = $row->stj_topic;
			$question = $row->stj_question;
			$description = $row->stj_description;
			$enabled = (int) $row->stj_enabled;
			$dateCreated = $row->stj_created;
		}

		return static::newFromValues($id, $topic, $question, $description, $enabled, $dateCreated);
	}

	public static function getAllTopicJobs(): array {
		$rows = static::getDao()->getAllTopicJobs();
		$jobs = [];
		foreach ($rows as $r) {
			$jobs[] = static::newFromValues(
				$r->stj_id,
				$r->stj_topic,
				$r->stj_question,
				$r->stj_description,
				$r->stj_enabled,
				$r->stj_created
			);
		}
		return $jobs;
	}

	public function isValid(): bool
	{
		return !empty(trim($this->topic)) && !empty(trim($this->question)) && !empty(trim($this->description));
	}

	// this is a little expensive, so only call it if you need the yes/no/unresolved/total counts
	// for all the articles in the Topic Tagging Tool for a certain job id
	public function addCounts()
	{
		if (empty($this->id)) return;

		$total_count = 0;
		$complete_count = 0;
		$complete_yes_count = 0;
		$complete_no_count = 0;

		$data = SensitiveArticleVote::getAllActiveByJobId($this->id);

		foreach ($data as $datum) {
			$total_count++;

			if ($datum->complete) {
				$complete_count++;

				if (SensitiveArticleVote::isApproved($datum))
					$complete_yes_count++;
				elseif (SensitiveArticleVote::isRejected($datum))
					$complete_no_count++;
			}
		}

		$this->article_count = $total_count;
		$this->yes_count = $complete_yes_count;
		$this->no_count = $complete_no_count;
		$this->unresolved_count = $complete_count - ($complete_yes_count + $complete_no_count);
	}

	/**
	 * Store the object in the database
	 */
	public function save(): bool
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->id > 0) {
			$res = static::getDao()->updateTopicJob($this);
		} else {
			$res = static::getDao()->insertTopicJob($this);
		}
		return $res;
	}

	public function newestJobId(): int {
		return static::getDao()->getNewestJobId();
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
