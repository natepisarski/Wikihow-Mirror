<?php

class CatSearchUI extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'CatSearchUI' );
	}

	public function execute($par) {

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$out->setRobotPolicy( 'noindex,nofollow' );

		$vars = array();
		$this->getUserCategoriesHtml($vars);
		EasyTemplate::set_path( __DIR__.'/' );
		$html = EasyTemplate::html('CatSearchUI.tmpl.php', $vars);

		$embedded = intval($request->getVal('embed'));
		$out->setArticleBodyOnly($embedded);
		$out->addHtml($html);
	}

	private function getUserCategoriesHtml(&$vars) {
		$cats = CategoryInterests::getCategoryInterests();
		$html = "";
		if (sizeof($cats)) {
			$vars['cats'] = $this->getCategoryDivs($cats);
			$vars['nocats_hidden'] = 'csui_hidden';
		} else {
			$vars['cats_hidden'] = 'csui_hidden';
		}

		$suggested = $this->getSuggestedCategoryDivs(CategoryInterests::suggestCategoryInterests());
		$vars['suggested_cats'] = $suggested;
	}


	private function getSuggestedCategoryDivs(&$cats) {
		$html = "";
		foreach ($cats as $key => $cat) {
			$catName = trim(str_replace("-", " ", $cat));
			$cats[$key] = "<div class='csui_suggestion'><div class='csui_hidden'>$cat</div>$catName</div>";
		}
		$html = implode(", ", $cats);

		return $html;
	}

	private function getCategoryDivs($cats) {
		$html = "";
		foreach ($cats as $cat) {
			$catName = str_replace("-", " ", $cat);
			$html .= "<div class='csui_category ui-corner-all'><span class='csui_close'>x</span>$catName<div class='csui_hidden'>$cat</div></div>\n";
		}
		return $html;
	}
}
