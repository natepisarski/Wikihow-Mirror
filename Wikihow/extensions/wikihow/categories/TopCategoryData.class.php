<?php

class TopCategoryData {
	const TABLE = "topcatdata";
	const HIGHTRAFFIC = 0;
	const FEATURED = 1;
	const MAX_INSERTS = 3000;
	const EARLIEST_FEATURED_DATE = "";

	static function getPagesForCategory($categoryName, $type, $maxPages = 100) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(self::TABLE, ['tcd_page_id'], ['tcd_category' => $categoryName, 'tcd_type' => $type], __METHOD__, ['LIMIT' => $maxPages, 'ORDER BY' => 'tcd_id DESC']);
		$pageIds = [];
		foreach ($res as $row) {
			$pageIds[] = $row->tcd_page_id;
		}

		return $pageIds;
	}


	static function setPagesForCategory($categoryName, $pageCount = 100) {
		//first do the high traffic pages
		$dbr = wfGetDB(DB_REPLICA);
		$oldPages = [];
		$res = $dbr->select(self::TABLE, ['tcd_id'], ['tcd_category' => $categoryName], __METHOD__);
		foreach ($res as $row) {
			$oldPages[] = $row->tcd_id;
		}
		$pages = [];
		$subcats = self::getFullSubcategories($categoryName);

		echo "There are " . count($subcats) . " categories in " . $categoryName . "\n";

		$subcatList = [];

		foreach ($subcats as $catInfo) {
			$subcatList[] = $catInfo['page_title'];
		}

		if (count($subcatList) == 0) {
			echo "No subcats, done!\n";
			return;
		}
		$pages = self::getPagesForCategoryArray($subcatList, $pageCount);

		//now sort all the pages
		usort($pages, function($a, $b) {
			return $a['ti_30day_views'] < $b['ti_30day_views'];
		});

		$pages = array_slice($pages, 0, $pageCount);

		$pageInserts = [];
		foreach ($pages as $page) {
			$pageInserts[] = ['tcd_category' => $categoryName, 'tcd_page_id' => $page['page_id'], 'tcd_type' => self::HIGHTRAFFIC];
		}

		//now need to add all the new pages
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(
			self::TABLE,
			$pageInserts,
			__METHOD__
		);

		//now delete all the old ones
		if (count($oldPages) > 0) {
			$dbw->delete(self::TABLE, ['tcd_id IN (' . $dbw->makeList($oldPages) . ')'], __METHOD__);
		}
	}

	static function setFeaturedArticlePages() {
		$dbr = wfGetDB(DB_REPLICA);
		$featuredArticles = [];
		$res = $dbr->select('categorylinks', 'cl_from', ['cl_to' => 'Featured-Articles'], __METHOD__);
		foreach ($res as $row) {
			$parentCatTitles = CategoryHelper::getTitleTopLevelCategories(Title::newFromID($row->cl_from));
			foreach ($parentCatTitles as $parentTitle) {
				$parentKey = $parentTitle->getDBKey();
				if (!isset($featuredArticles[$parentKey])) {
					$featuredArticles[$parentKey] = [];
				}
				$featuredArticles[$parentKey][$row->cl_from] = ['tcd_category' => $parentKey, 'tcd_page_id' => $row->cl_from, 'tcd_type' => self::FEATURED];
			}
		}

		//now grab all the ones we already have
		$toDelete = [];
		$res = $dbr->select(self::TABLE, ['tcd_id', 'tcd_category', 'tcd_page_id'], ['tcd_type' => self::FEATURED], __METHOD__);
		foreach ($res as $row) {
			if (isset($featuredArticles[$row->tcd_category])) {
				if (isset($featuredArticles[$row->tcd_category][$row->tcd_page_id])) {
					//It's in the list of new ones AND the old ones, so throw it out
					unset($featuredArticles[$row->tcd_category][$row->tcd_page_id]);
				} else {
					//it's not in the list of the new ones, so include it in the list ot be deleted
					$toDelete[] = $row->tcd_id;
				}
			}
		}

		$dbw = wfGetDB(DB_MASTER);
		if (count($toDelete) > 0) {
			echo "Deleting " . count($toDelete) . "rows from the featured articles lists\n";
			$dbw->delete(self::TABLE, ['tcd_id IN (' . $dbw->makeList($toDelete) . ')'], __METHOD__);
		}

		//now insert all the remaining ones into the db
		foreach ($featuredArticles as $catArray) {
			$loops = ceil(count($catArray) / self::MAX_INSERTS);
			echo "Adding " . count($catArray) . "rows from the featured articles lists\n";
			for ($i = 0; $i < $loops; $i++) {
				$insertArray = array_slice($catArray, $i * self::MAX_INSERTS, self::MAX_INSERTS);
				$dbw->insert(self::TABLE, $insertArray, __METHOD__);
			}
		}
	}

	static function getPagesForCategoryArray($catArray, $pageCount) {
		global $wgLanguageCode;

		$dbr = wfGetDB(DB_REPLICA);
		$pages = [];
		$titus_copy = WH_DATABASE_NAME_EN . '.titus_copy';
		$res = $dbr->select(
			['categorylinks', $titus_copy],
			['cl_from', 'ti_30day_views'],
			[
				'ti_page_id=cl_from',
				'cl_to' => $catArray,
				'ti_language_code' => $wgLanguageCode
			],
			__METHOD__,
			[
				'LIMIT' => $pageCount,
				'ORDER BY' => 'ti_30day_views DESC',
				'GROUP BY' => 'cl_from'
			]
		);

		foreach ($res as $row) {
			$pages[$row->cl_from] = ['page_id' => $row->cl_from, 'ti_30day_views' => intval($row->ti_30day_views)];
		}

		return $pages;
	}

	static function getFullSubcategories($categoryName, $subcats = []) {
		if (array_key_exists($categoryName, $subcats)) {
			return [];
		}
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select (
			['categorylinks', 'page', 'index_info'],
			['page_title', 'page_id'],
			[
				'page_id=cl_from',
				'cl_to' => $categoryName,
				'page_namespace' => NS_CATEGORY,
				'ii_policy IN (1, 4)'
			],
			__METHOD__,
			[],
			['index_info' => ['LEFT JOIN', 'ii_page=page_id']]
		);

		foreach ($res as $row) {
			$subcats[$row->page_title] = ['page_title' => $row->page_title, 'page_id' => $row->page_id];
			$subcats = array_merge($subcats, self::getFullSubcategories($row->page_title, $subcats));
		}

		return $subcats;
	}
}

/*****
CREATE TABLE `topcatdata` (
`tcd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`tcd_category` varbinary(255) NOT NULL default '',
`tcd_page_id` int(10) unsigned NOT NULL default 0,
`tcd_type` tinyint(2) unsigned NOT NULL default 0,
PRIMARY KEY  (`tcd_id`),
KEY cat_type (`tcd_category`, `tcd_type`)
) ENGINE=InnoDB;
*****/
