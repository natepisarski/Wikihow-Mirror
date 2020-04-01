<?php

class RatingArticleMHStyle extends RatingArticle {
	public function __construct() {
		parent::__construct();
	}

	function getRatingResponsePlatform($itemId, $rating, $ratingId, $source) {
		if (GoogleAmp::isAmpMode(RequestContext::getMain()->getOutput())) {
			return json_encode(['result' => 'true']);
		}

		$tmpl = new EasyTemplate(__DIR__);
		$title = Title::newFromID($itemId);
		$tmpl->set_vars(
			array(
				'rating' => $rating,
				'titleText' => $title->getText(),
				'ratingId' => $ratingId,
				'isMobile' => $source == 'mobile'
		));

		$tech_article = SpecialTechFeedback::isTitleInTechCategory( $title );

		if ($tech_article) {
			$template = 'rateitem_response_tech.tmpl.php';
		}
		elseif ($source == 'discuss_tab') {
			$tmpl->set_vars['tech_article'] = $tech_article;
			$template = 'rating_mh_modal.tmpl.php';
		}
		else {
			$template = 'rating_mh_style.tmpl.php';
		}

		return $tmpl->execute( $template );
	}

	function getRatingResponseMobile($itemId, $rating, $ratingId) {
		$source = 'mobile';
		return $this->getRatingResponsePlatform($itemId, $rating, $ratingId, $source);
	}

	function getRatingResponseDesktop($itemId, $rating, $ratingId) {
		$source = 'desktop';
		return $this->getRatingResponsePlatform($itemId, $rating, $ratingId, $source);
	}

	function getRatingResponseModal($itemId, $rating, $ratingId) {
		$source = 'discuss_tab';
		return $this->getRatingResponsePlatform($itemId, $rating, $ratingId, $source);
	}
}

