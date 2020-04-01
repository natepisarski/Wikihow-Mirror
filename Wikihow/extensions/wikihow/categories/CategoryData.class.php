<?php

class CategoryData {
	var $t = null;
	var $data = null;
	var $currentPage = null;

	const CAT_LISTING_IMG_WIDTH = 201;
	const CAT_LISTING_IMG_HEIGHT = 134;

	const CAT_IMG_WIDTH = 127;
	const CAT_IMG_HEIGHT = 120;

	const ALL_ARTICLES_CHUNK = 100;
	const SUB_CAT_CHUNK = 24;

	const LISTING_TABLE = 'categorylisting';

	function __construct(Title $title, int $currentPage = 1) {
		$this->t = $title;
		$this->currentPage = $currentPage;
	}

	public function getText() {
		return $this->t->getText();
	}

	public function getUrl() {
		return $this->t->getLocalUrl();
	}

	public function getArticleID() {
		return $this->t->getArticleID();
	}

	public function getParentCategoryUrl() {
		$parent = array_pop(array_keys($this->t->getParentCategories()));
		$t = Title::newFromText($parent, NS_CATEGORY);

		return $t ? $t->getLocalUrl() : SpecialPage::getTitleFor('CategoryListing')->getLocalURL();
	}

	public function getJSON() {
		return json_encode($this->getAllData());
	}

	public function getPagination($numArticles ) {
		$numPages = (self::ALL_ARTICLES_CHUNK > 0) ? ceil($numArticles / self::ALL_ARTICLES_CHUNK) : 0;
		$thisUrl = $this->getUrl();

		$data = [];

		if ($numArticles > self::ALL_ARTICLES_CHUNK) {
			$data['has_pagination'] = true;
			$data['next_text'] = wfMessage('lsearch_next')->text();
			$data['prev_text'] = wfMessage('lsearch_previous')->text();
			if ($this->currentPage > 1) {
				$data['prev_url'] = "?pg=" . ($this->currentPage-1);
				$data['prev_class'] = "";
			} else {
				$data['prev_url'] = "#";
				$data['prev_class'] = "disabled";
			}
			if ($this->currentPage < $numPages) {
				$data['next_url'] = "?pg=".($this->currentPage+1);
				$data['next_class'] = "";
			}
			else {
				$data['next_url'] = "#";
				$data['next_class'] = "disabled";
			}

			$data['pages'] = [];
			for ($i=1; $i<=$numPages; $i++) {
				$data['pages'][$i-1] = ['rel' => ''];
				if ($i == ($this->currentPage-1)) {
					$data['pages'][$i-1]['rel'] = 'rel="prev"';
				}
				elseif ($i == ($this->currentPage+1)) {
					$data['pages'][$i-1]['rel'] = 'rel="next"';
				}

				$data['pages'][$i-1]['text'] = $i;
				if ($this->currentPage != $i) {
					if ($i == 1) {
						$data['pages'][$i - 1]['page_url'] = $thisUrl;
					} else {
						$data['pages'][$i - 1]['page_url'] = "$thisUrl?pg=$i";
					}
				}
			}
			return $data;
		}

		return ['has_pagination' => "0"];
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

		$catmap = CategoryHelper::getIconMap();
		ksort($catmap);

		$catData = ["subcats" => []];
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
					$catData["subcats"][] = [
						'url' => $catTitle->getLocalURL($queryString),
						'img_url' => wfGetPad($thumb->getUrl()),
						'cat_title' => $category
					];

				}


			}
		}
		return $catData;
	}

	private function getCategoryListingInfo() {
		$dbr = wfGetDB(DB_REPLICA);
		$dbr->select(
			self::LISTING_TABLE,
			'*',
			[],
			__METHOD__
		);

	}
}
