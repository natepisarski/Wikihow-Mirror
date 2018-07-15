<?php

namespace TechArticle;

use WikiPage;

/**
 * Instances represent a set of rows in the `tech_article` table
 */
class TechArticle {

	private static $dao = null; // TechArticleDao

	public $pageId; // int
	public $revId; // int
	public $userId; // int
	public $productId; // int
	public $platforms; // array with items like: [ int 'id', bool 'tested' ]
	public $date; // string in TS_MW format

	protected function __construct() {}

	/**
	 * Create a TechArticle from the given values
	 */
	public static function newFromValues(int $pageId, int $revId, int $userId, int $productId,
			array $platforms, string $date = ''): TechArticle {

		$techArticle = new TechArticle();
		$techArticle->pageId = $pageId;
		$techArticle->revId = $revId;
		$techArticle->userId = $userId;
		$techArticle->productId = $productId;
		$techArticle->platforms = $platforms;
		$techArticle->date = $date;

		return $techArticle;
	}

	/**
	 * Load a TechArticle from the database
	 */
	public static function newFromDB(int $pageId): TechArticle {
		$revId = 0;
		$userId = 0;
		$productId = 0;
		$platforms = [];
		$date = '';

		$res = static::getDao()->getTechArticleData($pageId);
		foreach ($res as $row) {
			$revId = (int) $row->tar_rev_id;
			$userId = (int) $row->tar_user_id;
			$productId = (int) $row->tar_product_id;
			$platforms[] = [
				'id' => (int) $row->tar_platform_id,
				'tested' => (bool) $row->tar_tested
			];
			$date = $row->tar_date;
		}

		return static::newFromValues($pageId, $revId, $userId, $productId, $platforms, $date);
	}

	/**
	 * Whether the article has tech metadata (i.e. it is a "tech article")
	 */
	public function hasTechData(): bool {
		return $this->revId > 0 && $this->productId > 0 && $this->platforms;
	}

	/**
	 * Whether all platforms have been tested
	 */
	public function isFullyTested(): bool {
		if (!$this->hasTechData()) {
			return false;
		}

		foreach ($this->platforms as $platform) {
			if (!$platform['tested']) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Store the object in the database
	 */
	public function save(): bool {
		if (!$this->hasTechData()) {
			return false;
		}

		static::getDao()->deleteTechArticleData($this->pageId);
		$result = static::getDao()->insertTechArticleData($this);

		if ($result) {
			$this->date = wfTimestampNow();
		}
		return $result;
	}

	/**
	 * Access a single instance of TechArticleDao
	 */
	private static function getDao(): TechArticleDao {
		if (!static::$dao) {
			static::$dao = new TechArticleDao();
		}
		return static::$dao;
	}

}
