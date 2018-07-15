<?php

class CategoryData {
	var $t = null;
	var $data = null;

	const CAT_LISTING_IMG_WIDTH = 201;
	const CAT_LISTING_IMG_HEIGHT = 134;

	const CAT_IMG_WIDTH = 127;
	const CAT_IMG_HEIGHT = 120;

	function __construct(Title $title) {
		$this->t = $title;
	}

	public function getText() {
		return $this->t->getText();
	}

	public function getUrl() {
		return $this->t->getLocalUrl();
	}

	public function getParentCategoryUrl() {
		$parent = array_pop(array_keys($this->t->getParentCategories()));
		$t = Title::newFromText($parent, NS_CATEGORY);

		return $t ? $t->getLocalUrl() : SpecialPage::getTitleFor('CategoryListing')->getLocalURL();
	}

	public function getJSON() {
		return json_encode($this->getAllData());
	}

	public function getAllData($sortKey = '', $withFeatured = true) {
		return array(
			'howto_prefix' => wfMessage('howto','')->text(),
			'cat_title' => $this->getText(),
			'cat_id' => $this->t->getArticleID(),
			'url' => $this->getUrl(),
			'parent_url' => $this->getParentCategoryUrl(),
			'cat_articles' => $this->getArticles($sortKey, $withFeatured),
			'subcategories' => $this->getSubcategories()
		);
	}

	protected function getArticles($sortKey = '', $withFeatured = true) {
		$rows = array();

		$limit = 48;
		if ($withFeatured) {
			$rows = array_merge($rows, $this->getArticlesFromDB($sortKey, $limit, true));
		}

		$additionalRows = $limit - sizeof($rows);
		if ($additionalRows) {
			// If we've already pulled some from featured articles, reset the sortkey so
			// we can pull non-featured articles starting from the beginning
			if ($additionalRows != $limit) {
				$sortKey = "";
			}

			$rows = array_merge($rows, $this->getArticlesFromDB($sortKey, $additionalRows, false));
		}

		return $this->getArticlesData($rows);
	}

	protected function getArticlesFromDB($sortKey = '', int $limit, $featured) {
		global $wgUser;

		// Show only indexable articles to anons
		$indexConds = $wgUser->isAnon() ? 'AND ii_policy IN (1, 4)' : '';

		$dbr = wfGetDB(DB_SLAVE);
		if (empty($sortKey)) {
			$sortKeyWhere = '';
		} else {
			$sortKeyWhere = " AND cl_sortkey > " . $dbr->addQuotes($sortKey);
		}

		$featuredWhere = " AND page_is_featured = " . intVal($featured);

		$sql = "SELECT cl_sortkey, page_id, page_title, page_namespace, page_is_featured
			FROM (page, categorylinks )
			LEFT JOIN index_info
			  ON ii_page = page_id
			WHERE
				cl_from = page_id
				AND cl_to = " . $dbr->addQuotes($this->t->getDBKey()) . "
				AND page_namespace != " . NS_CATEGORY . "
				$sortKeyWhere
				$featuredWhere
				$indexConds
			GROUP BY page_id
			ORDER BY page_is_featured DESC, cl_sortkey
			LIMIT $limit";


		$res = $dbr->query($sql, __METHOD__);

		$rows = array();
		foreach ($res as $row) {
			if (wfRunHooks('WikihowCategoryViewerQueryBeforeProcessTitle', [$row->page_id])) {
				$rows[] = $row;
			}
		}

		return $rows;
	}



	public function getSubcategories() {
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select(
			array('categorylinks', 'page'),
			array('page_title', 'page_namespace'),
			array('page_id=cl_from',
				'cl_to' => $this->t->getDBKey(),
				'page_namespace=' . NS_CATEGORY
			),
			__METHOD__,
			array('ORDER BY' => 'LOWER(page_title)')
		);
		$results = array();
		foreach ($res as $row) {
			$results[] = Title::makeTitle($row->page_namespace, $row->page_title);
		}

		return $results;
	}

	private function getArticleData($row) {
		$data = get_object_vars($row);
		$t = Title::newFromID($data['page_id'], $data['page_namespace']);
		$data['url'] = $t->getLocalUrl();
		$data['title'] =  $t->getText();
		$data['thumb_url'] = wfGetPad($this->getThumbUrl($t, self::CAT_IMG_WIDTH, self::CAT_IMG_HEIGHT));
		return $data;
	}

	protected function getArticlesData($res){
		$articles = array();
		foreach ($res as $i => $row) {
			$articles[] = $this->getArticleData($row);
		}



		return array(
			'articles' => $articles,
			'last_sortkey' => end($articles)['cl_sortkey'],
			'last_page_is_featured' => strval(end($articles)['page_is_featured'])
		);
	}

	protected function getThumbUrl($title, $width, $height) {
		$file = Wikitext::getTitleImage($title);
		$thumbUrl = "";
		if (!$file || !$file->exists()) {
			$file = Wikitext::getDefaultTitleImage($title);
		}

		if ($file && $file->exists()) {
			// Use same transform params as "Related Images"
			$params = array(
				'width' => $width,
				'height' => $height,
				'crop' => 1
			);
			$thumb = $file->transform($params, 0);
		}

		if ($thumb) {
			$thumbUrl = $thumb->getUrl();
		}

		return $thumbUrl;
	}

	public static function getCategoryListingData() {
		$queryString = WikihowCategoryViewer::getViewModeParam();

		$catmap = Categoryhelper::getIconMap();
		ksort($catmap);

		$catData = array();
		foreach ($catmap as $cat => $image) {
			$title = Title::newFromText($image);

			if ($title) {
				$file = wfFindFile($title, false);
				if (!$file) continue;

				$params = array(
					'width' => self::CAT_LISTING_IMG_WIDTH,
					'height' => self::CAT_LISTING_IMG_HEIGHT,
					'crop' => 1
				);
				$thumb = $file->transform($params, 0);

				$category = urldecode(str_replace("-", " ", $cat));
				$catTitle = Title::newFromText($category, NS_CATEGORY);
				if ($catTitle) {
					$catData[] = array(
						'url' => $catTitle->getLocalURL($queryString),
						'img_url' => wfGetPad($thumb->getUrl()),
						'cat_title' => $category
					);
				}
			}
		}
		return $catData;
	}
}
