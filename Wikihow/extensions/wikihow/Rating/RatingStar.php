<?php

/*******************
 *
 * Contains all the specific information relating
 * to ratings of articles. Article ratings happen on
 * desktop.
 *
 ******************/

class RatingStar extends RatingsTool {

	public function __construct() {
        parent::__construct();

		$this->ratingType = 'star';
		$this->tableName = "rating_star";
		$this->tablePrefix = "rs_";
		$this->logType = "accuracy";
		$this->lowTable = "rating_star_low";
		$this->lowTablePrefix = "rsl_";
	}
/*
 * The functions below are copied from RatingsArticle.php
 * and are declared as abstract functions in
 * RatingsTool.php, necessary for PageHelpfulness sidebar
 * and clear ratings functionality. We need to update
 * versions for RatingStar when we implement the sidebar.
 *
 * @Wilson
 * @03-14-16
*/
	function logClear($itemId, $max, $min, $count, $reason){
		$title = Title::newFromID($itemId);

		if ($title) {
			$params = array($itemId, $min, $max);
			$log = new LogPage( $this->logType, true );
			$logMsg = wfMessage('clearratings_logsummary_star', $reason, $title->getFullText(), $count)->text();
			$log->addEntry( 'accuracy', $title, $logMsg, $params );
		}
	}

	function getLoggingInfo($title) {
		global $wgLang, $wgOut;

		$dbr = wfGetDB( DB_REPLICA );

		// get log
		$res = $dbr->select ('logging',
			array('log_timestamp', 'log_user', 'log_comment', 'log_params'),
			array ('log_type' => $this->logType, "log_title"=>$title->getDBKey() ),
			__METHOD__);

		$results = array();
		foreach ($res as $row) {
			$item = array();
			$item['date'] = $wgLang->date($row->log_timestamp);
			$u = User::newFromId($row->log_user);
			$item['userId'] = $row->log_user;
			$item['userName'] = $u->getName();
			$item['userPage'] = $u->getUserPage();
			$item['params'] = explode("\n", $row->log_params);
			$item['comment'] = preg_replace('/<?p>/', '', $wgOut->parse($row->log_comment) );
			$item['show'] = (strpos($row->log_comment, wfMessage('clearratings_restore')->text()) === false);

			$results[] = $item;
		}

		return $results;
	}

	function logRestore($itemId, $low, $hi, $reason, $count) {
		$title = Title::newFromId($itemId);
		$params = array($itemId, $low, $hi);
		$log = new LogPage( 'accuracy', true );
		$log->addEntry( $this->logType, $title, wfMessage('clearratings_logrestore', $reason, $title->getFullText(), $count)->text(), $params );
	}

	function makeTitle($itemId) {
		if (is_numeric($itemId)) {
			return Title::newFromID($itemId);
		} else {
			return Title::newFromText($itemId);
		}
	}

	function makeTitleFromId($itemId) {
		return Title::newFromID($itemId);
	}

	function getId($title) {
		return $title->getArticleID();
	}

	function getRatingResponse($itemId, $rating, $source, $ratingId) {
		if ($source == "mobile") {
			return static::getRatingResponseMobile($itemId, $rating, $ratingId);
		} else {
			return static::getRatingResponseDesktop($itemId, $rating, $ratingId);
		}
	}

	function getRatingResponseMobile($itemId, $rating, $ratingId) {
		$newContext = new DerivativeContext( $this->getContext() );
		$title = Title::newFromID($itemId);
		$newContext->setTitle($title);
		if ($rating > 0) {
			return $newContext->msg('ratearticle_rated_mobile')->text();
		}

		$textMsg = $newContext->msg('ratearticle_notrated_mobile')->text();

		$responses = wfMessage("ratearticle_notrated_responses")->text();
		$textArea = wfMessage("ratearticle_notrated_textarea")->text();
		$responses = explode("\n", $responses);
		$listHtml = "";
		$first = true;
		foreach($responses as $response) {
			$msg = wfMessage($response)->text();
			$listHtml .= "<label for='$response' class='article_rating_detail'>
				<input type='radio' name='ar_radio' id='$response' value='$response'></input>$msg</label>";
		}

		$titleText = htmlspecialchars($title->getText(), ENT_QUOTES);

		$submitMsg = wfMessage('Submit')->text();

		$html = <<<EOHTML
			<span>$textMsg</span>
			<div id="article_rating_input" class="clearfix">
				<div id="article_rating_more">$listHtml</div>
				<textarea placeholder='$textArea' id='article_rating_feedback' name=submit maxlength='254'></textarea>
				<div class="clearall"></div>
				<input type='button' class='rating_submit button primary' value='$submitMsg'
					onClick='WH.ratings.ratingReason($("#article_rating_feedback").val(), "$titleText", "article", 0, "MOBILE", null, $("#article_rating_more input[name=ar_radio]:checked").val(), $ratingId);'>
			</div>
			<div class="clearall"></div>
EOHTML;
		return $html;
	}

	function getRatingResponseDesktop($itemId, $rating, $ratingId) {
		$tmpl = new EasyTemplate(__DIR__);
		$title = Title::newFromID($itemId);
		$titleText = htmlspecialchars($title->getText(), ENT_QUOTES);
		$tmpl->set_vars(
			array(
				'rating' => $rating,
				'titleText' => $titleText,
				'ratingId' => $ratingId
		));
		return $tmpl->execute('rating.tmpl.php');
	}

