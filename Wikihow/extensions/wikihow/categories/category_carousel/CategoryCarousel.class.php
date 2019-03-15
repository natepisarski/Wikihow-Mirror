<?php

class CategoryCarousel {
	var $isSubCategory = null;
	var $showArticles = null;
	var $subcategories = null;
	var $data = null;

	const DEFAULT_THUMB_IMG = '/images/thumb/b/b5/Default_wikihow_green.png/-crop-127-120-127px-Default_wikihow_green.png';

	public function __construct($data, $isSubcategory, $subcategories = []) {
		$this->isSubCategory = $isSubcategory;
		$this->subcategories = $subcategories;
		$this->data = $data;
	}

	public function getCarouselHtml() {
		RequestContext::getMain()->getOutput()->addModules(array('mobile.wikihow.category_carousel'));

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);
		$data = $this->getFormattedData();
		return $m->render('category_carousel', $data);
	}

	public static function getCategoryListingHtml($listingData) {

		RequestContext::getMain()->getOutput()->addModules(array('mobile.wikihow.category_carousel'));

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);
		return $m->render('category_listing_carousel', $listingData);
	}

	public function getMoreArticles() {

		$data = $this->data['cat_articles'];
		$data['howto_prefix'] = $this->data['howto_prefix'];
		$data['default_image'] = wfGetPad(self::DEFAULT_THUMB_IMG);

		return $data;
	}

	protected function getFormattedData() {
		$data = $this->data;
		$data['issubcat'] = $this->isSubCategory ? "1" : "0";
		$data['has_subsublist'] = $this->isSubCategory && count($data['subcategories']) > 0 ? "1" : "0";
		$data['cat_articles']['articles'] = $this->truncateTitles($data['cat_articles']['articles']);
		$data['cat_articles']['articles'] = $this->removeArticleSortKeys($data['cat_articles']['articles']);
		$data['default_image'] = wfGetPad(self::DEFAULT_THUMB_IMG);
		$data['all_articles_title'] = wfMessage('cat_all_articles')->text();
		if ($this->isSubCategory) {
			$data['subsublist'] = $this->formatSubcategoryList($data['subcategories']);
		}
		$data['show_more'] = wfMessage('cat_show_more')->text();
		$data['show_less'] = wfMessage('cat_show_less')->text();
		$data['postload'] = count($data['cat_articles']['articles']) > 0 ? "0" : "1";

		return $data;
	}

	public function getArticlesOnly($data) {
		$data['cat_articles']['articles'] = $this->truncateTitles($data['cat_articles']['articles']);
		$data['cat_articles']['articles'] = $this->removeArticleSortKeys($data['cat_articles']['articles']);
		$defaultImage = wfGetPad(self::DEFAULT_THUMB_IMG);

		$html = "";
		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);
		foreach ($data['cat_articles']['articles'] as $item) {
			$item['default_image'] = $defaultImage;
			$item['howto_prefix'] = $data['howto_prefix'];
			$html .=  $m->render('category_carousel_item', $item);
		}
		return $html;
	}

	protected function formatSubcategoryList($subcats) {
		$subdata = [];
		foreach ($subcats as $title) {
			$subdata[] = ['url' => $title->getLocalUrl(), 'text' => $title->getText()];
		}
		return $subdata;
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
