<?php

class MobileWikihowCategoryPage extends CategoryPage {
	var $out = null;
	var $request = null;
	var $viewer = null;

	const NUM_SUBCATS = 5;

	function view() {
		global $wgSquidMaxage;

		if (Misc::isAltDomain()) {
			Misc::exitWith404();
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
		} elseif ($this->request->getVal('a', '') == 'fill') {
			$this->getSubcategoryArticles();
		} else {
			$this->render();
		}
	}

	function getSubcategoryArticles() {
		$catId = $this->request->getVal('cat_id');
		$title = Title::newFromID($catId, NS_CATEGORY);
		$viewer = new WikihowCategoryViewer($title, $this->getContext());
		$viewer->doQuery(false);
		if ($viewer->getNumArticles() <= 0) {
			$this->out->setArticleBodyOnly(true);
			$this->out->addHtml("");
		}
		$catData = new CategoryData($title, 1);
		$data = $this->getData(false, 1, $catData, true, $viewer);
		$carousel = new CategoryCarousel($viewer, true, []);
		$this->out->setArticleBodyOnly(true);
		$this->out->addHtml($carousel->getArticlesOnly($data));
	}

	function getMoreArticles() {
		$catId = $this->request->getVal('cat_id');
		$lastPage = $this->request->getVal('cat_last_page');
		$t = Title::newFromID($catId, NS_CATEGORY);

		$viewer = new WikihowCategoryViewer($t, $this->getContext());
		$viewer->doQuery();

		$catData = new CategoryData($t, ($lastPage+1));
		$data = $this->getData(false, ($lastPage+1), $catData, true, $viewer);

		$carousel = new CategoryCarousel($data, true, []);

		$this->out->setArticleBodyOnly(true);
		$this->out->addHtml(json_encode($carousel->getMoreArticles()));
	}

	function render() {
		$page = $this->request->getInt('pg', 1);
		//don't have a ?pg=1 page
		if ($this->request->getInt('pg') == 1) {
			$this->out->redirect($this->title->getFullURL());
		}
		if ($page > 1) {
			$this->out->setRobotPolicy('noindex');
		}

		$this->viewer = new WikihowCategoryViewer($this->title, $this->getContext());
		$this->viewer->clearCategoryState();
		$this->viewer->doQuery(); //we still do this call even if we don't want FA section on this page b/c it initializes the article viewer object
		$this->addCSSAndJs();
		$this->renderCarousels($page);
	}

	function addCSSAndJs() {
		$out = RequestContext::getMain()->getOutput();
		$out->addModules(array('mobile.wikihow.mobile_category_page', 'wikihow.common.font-awesome'));
	}

	protected function getCarouselsHtml($page) {
		$carouselHtml = array();

		$subCats = $this->viewer->getChildren();

		// Main Category
		$catData = new CategoryData($this->title, $page);
		$data = $this->getData(true, $page, $catData, true, $this->viewer);
		$carousel = new CategoryCarousel($data, false, [], true);
		$carouselHtml[] = $carousel->getCarouselHtml(CategoryData::ALL_ARTICLES_CHUNK);

		if ($this->viewer->getNumOnPage($page, CategoryData::ALL_ARTICLES_CHUNK) < 20) {
			$maxSubsToFill = self::NUM_SUBCATS;
		} else {
			$maxSubsToFill = 0;
		}

		// Subcategories
		$carouselHtml = array_merge($carouselHtml,  $this->getSubcategoryCarouselsHtml($subCats, $maxSubsToFill));

		if (!$carouselHtml) {
			Misc::exitWith404();
		}

		return [
			'cat_parent_url' => $catData->getParentCategoryUrl(),
			'cat_title' => $catData->getText(),
			'carousels' => implode("", $carouselHtml)
		];
	}

	protected function getData($isMainCategory, $currentPage, CategoryData $catData, $getArticles, $viewer) {
		$data = [
			'howto_prefix' => wfMessage('howto_prefix')->showIfExists(),
			'cat_title' => $catData->getText(),
			'cat_id' => $catData->getArticleID(),
			'url' => $catData->getUrl(),
			'parent_url' => $catData->getParentCategoryUrl()
		];

		if ($isMainCategory) {
			if ($getArticles) {
				$data['cat_articles'] = $viewer->getArticlesMobile($currentPage, CategoryData::ALL_ARTICLES_CHUNK);
				$data = array_merge($data, $catData->getPagination($viewer->getNumArticles()));
				$data['cat_articles']['last_page'] = $currentPage;
			} else {
				$data['cat_articles']['articles'] = [];
				$data['cat_articles']['last_page'] = $currentPage-1;
			}
		} else {
			if ($getArticles) {
				$data['cat_articles'] = $viewer->getArticlesMobile($currentPage, CategoryData::SUB_CAT_CHUNK);
				$data['cat_articles']['last_page'] = $currentPage;
			} else {
				$data['cat_articles']['articles'] = [];
				$data['cat_articles']['last_page'] = $currentPage-1;
			}
		}

		return $data;
	}

	protected function renderCarousels($page) {
		$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$this->out->addHtml(
			$m->render(
				'mobile_category_page.mustache',
				array_merge($this->getCarouselsHtml($page), ['template' => $loader->load('../category_carousel/category_carousel_item')])
			)
		);
	}


	protected function getSubcategoryCarouselsHtml($subCats, $limit = 100) {
		$carouselHtml = [];
		$subcatsArray = [];
		foreach ($subCats as $subcat) {
			if ($subcat instanceof Title) {
				if ($subcat->getArticleID() != $this->title->getArticleID()) {
					$subcatsArray[] = ['title' => $subcat, 'subsubcats' => []];
					$topLevelSubcats[] = $subcat;
				}
			}
			elseif (count($subcat) == 1) {
				if ($subcat[0] instanceof Title) {
					$subcatsArray[] = ['title' => $subcat[0], 'subsubcats' => []];
					$topLevelSubcats[] = $subcat[0];
				}
			} elseif (count($subcat) == 2) {
				$subsubcatsArray = [];
				if (is_array($subcat[1])) {
					foreach ($subcat[1] as $t) {
						$subsubcatsArray[] = $t;
					}
				}
				$subcatsArray[] = ['title' => $subcat[0], 'hasSubsubcats' => true, 'subsubcats' => $subsubcatsArray];
				$topLevelSubcats[] = $subcat[0];
			}
		}

		foreach ($subcatsArray as $index => $subcats) {
			$catData = new CategoryData($subcats['title'], 1);
			if ($index < $limit) {
				$viewer = new WikihowCategoryViewer($subcats['title'], $this->getContext());
				$viewer->doQuery();
				$data = $this->getData(false, 1, $catData, true, $viewer);
			} else {
				$data = $this->getData(false, 1, $catData, false, null);
			}
			$data['subcategories'] = $subcats['subsubcats'];


			$carousel = new CategoryCarousel($data, true, $subcats['subsubcats']);
			$carouselHtml[] = $carousel->getCarouselHtml();
		}

		return $carouselHtml;
	}


}