	function getRatingFormForType($type="desktop") {
		global $wgTitle, $wgRequest;
		if ($this->mContext->canUseWikiPage() == false ) return;
		$page_id = $this->mContext->getWikiPage()->getId();
		if ($page_id <= 0) return;
		$action = $wgRequest->getVal('action');
		if ($action != null &&  $action != 'view') return;
		if ($wgRequest->getVal('diff', null) != null) return;

		// use this only for (Main) namespace pages that are not the main page - feel free to remove this...
		$mainPageObj = Title::newMainPage();
		if (!$wgTitle->inNamespace(NS_MAIN)
			|| $mainPageObj->getFullText() == $wgTitle->getFullText())
		{
			return;
		}
		if ($type == "desktop") {
			$msg = self::getMWMessageDesktop($wgTitle->getLocalUrl());
			$s = self::getDesktopRatingHTML($page_id, $msg);
		} else {
			$msg = self::getMWMessageMobile($wgTitle->getLocalUrl());
			$s = self::getMobileRatingHTML($page_id, $msg);
		}

		return $s;
	}

	function getDesktopRatingHTML($page_id, $msg) {
		$msgText = wfMessage($msg)->text();
		$yesText = wfMessage('rateitem_yes_button')->text();
		$noText = wfMessage('rateitem_no_button')->text();

		$html = <<<EOHTML
			<div id="article_rating">
				<span class="mw-headline">$msgText</span>
				<div id="ar_buttons">
					<button id="gatAccuracyYes" pageid="$page_id" class="button secondary aritem" role="button" tabindex="0">$yesText</button>
					<button id="gatAccuracyNo" pageid="$page_id" class="button secondary aritem" role="button" tabindex="0">$noText</button>
				</div>
			</div>
EOHTML;
		return $html;
	}

	function getMobileRatingHTML($page_id, $msg) {
		$msgText = wfMessage($msg)->text();
		$yesText = wfMessage('rateitem_yes_button')->text();
		$noText = wfMessage('rateitem_no_button')->text();

		$html = <<<EOHTML
			<div id="article_rating_mobile" class="section">
				<h2>
					<span class="mw-headline">$msgText</span>
				</h2>
				<div id="article_rating" class="section_text trvote_box ar_box">
					<div id="gatAccuracyYes" pageid="$page_id" class="ar_box_vote vote_up aritem">
						<div class="thumb ar_thumb"></div>
						<div class="ar_thumb_text" role="button" tabindex="0">$yesText</div>
					</div>
					<div class="ar_box_line"> </div>
					<div id="gatAccuracyNo" pageid="$page_id" class="ar_box_vote vote_down aritem">
						<div class="thumb ar_thumb"></div>
						<div class="ar_thumb_text" role="button" tabindex="0">$noText</div>
					</div>
					<div class="clearall"></div>
				</div>
			</div>
		</div>
EOHTML;
	return $html;
	}

	function getRatingForm() {
		return self::getRatingFormForType("desktop");
	}
	function getMobileRatingForm() {
		return self::getRatingFormForType("mobile");
	}

	function getQueryPage() {
		return new ListArticleAccuracyPatrol();
	}

	function getMWMessageDesktop($localUrl) {
			$msg = 'rateitem_question_desktop';
			return $msg;
	}
	function getMWMessageMobile($localUrl) {
			$msg = 'rateitem_question_mobile';
			return $msg;
	}

	function getRatingResponseMessage($rating, $isMobile) {
		    return 'ratearticle_reason_submitted' . ( $isMobile ? '_mobile' : '' ) . ( $rating ? '_yes' : '' );
	}

	public function getRatingReasonResponse($rating, $itemId = null) {

		$context = RequestContext::getMain();

		if ($itemId) {
			$context->setTitle( Title::newFromText($itemId) );
		}

		$msg = $this->getRatingResponseMessage(intval($rating), Misc::isMobileMode());

		return $context->msg($msg)->parse();
	}
}


/***
 *
CREATE TABLE `rating_star` (
  `rs_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `rs_page` int(8) unsigned NOT NULL DEFAULT '0',
  `rs_user` int(5) unsigned NOT NULL DEFAULT '0',
  `rs_user_text` varchar(255) NOT NULL DEFAULT '',
  `rs_month` varchar(7) NOT NULL DEFAULT '',
  `rs_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rs_rating` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `rs_isdeleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rs_user_deleted` int(10) unsigned DEFAULT NULL,
  `rs_deleted_when` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rs_source` varchar(7) DEFAULT NULL,
  `rs_detail` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`rs_page`,`rs_id`),
  UNIQUE KEY `rs_id` (`rs_id`),
  UNIQUE KEY `user_month_id` (`rs_page`,`rs_user_text`,`rs_month`),
  KEY `rs_timestamp` (`rs_timestamp`),
  KEY `rs_page` (`rs_page`,`rs_detail`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

	CREATE TABLE `rating_star_low` (
	`rsl_page` int(8) unsigned NOT NULL default '0',
	`rsl_avg` double NOT NULL default '0',
	`rsl_count` tinyint(4) NOT NULL default '0'
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;

 *
 ***/

