<?php

class CategoryCarousel {
	var $catData = null;
	var $isSubCategory = null;

	const ARTICLE_VIEW_PARAM = "av";
	const DEFAULT_THUMB_IMG = '/images/thumb/b/b5/Default_wikihow_green.png/-crop-127-120-127px-Default_wikihow_green.png';

	public function __construct(CategoryData $data, $isSubcategory, $isLeafNode, $isArticleView = false) {
		$this->catData = $data;
		$this->isArticleView = $isArticleView;
		$this->isLeafNode = $isLeafNode;
		$this->isSubCategory = $isSubcategory;
	}

	public function getCarouselHtml() {
		RequestContext::getMain()->getOutput()->addModules(array('mobile.wikihow.category_carousel'));

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__)),
		);
		$m = new Mustache_Engine($options);
		return $m->render('category_carousel', $this->getFormattedData(''));
	}

	public static function getCategoryListingHtml($listingData) {

		RequestContext::getMain()->getOutput()->addModules(array('mobile.wikihow.category_carousel'));

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__)),
		);
		$m = new Mustache_Engine($options);
		return $m->render('category_listing_carousel', $listingData);
	}

	public function getMoreArticles($sortKey, $isFeatured) {
		$data = $this->getFormattedData($sortKey, $isFeatured);
		$howto_prefix = $data['howto_prefix'];
		$data = $data['cat_articles'];
		$data['howto_prefix'] = $howto_prefix;
		$data['default_image'] = wfGetPad(self::DEFAULT_THUMB_IMG);

		return $data;
	}

	protected function getFormattedData($sortKey, $includeFeatured = true) {
		$data = $this->catData->getAllData($sortKey, $includeFeatured);
		if (!$this->isSubCategory) {
			$data['url'] .= "?" . self::ARTICLE_VIEW_PARAM . "=1";
		}
		$data['subcat'] = $this->isSubCategory ? "1" : "0";
		$data['cat_articles']['articles'] = $this->truncateTitles($data['cat_articles']['articles']);
		$data['cat_articles']['articles'] = $this->removeArticleSortKeys($data['cat_articles']['articles']);
		$data['leaf_node'] = $this->isLeafNode ? "1" : "0";
		$data['article_view'] = $this->isArticleView ? "1" : "0";
		$data['default_image'] = wfGetPad(self::DEFAULT_THUMB_IMG);
		$data['all_articles_title'] = wfMessage('cat_all_articles')->text();

		return $data;
	}

	protected function removeArticleSortKeys($data) {
		foreach ($data as $i => $a) {
			unset($a['cl_sortkey']);
			$data[$i] = $a;
		}

		return $data;
	}

	protected function truncateTitles($articles) {
		foreach ($articles as $i => $a) {
			$a['title'] = mb_strlen($a['title']) > 35 ?
				mb_substr($a['title'], 0, 32) . '...' : $a['title'];
			$articles[$i] = $a;
		}

		return $articles;
	}
}
