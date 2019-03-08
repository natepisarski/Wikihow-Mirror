<?php

class Slider extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'Slider' );
	}

	public function getBox() {
		return self::getBox_08();
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

	//circle slider with Become an expert link
	public function getBox_10() {

		$theBox = "<div id='sliderbox' class='sliderbox_10' style='display:none'>
						<div id='slider_thanks_10'>
							<a href='#' id='slider_close_button'></a>
							<div class='slider_become_main'>
								<p class='slider_become_text'>".wfMessage('slider-text-become-2')->text()."</p>
								<p class='slider_button'><a class='button primary' target='_blank' href='".wfMessage('slider-text-become-link-2')->text()."'>".wfMessage('slider-button-text')."</a></p>
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
