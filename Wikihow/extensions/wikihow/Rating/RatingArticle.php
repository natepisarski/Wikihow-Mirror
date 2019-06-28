<?php

/*******************
 *
 * Contains all the specific information relating
 * to ratings of articles. Article ratings happen on
 * both desktop and mobile.
 *
 ******************/

class RatingArticle extends RatingsTool {

	public function __construct() {
        parent::__construct();

		$this->ratingType = 'article';
		$this->tableName = "rating";
		$this->tablePrefix = "rat_";
		$this->logType = "accuracy";
		$this->lowTable = "rating_low";
		$this->lowTablePrefix = "rl_";
	}

	function logClear($itemId, $max, $min, $count, $reason){
		$title = Title::newFromID($itemId);

		if ($title) {
			$params = array($itemId, $min, $max);
			$log = new LogPage( $this->logType, true );
			$logMsg = wfMessage('clearratings_logsummary', $reason, $title->getFullText(), $count)->text();
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
		} elseif ($source == 'discuss_tab') {
			return static::getRatingResponseModal($itemId, $rating, $ratingId);
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

	// deprecated
	function getRatingFormForType($type="desktop", $class = '') {
		global $wgTitle, $wgRequest;
		if ($this->mContext->canUseWikiPage() == false ) return;
		$page_id = $this->mContext->getWikiPage()->getId();
		if ($page_id <= 0) return;
		$action = $wgRequest->getVal('action');
		if ($action != null &&  $action != 'view') return;
		if ($wgRequest->getVal('diff', null) != null) return;

		/* use this only for (Main) namespace pages that are not the main page - feel free to remove this... */
		$mainPageObj = Title::newMainPage();
		if (!$wgTitle->inNamespace(NS_MAIN)
			|| $mainPageObj->getFullText() == $wgTitle->getFullText())
		{
			return;
		}
		if ($type == "desktop") {
			$msg = self::getMWMessageDesktop($wgTitle->getLocalUrl());
			$s = self::getDesktopRatingHTML($page_id, $msg);
		} elseif ($type == "sidebar") {
			$s = self::getSidebarRatingHTML($page_id, $class);
		} else {
			$msg = self::getMWMessageMobile($wgTitle->getLocalUrl());
			$s = self::getMobileRatingHTML($page_id, $msg);
		}

		return $s;
	}

	// deprecated
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

	// deprecated
	function getSidebarRatingHTML($page_id, $class) {
		$context = RequestContext::getMain();
		$context->getOutput()->addModules(['ext.wikihow.rating_sidebar.styles','ext.wikihow.rating_sidebar']);

		$tmpl = new EasyTemplate(__DIR__);
		$tmpl->set_vars([
			'class' => $class,
			'is_intl' => ($context->getLanguage()->getCode() != 'en')
		]);
		return $tmpl->execute('rating_sidebar.tmpl.php');
	}

	// deprecated
	function getMobileRatingHTML($page_id, $msg) {
		$msgText = wfMessage($msg)->text();
		$yesText = wfMessage('rateitem_yes_button')->text();
		$noText = wfMessage('rateitem_no_button')->text();

		$html = <<<EOHTML
			<div id="article_rating_mobile" class="section_text">
				<h2>
					<span class="mw-headline">$msgText</span>
				</h2>
				<div id="article_rating" class="trvote_box ar_box">
					<div id="gatAccuracyYes" pageid="$page_id" class="ar_box_vote vote_up aritem">
						<div class="ar_face"></div>
						<div class="ar_thumb_text" role="button" tabindex="0">$yesText</div>
					</div>
					<div id="gatAccuracyNo" pageid="$page_id" class="ar_box_vote vote_down aritem">
						<div class="ar_face"></div>
						<div class="ar_thumb_text" role="button" tabindex="0">$noText</div>
					</div>
					<div class="clearall"></div>
				</div>
			</div>
		</div>
EOHTML;
	return $html;
	}


	public static function getDesktopSideForm( $pageId, $class ) {
		$context = RequestContext::getMain();
		$context->getOutput()->addModules(['ext.wikihow.rating_sidebar.styles','ext.wikihow.rating_sidebar']);
		$headerText = wfMessage('ratearticle_side_hdr')->text();
		$noMessage = wfMessage('ras_res_no_top')->text();
		$showNoForm = false;
		if ( SpecialTechFeedback::isTitleInTechCategory( $context->getTitle() ) ) {
			$headerText = wfMessage( 'rateitem_question_tech' )->text();
			$noMessage = wfMessage('sidebar_no_message_tech')->text();
			$showNoForm = true;
		}

		$tmpl = new EasyTemplate(__DIR__);
		$tmpl->set_vars([
			'headerText' => $headerText,
			'class' => $class,
			'is_intl' => ($context->getLanguage()->getCode() != 'en'),
			'noMessage' => $noMessage,
			'showNoForm' => $showNoForm,
		]);
		return $tmpl->execute('rating_sidebar.tmpl.php');
	}

	public static function getDesktopBodyForm( $pageId ) {
		$context = RequestContext::getMain();
		$context->getOutput()->addModules('ext.wikihow.rating_desktop.style');

		if ( SpecialTechFeedback::isTitleInTechCategory( $context->getTitle() ) )
			$msgText = wfMessage( 'rateitem_question_tech' )->text();
		else
			$msgText = wfMessage( 'rateitem_question_desktop' )->text();

		$show_koala = mt_rand(1, 20) == 1 || $context->getRequest()->getInt('show_koala',0) == 1;

		$vars = [
			'msgText' => $msgText,
			'yesText' => wfMessage('rateitem_yes_button')->text(),
			'noText' => wfMessage('rateitem_no_button')->text(),
			'pageId' => $pageId,
			'show_koala' => $show_koala
		];

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$html = $m->render('rating_desktop_body', $vars);
		return $html;
	}

	public static function getMobileForm( $pageId, $isAmp = false ) {
		$context = RequestContext::getMain();
		$msgText = wfMessage( 'rateitem_question_mobile' )->text();
		$ampResponseYesText = '';
		$ampResponseNoText = '';

		if ( SpecialTechFeedback::isTitleInTechCategory( $context->getTitle() ) ) {
			$msgText = wfMessage( 'rateitem_question_tech' )->text();
		}

		if ($isAmp) {
			$title = Title::newFromId($pageId);
			$search_term = $title ? str_replace(' ', '+', $title->getText()) : '';
			$search_term = htmlspecialchars($search_term);

			$ampResponseYesText = wfMessage('ratearticle_reason_submitted_yes', $search_term)->text();
			$ampResponseNoText = wfMessage('ratearticle_reason_submitted', $search_term)->text();
		}

		$tmpl = new EasyTemplate(__DIR__);
		$tmpl->set_vars(
			array(
				'msgText' => $msgText,
				'yesText' => wfMessage('rateitem_yes_button')->text(),
				'noText' => wfMessage('rateitem_no_button')->text(),
				'pageId' => $pageId,
				'amp' => $isAmp,
				'amp_form_yes_response' => $ampResponseYesText,
				'amp_form_no_response' => $ampResponseNoText
		));
		return $tmpl->execute('rating_form_mobile.tmpl.php');
	}

	public static function getDesktopModalForm($aid) {
		$title = Title::newFromId($aid);
		$msgText = wfMessage( 'rateitem_question_desktop' )->text();

		if ( SpecialTechFeedback::isTitleInTechCategory( $title ) ) {
			$msgText = wfMessage( 'rateitem_question_tech' )->text();
		}

		$vars = [
			'msgText' => $msgText,
			'yesText' => wfMessage('rateitem_yes_button')->text(),
			'noText' => wfMessage('rateitem_no_button')->text(),
			'pageId' => $title->getArticleID()
		];

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);
		$html = $m->render('rating_desktop_modal', $vars);
		return $html;
	}

	// deprecated
	function getRatingForm() {
		return self::getRatingFormForType("desktop");
	}
	// deprecated
	function getSidebarRatingForm($class) {
		return self::getRatingFormForType("sidebar", $class);
	}
	// deprecated
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
		    return 'ratearticle_reason_submitted' . ( $rating ? '_yes' : '' );
	}

