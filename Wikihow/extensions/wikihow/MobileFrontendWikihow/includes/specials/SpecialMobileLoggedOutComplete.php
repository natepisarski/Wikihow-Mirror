<?php

class SpecialMobileLoggedOutComplete extends MobileSpecialPage {

	protected $disableSearchAndFooter = true;

	public function __construct() {
		parent::__construct( 'MobileLoggedOutComplete' );
	}

	public function executeWhenAvailable( $par ) {
		$out = $this->getOutput();
		$this->addModules();
		$out->addModuleStyles( 'mobile.special.styles' );
		$out->setArticleBodyOnly(true);

		$out->addHtml("<h3>" . wfMessage('loggedout-main-heading') . "</h3>");
		$out->addHtml("<div class='section_text'>");
		$out->addWikiMsg( 'loggedout-main-text', '/Main-Page' );
		$out->returnToMain();
		$out->addHtml("</div>");

		$out->addHtml("<h3>" . wfMessage('featured_articles')->text() . "</h3>");
		$fas = FeaturedArticles::getTitles(6);
		// @fixme: set ID to #relatedwikihows instead of #fa_container to force the same
		// styling as the related section.
		$out->addHTML("<div class='section_text'><div id='relatedwikihows'>");
		$out->addHTML('<div class="related_boxes">');
		$isEvenItem = true;
		foreach ($fas as $fa) {
			$box = WikihowMobileTools::makeFeaturedArticlesBox($fa['title']);
            // for now do not allow videos in this section
            $box->noVideo = true;
			$boxInnerHtml = WikihowMobileTools::getImageContainerBoxHtml( $box );
			$out->addHTML($boxInnerHtml);
		}
		$out->addHTML("</div><div class='clearall'></div></div></div>");

	}

	// WIKIHOW added this function to allow login/logout on mobile
	public function isMobileCapable() {
		return true;
	}
}
