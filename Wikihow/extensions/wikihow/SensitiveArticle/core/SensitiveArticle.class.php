<?php

namespace SensitiveArticle;

/**
 * Instances represent a set of rows in the `sensitive_article` table
 */
class SensitiveArticle
{
	public $pageId; // int
	public $reasonIds; // int[]
	public $revId; // int
	public $userId; // int
	public $date; // string in TS_MW format

	protected static $dao = null; // SensitiveArticleDao

	private static $articles = []; // For memoization in hasReasons()

	protected function __construct() {}

	/**
	 * Create a SensitiveArticle from the given values
	 */
	protected static function newFromValues(int $pageId, array $reasonIds,
			int $revId, int $userId, string $date): SensitiveArticle
	{
		$article = new SensitiveArticle();
		$article->pageId = $pageId;
		$article->reasonIds = $reasonIds;
		$article->revId = $revId;
		$article->userId = $userId;
		$article->date = $date;

		return $article;
	}

	/**
	 * Load a SensitiveArticle from the database
	 */
	public static function newFromDB(int $pageId): SensitiveArticle
	{
		$reasonIds = [];
		$revId = 0;
		$userId = 0;
		$date = '';

		$res = static::getDao()->getSensitiveArticleData($pageId);
		foreach ($res as $row) {
			$reasonIds[] = (int) $row->sa_reason_id;
			$revId = (int) $row->sa_rev_id;
			$userId = (int) $row->sa_user_id;
			$date = $row->sa_date;
		}

		return static::newFromValues($pageId, $reasonIds, $revId, $userId, $date);
	}

	/**
	 * Store the object in the database
	 */
	public function save(): bool
	{
		$result = static::getDao()->deleteSensitiveArticleData($this->pageId);

		if (!empty($this->reasonIds)) {
			$result = static::getDao()->insertSensitiveArticleData($this);
		}

		Hooks::run( "SensitiveArticleEdited" , [$this->pageId, $this->reasonIds]);

		return $result;
	}

	/**
	 * True if the article has been tagged with any of the given reason IDs
	 */
	public static function hasReasons(int $pageId, array $reasonIds): bool
	{
		if (!isset(static::$articles[$pageId])) {
			static::$articles[$pageId] = static::newFromDB($pageId);
		}
		$sa = static::$articles[$pageId];
		return !empty(array_intersect($reasonIds, $sa->reasonIds));
	}

	public static function getSensitiveArticleCountByReasonId(int $reasonId): int {
		return static::getDao()->getSensitiveArticleCountByReasonId($reasonId);
	}

	public static function onSensitiveReasonDeleted(int $reasonId) {
		static::getDao()->deleteSensitiveArticleDataByReasonId($reasonId);
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
