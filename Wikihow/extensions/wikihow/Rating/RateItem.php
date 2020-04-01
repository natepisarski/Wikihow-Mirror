<?php

use MethodHelpfulness\ArticleMethod;

/**
 * AJAX call class to actually rate an item.
 * Currently we can rate: articles and samples
 */
class RateItem extends UnlistedSpecialPage {

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'RateItem' );
		$wgHooks['ArticleDelete'][] = array("RateItem::clearRatingsOnDelete");
	}

	public function isMobileCapable() {
		return true;
	}

	/**
	 *
	 * This function can only get called when an article gets deleted
	 *
	 **/
	public static function clearRatingsOnDelete($wikiPage, $user, $reason) {
		$ratingTool = new RatingArticle();
		$ratingTool->clearRatings($wikiPage->getId(), $user, "Deleting page");
		$starRatingTool = new RatingStar();
		$starRatingTool->clearRatings($wikiPage->getId(), $user, "Deleting page");
		return true;
	}

	/*
	 * add a rating to summary section
	 * type is the rating type which for example summaryvideohelp
	 */
	private function addItemRating( $pageId, $type, $rating ) {
		$dbw = wfGetDB( DB_MASTER );
		$table = 'item_rating';
		$date = gmdate( "Y-m-d H:i:s" );
		$insertData = array(
			'ir_page_id' => $pageId,
			'ir_type' => $type,
			'ir_rating' => $rating,
		);
		$options = array( 'IGNORE' );
		$dbw->insert( $table, $insertData, __METHOD__, $options );

		$id = $dbw->insertId();

		// return the inserted id
		return $id;
	}

	private function rateSummaryVideo() {
		$type = 'summaryvideohelp';
		$request = $this->getRequest();

		$rating = $request->getFuzzyBool( 'rating' );
		$pageId = $request->getVal( 'pageId' );

		// TODO if there is a reason then add rating reason
		// but this is not implemented yet
		$reason = $request->getVal( 'reason' );

		$id = $this->addItemRating( $pageId, $type, $rating );

		// return the inserted id
		return $id;
	}

	private function itemRatingReason() {
		$dbw = wfGetDB( DB_MASTER );
		$request = $this->getRequest();
		$ratingId = $request->getInt( 'ratingId' );
		$text = $request->getVal( 'text' );

		// first get the item_rating row
		$table = "item_rating";
		$var = '*';
		$cond = array( 'ir_id' => $ratingId );;
		$itemRating = $dbw->selectRow( $table, $var, $cond, __METHOD__ );
		$user = $this->getUser()->getId();
		$userText = $this->getUser()->getName();
		$title = Title::newFromId( $itemRating->ir_page_id );
		// now store the rating reason
		$table = "rating_reason";
		$insertData = array(
			'ratr_type' => 'itemrating',
			'ratr_item' => $title->getText(),
			'ratr_detail' => $itemRating->ir_type,
			'ratr_page_id' => $itemRating->ir_page_id,
			'ratr_user' => $user,
			'ratr_user_text' => $userText,
			'ratr_text' => $text,
			'ratr_rating' => $itemRating->ir_rating
		);
		$options = array();
		$dbw->insert( $table, $insertData, __METHOD__, $options );
	}

	private function rateSummaryText() {
		$type = 'summarytexthelp';
		$request = $this->getRequest();

		$rating = $request->getFuzzyBool( 'rating' );
		$pageId = $request->getVal( 'pageId' );

		// TODO if there is a reason then add rating reason
		// but this is not implemented yet
		$reason = $request->getVal( 'reason' );

		$id = $this->addItemRating( $pageId, $type, $rating );

		// return the inserted id
		return $id;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$ratType = $req->getVal("type", 'article_mh_style');
		if ( $ratType == 'summaryvideo' ) {
			$id = $this->rateSummaryVideo();
			echo $id;
			exit;
		}
		if ( $ratType == 'summarytexthelp' ) {
			$id = $this->rateSummaryText();
			echo $id;
			exit;
		}
		if ( $ratType == 'itemratingreason' ) {
			$this->itemRatingReason();
			exit;
		}
		$ratId = $req->getVal("page_id");
		$ratUser = $user->getID();
		$ratUserext = $user->getName();
		$ratRating = $req->getVal('rating');
		$source = $req->getVal('source');
		$out->setArticleBodyOnly(true);

		// disable ratings more than 5, less than 1
		if ($ratRating > 5 || $ratRating < 0) return;
		if (!$ratId) return;

		$rateItem = new RateItem();
		$ratingTool = $rateItem->getRatingTool($ratType);

		//AMP HEADERS
		//based on https://www.ampproject.org/docs/guides/amp-cors-requests#cors-security-in-amp
		if ($req->getInt('amp')) {
			$bad_header_characters = '/\n|\r/';
			$origin = '';
			$allowedOrigins = [
				'https://m.wikihow.com',
				'https://m-wikihow-com.cdn.ampproject.org',
				'https://cdn.ampproject.org'
			];
			$allowedSourceOrigin = 'https://m.wikihow.com';
			$sourceOrigin = $req->getVal('__amp_source_origin');
			if (preg_match($bad_header_characters, $sourceOrigin)) $sourceOrigin = '';

			if ($req->getHeader('amp-same-origin')) {
				$origin = $sourceOrigin;
			}
			elseif (
				in_array($req->getHeader('origin'),$allowedOrigins) &&
				$sourceOrigin == $allowedSourceOrigin
			) {
				$origin = $req->getHeader('origin');
			}

			if (preg_match($bad_header_characters, $origin)) $origin = '';

			if (!empty($origin)) {
				header('Access-Control-Allow-Credentials:true');
				header('Access-Control-Allow-Origin:'.$origin);
				header('Access-Control-Expose-Headers:AMP-Access-Control-Allow-Source-Origin');
				header('AMP-Access-Control-Allow-Source-Origin:'.$sourceOrigin);
			}
		}

		print $ratingTool->addRating($ratId, $ratUser, $ratUserext, $ratRating, $source);

		if ($ratType == 'article_mh_style' && $ratRating == "1") {
			RatingRedis::incrementRating();
		}
	}

	public static function showForm($type) {
		$rateItem = new RateItem();
		$ratingTool = $rateItem->getRatingTool($type);

		return $ratingTool->getRatingForm();
	}

	public static function showSidebarForm($type, $class = '') {
		$rateItem = new RateItem();
		$ratingTool = $rateItem->getRatingTool($type);

		return $ratingTool->getSidebarRatingForm($class);
	}

	public function showMobileForm($type) {
		$rateItem = new RateItem();
		$ratingTool = $rateItem->getRatingTool($type);

		$result = $ratingTool->getMobileRatingForm();
		return $result;
	}

	public function getRatingTool($type) {
		switch (strtolower($type)) {
			case 'article': // LEGACY from before 10/27/15
				$rTool = new RatingArticle();
				break;
			case 'sample': // LEGACY from before 10/27/15
				$rTool = new RatingSample();
				break;
			case 'article_mh_style':
				$rTool = new RatingArticleMHStyle();
				break;
			case 'star':
				$rTool = new RatingStar();
				break;
		}

		$rTool->setContext($this->getContext());
		return $rTool;
	}

	/*
	 * get an html snippet which will add a mini helpfulness rating section
	 * to be placed at the end of a section
	 * @param textFeedback bool set to true if you want to prompt for text feedback
	 */
	public static function getSectionRatingHtml( $type, $textFeedback = false,  $buttonClass = null, $text = null, $wrapperClass = null, $finishPromptYes = null, $finishPromptNo = null, $summary_at_top = true ) {
		if ( !$text ) {
			$text = wfMessage( 'rateitem_summary_text' )->text();
		}

		if ( !$buttonClass ) {
			$buttonClass = 's-help-response';
		}

		if ( !$wrapperClass ) {
			$wrapperClass = 's-help-wrap';
		}

		if ( !$finishPromptYes ) {
			if ($summary_at_top)
				$finishPromptYes = wfMessage( 'rateitem_summary_finish_prompt_yes' )->text();
			else
				$finishPromptYes = wfMessage( 'rateitem_summary_finish_prompt_yes_bottom' )->text();

		}
		if ( !$finishPromptNo ) {
			if ($summary_at_top)
				$finishPromptNo = wfMessage( 'rateitem_summary_finish_prompt_no' )->text();
			else
				$finishPromptNo = wfMessage( 'rateitem_summary_finish_prompt_no_bottom' )->text();
		}
		$yesText = wfMessage( 'rateitem_summary_yes' )->text();
		$noText = wfMessage( 'rateitem_summary_no' )->text();

		$promptTextYes = wfMessage( 'rateitem_summary_prompt_text_yes' )->text();
		$promptTextNo = wfMessage( 'rateitem_summary_prompt_text_no' )->text();

		$textareaPromptYes = wfMessage( 'rateitem_summary_textarea_prompt_yes' )->text();
		$textareaPromptNo = wfMessage( 'rateitem_summary_textarea_prompt_no' )->text();

		$submitText = wfMessage( 'rateitem_summary_submit_text' )->text();

		$inner = Html::element( 'span', ['class' => 's-help-prompt' ], $text );
		$inner .= Html::element(
			'button',
			[
				'class' => $buttonClass,
				'data-value' => 1,
				'data-type' => $type,
				'data-prompt-text' => $promptTextYes,
				'data-finish-prompt' => $finishPromptYes,
				'data-textarea-prompt' => $textareaPromptYes,
				'data-text-feedback' => $textFeedback
			],
			$yesText
		);
		$inner .= Html::element(
			'button',
			[
				'class' => $buttonClass,
				'data-value' => 0,
				'data-type' => $type,
				'data-prompt-text' => $promptTextNo,
				'data-finish-prompt' => $finishPromptNo,
				'data-textarea-prompt' => $textareaPromptNo,
				'data-text-feedback' => $textFeedback
			],
			$noText
		);

		$textFeedback = Html::rawElement( 'textarea', array( 'class'=> 's-help-textarea' ) );
		$textFeedback .= Html::rawElement( 'input', array( 'type' => 'button', 'class'=> 's-help-submit button primary', 'value' => $submitText ) );

		$inner .= Html::rawElement( 'div', array( 'class' => 's-help-feedback-wrap'), $textFeedback );

		$wrap = Html::rawElement( 'div', ['class' => $wrapperClass, 'data-type' => $type], $inner );
		return $wrap;
	}

	public static function getSummarySectionRatingHtml($summary_at_top) {
		if ( Misc::isIntl() ) {
			return '';
		}
		$type = 'summarytexthelp';
		$textFeedback = false;
		$buttonClass = null;
		$text = null;
		$wrapperClass = null;
		$finishPromptYes = null;
		$finishPromptNo = null;

		return self::getSectionRatingHtml( $type, $textFeedback,  $buttonClass, $text, $wrapperClass, $finishPromptYes, $finishPromptNo, $summary_at_top );
	}
}
