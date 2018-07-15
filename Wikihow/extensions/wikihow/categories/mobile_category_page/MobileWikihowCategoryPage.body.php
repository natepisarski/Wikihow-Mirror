<?

class MobileWikihowCategoryPage extends CategoryPage {
	var $out = null;
	var $request = null;

	const NUM_SUBCATS = 5;

	function view() {
		global $wgSquidMaxage;

		if (Misc::isAltDomain()) {
			Misc::respondWith404();
		}

		$ctx = RequestContext::getMain();
		$this->out = $ctx->getOutput();

		if ($ctx->getUser()->isAnon()) {
			$this->out->setSquidMaxage($wgSquidMaxage);
		}

		$this->request = $ctx->getRequest();
		$this->title = $ctx->getTitle();

		if ($this->request->getVal('a', '') == 'more') {
			$this->getMoreArticles();
		} else if ($this->request->getVal('a', '') == 'sub') {
			$this->getMoreSubcategoryCarousels();
		} else {
			$this->render();
		}
	}

	function getMoreArticles() {
		$catId = $this->request->getVal('cat_id');
		$sortKey = $this->request->getVal('cat_last_sortkey');
		$isFeatured = $this->request->getVal('cat_last_page_is_featured');
		$t = Title::newFromID($catId, NS_CATEGORY);
		$catData = new CategoryData($t);
		$carousel = new CategoryCarousel($catData, false, false);

		$this->out->setArticleBodyOnly(true);
		$this->out->addHtml(json_encode($carousel->getMoreArticles($sortKey, $isFeatured)));
	}

	function getMoreSubcategoryCarousels() {
		$catData = new CategoryData($this->title);
		$subCats = $catData->getSubcategories();
		$afterCatId = $this->request->getVal('last_cat_id', 0);
		$afterTitleText = '';
		if ($afterCatId > 0) {
			$t = Title::newFromId($afterCatId, NS_CATEGORY);
			if ($t && $t->exists()) {
				$afterTitleText = $t->getText();
			}
		}

		$this->out->setArticleBodyOnly(true);
		$this->out->addHtml(implode("", $this->getSubcategoryCarouselsHtml($subCats, self::NUM_SUBCATS, $afterTitleText)));
	}

	function render() {
		$this->catData = new CategoryData($this->title);
		$this->addCSSAndJs();
		$this->renderCarousels();
	}

	function addCSSAndJs() {
		$out = RequestContext::getMain()->getOutput();
		$out->addModules(array('mobile.wikihow.mobile_category_page', 'wikihow.common.font-awesome'));
	}

	protected function getCarouselsHtml() {
		$carouselHtml = array();

		$subCats = $this->catData->getSubcategories();
		$isLeafNode = empty($subCats);

		// Main Category
		$isArticleView = $this->isArticleView();
		$articles = $this->catData->getAllData()['cat_articles']['articles'];
		if (!empty($articles)) {
			$carousel = new CategoryCarousel($this->catData, false, $isLeafNode, $isArticleView);
			$carouselHtml[] = $carousel->getCarouselHtml();
		}

		// Subcategories
		if (!$isArticleView) {
			$carouselHtml = array_merge($carouselHtml,  $this->getSubcategoryCarouselsHtml($subCats, 4));
		}

		if (!$carouselHtml) {
			Misc::respondWith404();
		}

		return implode("", $carouselHtml);
	}

	protected function renderCarousels() {
		$loader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__));
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$this->out->addHtml(
			$m->render(
				'mobile_category_page',
				array(
					'cat_parent_url' => $this->isArticleView() ? $this->catData->getUrl() : $this->catData->getParentCategoryUrl(),
					'cat_title' => $this->catData->getText(),
					'carousels' => $this->getCarouselsHtml(),
					'template' => $loader->load('../category_carousel/category_carousel_item')
				)
			)
		);
	}

	protected function isArticleView() {
		return $this->request->getVal(CategoryCarousel::ARTICLE_VIEW_PARAM, 0);
	}


	protected function getSubcategoryCarouselsHtml($subCats, $limit, $afterTitleText = '') {
		$carouselHtml = [];
		$i = 0;
		foreach ($subCats as $t) {
			if (strnatcasecmp($t->getText(), $afterTitleText) > 0) {
				$catData = new CategoryData($t);
				$data = $catData->getAllData();
				if (empty($data['cat_articles']['articles'])) {
					continue;
				}
				$carousel = new CategoryCarousel($catData, true, false);
				$carouselHtml[] = $carousel->getCarouselHtml();

				if (++$i == $limit) {
					break;
				}
			}
		}

		return $carouselHtml;
	}


}

