<?php

class Categorylisting extends SpecialPage {

    function __construct($source = null) {
        parent::__construct( 'Categorylisting' );
    }

	function execute($par) {
		$out = $this->getOutput();

		$this->setHeaders();
		$out->setRobotpolicy('index,follow');
		$out->setSquidMaxage(6 * 60 * 60);


		$catData = CategoryData::getCategoryListingData();
		if (Misc::isMobileMode()) {
			$this->renderMobile($catData);
		} else {
			$this->renderDesktop($catData);
		}
	}

	function renderMobile($catData) {
		$out = $this->getOutput();
		$out->addModules('mobile.wikihow.mobile_category_page');
		$out->setPageTitle(wfMessage('categories')->text());
		$out->addHTML(CategoryCarousel::getCategoryListingHtml($catData));
	}

	function renderDesktop($catData) {
		$out = $this->getOutput();

		if (!Misc::isMobileMode()) {
			$out->addHTML(wfMessage('categorylisting_subheader')->text());
		}
		$out->addHTML("<br /><br />");

		$out->addHTML("<div class='section_text'>");
		foreach ($catData as $row) {
			$out->addHTML("<div class='thumbnail'><a href='{$row['url']}'><img src='{$row['img_url']}'/><div class='text'><p><span>{$row['cat_title']}</span></p></div></a></div>");
		}
		$out->addHTML("<div class='clearall'></div>");
		$out->addHTML("</div><!-- end section_text -->");
	}




	public function isMobileCapable() {
		return true;
	}
}
