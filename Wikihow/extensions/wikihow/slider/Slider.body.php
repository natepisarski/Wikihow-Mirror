<?php

class Slider extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'Slider' );
	}
	
	public function getBox() {
		//global $wgTitle;
	
		// First try to see if we have a recommendation
		//$html = self::getRecBox();
		//if($html) {
		//	return($html);	
		//}
		// Contribute to wikiHow slider
		return self::getBox_08();
	}
	
	//original slider
	public function getBox_01() {
		global $wgLanguageCode;

        // Remove background for non-english sites. Unfortunate, but bg image has English in it.
        $slider_thanks_intl = "";
        if ($wgLanguageCode != 'en') {
            $slider_thanks_intl = "class='slider_thanks_intl'";
        }

		$theBox = "<div id='sliderbox' style='display:none'>
						<div id='slider_thanks' $slider_thanks_intl>
							<a href='#' id='slider_close_button'>x</a>
							<div class='tta_plus1'><g:plusone size='tall'></g:plusone></div>
							<div class='tta_text'>
								<p class='tta_first'>".wfMessage('slider-text')->text()."</p>
								<p class='slider_subtext_plus1'>".wfMessage('slider-sub-text-plusone')->text()."</p>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//wikihow logo slider
	public function getBox_02() {
		
		$theBox = "<div id='sliderbox' style='display:none'>
						<div id='slider_thanks_02'>
							<a href='#' id='slider_close_button'>x</a>
							<div class='tta_plus1_02'><g:plusone size='tall'></g:plusone></div>
							<div class='tta_text_02'>
								<p class='tta_first'>".wfMessage('slider-text')->text()."</p>
								<p class='slider_subtext_plus1'>".wfMessage('slider-sub-text-plusone')->text()."</p>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//circle slider
	public function getBox_03() {
		
		$theBox = "<div id='sliderbox' class='sliderbox_03' style='display:none'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_03'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_03'>
									<p class='tta_first_03'>".wfMessage('slider-text')->text()."</p>
									<p class='slider_subtext_plus1'>".wfMessage('slider-sub-text-plusone')->text()."</p>
								</div>
								<div class='tta_plus1_03'><g:plusone size='tall'></g:plusone></div>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	/* Note: this box CANNOT be used without adding caching in WikihowShare::getPinterestImage($wgTitle)
	//circle slider [pinterest]
	public function getBox_04() {
		global $wgCanonicalServer, $wgTitle;

		$url = urlencode($wgCanonicalServer . "/" . $wgTitle->getPrefixedURL());
		$img = urlencode(WikihowShare::getPinterestImage($wgTitle));
		$desc = urlencode(wfMessage('Pinterest_text', $wgTitle->getText())->text());
		
		$theBox = "<div id='sliderbox' class='sliderbox_03' style='display:none'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_03'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_03'>
									<p class='tta_first_03'>".wfMessage('slider-text')->text()."</p>
									<p class='slider_subtext_pinit'>".wfMessage('slider-sub-text-pinit')->text()."</p>
								</div>
								<a href='http://pinterest.com/pin/create/button/?url=" . $url . "&media=" . $img . "&description=" . $desc . "' class='pin-it-button' count-layout='vertical'>Pin It</a>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	*/
	
/*
	//circle slider
	public function getBox_05() {
		
		//$ar = new ArticleRatingDesktopView();
		$body =  $ar->getHtml();
		$theBox = "<div id='sliderbox' class='sliderbox_rating' style='display:none'>
							<div id='slider_thanks_02'>
									<a href='#' id='slider_close_button'>x</a>
									<div class='tta_text_05'>
										<p class='tta_first_03'>".wfMessage('slider-ra-text')->text()."</p>
										<p class='slider_subtext_plus1'>".wfMessage('slider-ra-subtext')->text()."</p>
									</div>
									$body
							</div>
					</div>";

		return $theBox;
	}
*/
	
	//circle slider that follows our G+ page
	public function getBox_06() {
		
		$theBox = "<div id='sliderbox' class='sliderbox_06' style='display:none'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_06'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_06'>
									<p class='tta_first_03'>".wfMessage('slider-text')->text()."</p>
									<p class='slider_subtext_plus1'>".wfMessage('slider-sub-text-plusone-site')->text()."</p>
								</div>
								<div class='tta_plus1_03'><div class='g-plus' data-width='100' data-href='https://plus.google.com/102818024478962731382' data-rel='publisher'></div></div>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//circle slider with official G+ Follow Button
	public function getBox_07() {
		
		$theBox = "<div id='sliderbox' class='sliderbox_06' style='display:none'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_06'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_06'>
									<p class='tta_first_03'>".wfMessage('slider-text')->text()."</p>
									<p class='slider_subtext_plus1'>".wfMessage('slider-sub-text-plusone-site')->text()."</p>
								</div>
								<div class='tta_plus1_03'><div class='g-follow' data-annotation='vertical-bubble' data-height='20' data-href='https://plus.google.com/102818024478962731382' data-rel='publisher'></div></div>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//circle slider with Become an Author link
	public function getBox_08() {
		
		$theBox = "<div id='sliderbox' class='sliderbox_08' style='display:none'>
						<div id='slider_thanks_08'>
							<a href='#' id='slider_close_button'></a>
							<div class='slider_become_main'>
								<p class='slider_become_text'>".wfMessage('slider-text-become')->text()."</p>
								<!--p>".wfMessage('slider-sub-text-become')->text()."</p-->
								<p class='slider_button'><a class='button primary' href='".wfMessage('slider-text-become-link')->text()."'>".wfMessage('slider-button-text')."</a></p>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//circle slider with Try Out Editing link 
	public function getBox_09() {
		
		$theBox = "<div id='sliderbox' class='sliderbox_08' style='display:none'>
						<div id='slider_thanks_08'>
							<a href='#' id='slider_close_button'></a>
							<div class='slider_become_main'>
								<p class='slider_editing_text'>".wfMessage('slider-text-editing')->text()."</p>
								<!--p>".wfMessage('slider-sub-text-editing')->text()."</p-->
								<p class='slider_button'><a class='button primary' id='slider_edit_button' href='".wfMessage('slider-text-editing-link')->text()."'>".wfMessage('slider-editing-button-text')."</a></p>
							</div>
						</div>
					</div>";

		return $theBox;
	}

	/**
	 * EXECUTE
	 **/
	function execute ($par = '') {	
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		
		//log it to the database
		if ($wgRequest->getVal('action')) {
			$wgOut->addHTML($res);
			return;
		}
	}
	
}
