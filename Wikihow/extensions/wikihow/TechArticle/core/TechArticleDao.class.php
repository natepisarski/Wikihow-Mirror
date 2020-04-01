<?php

namespace TechArticle;

use Iterator;
use EmptyIterator;

use ResultWrapper;

use Misc;

/**
 * Data Access Object for the `tech_article` table
 */
class TechArticleDao {

	const FIELDS = ['tar_page_id', 'tar_rev_id', 'tar_user_id', 'tar_product_id', 'tar_platform_id', 'tar_tested', 'tar_date'];

	public function getTechArticleData(int $pageId): Iterator {
		$res = wfGetDB(DB_REPLICA)->select(
			'tech_article', static::FIELDS, ['tar_page_id' => $pageId]
		);
		return $res ?? new EmptyIterator();
	}

	public function insertTechArticleData(TechArticle $ta): bool {
		$date = wfTimestampNow();
		$rows = [];
		foreach ($ta->platforms as $platform) {
			$rows[] = [
				'tar_page_id' => $ta->pageId,
				'tar_rev_id' => $ta->revId,
				'tar_user_id' => $ta->userId,
				'tar_product_id' => $ta->productId,
				'tar_platform_id' => (int) $platform['id'],
				'tar_tested' => (bool) $platform['tested'],
				'tar_date' => $date,
			];
		}
		return wfGetDB(DB_MASTER)->insert('tech_article', $rows);
	}

	/**
	 * @return ResultWrapper|bool
	 */
	public function deleteTechArticleData(int $pageId) {
		return wfGetDB(DB_MASTER)->delete('tech_article', ['tar_page_id' => $pageId]);
	}

}