	public function getRatingReasonResponse($rating, $itemId = null) {
		$search_term = '';
		$context = RequestContext::getMain();

		if ($itemId) {
			$title = Title::newFromText($itemId);

			if (!empty($title)) {
				$context->setTitle($title);
				$search_term = str_replace(' ', '+', $title->getText());
				$search_term = htmlspecialchars($search_term);
			}
		}

		$msg = $this->getRatingResponseMessage(intval($rating), Misc::isMobileMode());

		return $context->msg($msg, $search_term)->text();
	}

	public function getRatingCountForPeriod($articleId, $startDate) {
		$dbr = wfGetDB(DB_REPLICA);

		$count = $dbr->selectField(
			$this->tableName,
			'count(*)',
			[
				'rat_page' => $articleId,
				"rat_timestamp > '{$startDate}'",
				'rat_isdeleted' => 0,
				'rat_rating' => 1
			],
			__METHOD__
		);

		return $count;
	}
}

/***
 *
	CREATE TABLE `rating` (
	`rat_id` int(8) unsigned NOT NULL auto_increment,
	`rat_page` int(8) unsigned NOT NULL default '0',
	`rat_user` int(5) unsigned NOT NULL default '0',
	`rat_user_text` varchar(255) NOT NULL default '',
	`rat_month` varchar(7) NOT NULL default '',
	`rat_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
	`rat_rating` tinyint(1) unsigned NOT NULL default '0',
	`rat_isdeleted` tinyint(3) unsigned NOT NULL default '0',
	`rat_user_deleted` int(10) unsigned default NULL,
	`rat_deleted_when` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`rat_page`,`rat_id`),
	UNIQUE KEY `rat_id` (`rat_id`),
	UNIQUE KEY `user_month_id` (`rat_page`,`rat_user_text`,`rat_month`),
	KEY `rat_timestamp` (`rat_timestamp`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1


	CREATE TABLE `rating_low` (
	`rl_page` int(8) unsigned NOT NULL default '0',
	`rl_avg` double NOT NULL default '0',
	`rl_count` tinyint(4) NOT NULL default '0'
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;

 *
 ***/

