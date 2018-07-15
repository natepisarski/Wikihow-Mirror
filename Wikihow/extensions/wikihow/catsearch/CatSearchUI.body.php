<?php

class CatSearchUI extends UnlistedSpecialPage {

	function __construct() { 
		parent::__construct( 'CatSearchUI' );
	}
	
	function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();		

		$fname = 'CatSearchUI::execute';
		wfProfileIn( $fname );

		$out->setRobotpolicy( 'noindex,nofollow' );
		
		$vars = array();
		$this->getUserCategoriesHtml($vars);
		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$html = EasyTemplate::html('CatSearchUI', $vars);

		$embedded = intval($request->getVal('embed'));
		$out->setArticleBodyOnly($embedded);
		$out->addHtml($html);

		wfProfileOut( $fname );
	}

	function getUserCategoriesHtml(&$vars) {
		$cats = CategoryInterests::getCategoryInterests();
		$html = "";
		if(sizeof($cats)) {
			$vars['cats'] = self::getCategoryDivs($cats);
			$vars['nocats_hidden'] = 'csui_hidden';
		}
		else {
			$vars['cats_hidden'] = 'csui_hidden';
		}

		$suggested = self::getSuggestedCategoryDivs(CategoryInterests::suggestCategoryInterests());
		$vars['suggested_cats'] = $suggested;
	}


	function getSuggestedCategoryDivs(&$cats) {
		$html = "";
		foreach ($cats as $key => $cat) {
			$catName = trim(str_replace("-", " ", $cat));
			$cats[$key] = "<div class='csui_suggestion'><div class='csui_hidden'>$cat</div>$catName</div>";
		}
		$html = implode(", ", $cats);

		return $html;
	}

	function getCategoryDivs(&$cats) {
		$html = "";
		foreach ($cats as $cat) {
			$catName = str_replace("-", " ", $cat);
			$html .= "<div class='csui_category ui-corner-all'><span class='csui_close'>x</span>$catName<div class='csui_hidden'>$cat</div></div>\n";
		}
		return $html;
	}
}
