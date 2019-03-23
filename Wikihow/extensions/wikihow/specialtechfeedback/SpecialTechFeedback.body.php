<?php

/*
CREATE TABLE `special_tech_feedback_item` (
	`stfi_page_id` int(10) NOT NULL DEFAULT 0,
	`stfi_rating_reason_id` int(10) NOT NULL DEFAULT 0,
	`stfi_user_id` varbinary(20) NOT NULL DEFAULT '',
	`stfi_vote` tinyint(3) NOT NULL DEFAULT 0,
	`stfi_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY (`stfi_page_id`,`stfi_rating_reason_id`, `stfi_user_id`),
	KEY (`stfi_page_id`, `stfi_rating_reason_id`),
	KEY (`stfi_user_id`)
);
*/

class SpecialTechFeedback extends UnlistedSpecialPage {

	const MAX_VOTES = 2;
	const STF_TABLE = 'special_tech_feedback_item';
	var $mLogActions = array();
	var $mUserRemainingCount;

	public function __construct() {
		parent::__construct( 'TechFeedback' );
		$this->out = $this->getContext()->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();
	}

	public function execute( $subPage ) {
		$this->out->setRobotPolicy( "noindex,follow" );

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		if ( $this->getLanguage()->getCode() != 'en' ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $this->request->getVal( 'getQs' ) ) {
			$this->out->setArticleBodyOnly( true );

			//grab the next one
			$data = $this->getNextItemData();
			//print json_encode( array( 'html' => $html ) );
			print json_encode( $data );

			return;
		} elseif ( $this->request->wasPosted() && XSSFilter::isValidRequest() ) {
			$this->out->setArticleBodyOnly( true );
			$this->saveVote();
			$this->updateVoted();
			$data = array( 'logactions' => $this->mLogActions );
			print json_encode( $data );
			return;
		}

		$this->out->setPageTitle( wfMessage( 'specialtechfeedback' )->text() );
		$this->addStandingGroups();
		$this->out->addModuleStyles( 'ext.wikihow.specialtechfeedback.styles' );
		$this->out->addModules( 'ext.wikihow.specialtechfeedback', 'ext.wikihow.UsageLogs' );
		$this->out->addModules('ext.wikihow.toolinfo');

		$html = $this->getMainHTML();
		$this->out->addHTML( $html );
	}

	protected function addStandingGroups() {
		$indi = new TechFeedbackStandingsIndividual();
		$indi->addStatsWidget();

		$group = new TechFeedbackStandingsGroup();
		$group->addStandingsWidget();
	}

	public function isMobileCapable() {
		return true;
	}

	public static function isTitleInTechCategory( $title ) {
		$result = CategoryHelper::isTitleInCategory( $title, 'Computers and Electronics' );
		return $result;
	}

	/*
	 * a hook that is called after a rating reason response is made
	 * so we can alter it if we want to
	 * @param $id the rating reason id that was inserted
	 * @param $data the array of data which was used in the insert
	 */
	public static function onRatingReasonAfterGetRatingReasonResponse( $rating, $pageId, &$response ) {
		$title = Title::newFromId( $data['ratr_page_id'] );

		if ( !self::isTitleInTechCategory( $title ) ) {
			$response = wfMessage( 'ratearticle_reason_submitted_mobile' )->text();
		}
	}

	/*
	 * a hook that is called after a rating reason is added
	 * @param $id the rating reason id that was inserted
	 * @param $data the array of data which was used in the insert
	 */
	public static function onRatingsToolRatingReasonAdded( $id, $data ) {
		if ( !$data || !isset( $data['ratr_page_id'] ) ) {
			return;
		}
		$title = Title::newFromId( $data['ratr_page_id'] );
		if ( !self::isTitleInTechCategory( $title ) ) {
			return;
		}
		$textAllowed = self::isTextAllowed( $data['ratr_text'] );
		if ( !$textAllowed ) {
			return;
		}
		$dbw = wfGetDB( DB_MASTER );
		$insertData = array( 'stfi_page_id' => $data['ratr_page_id'], 'stfi_rating_reason_id' => $id );
		$options = array();
		$dbw->insert( self::STF_TABLE, $insertData, __METHOD__, $options );
	}

	public static function isTextAllowed( $text ) {
		if ( strlen( $text ) < 21 ) {
			return false;
		}

		if ( substr_count( $text, ' ' ) < 2 ) {
			return false;
		}

		if ( BadWordFilter::hasBadWord( $text ) ) {
			return false;
		}

		$repeatCount = 0;
		$lastLetter = '';
		for( $i = 0; $i <= strlen( $text ); $i++ ) {
			$char = substr( $text, $i, 1 );
			if ( $char == $lastLetter ) {
				$repeatCount++;
			} else {
				$repeatCount = 0;
			}
			if ( $repeatCount >= 2 ) {
				return false;
			}
			$lastLetter = $char;
		}

		return true;
	}

	private function getMainHTML() {
		$titleTop = '';
		if ( !Misc::isMobileMode() ) {
			$titleTop = '<div id="desktop-title"><div id="header-remaining"><h3 id="header-count"></h3>remaining</div><h5>Review Tech Feedback</h5><div id="header-title"></div></div>';
		}
		$vars = [
			'title_top' => $titleTop,
			'get_next_msg' => wfMessage( 'specialtechfeedbacknext' )->text(),
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'specialtechfeedback', $vars );

		return $html;
	}

	/*
	 * get the next article to vote on
	 */
	private function getNextItem() {
		$dbr = wfGetDb( DB_REPLICA );
		$result = [];
		$conds = [];
		$userId = $this->getUserId();

		//$conds = "stfi_user_id = '' AND stfi_user_id <> '$userId'";
		$conds = array( "stfi_user_id" => array( '', $userId ) );

		$table = self::STF_TABLE;
		$vars = array( 'stfi_page_id', 'stfi_rating_reason_id', 'stfi_user_id' );
		$options = array(
			'GROUP BY' => 'stfi_page_id, stfi_rating_reason_id',
			'HAVING' => array( 'count(*) < 2', "stfi_user_id = ''" ),
			'SQL_CALC_FOUND_ROWS',
		);
		$row = $dbr->selectRow( $table, $vars, $conds, __METHOD__, $options );

		$result = array(
			'pageId' => $row->stfi_page_id,
			'ratingReasonId' => $row->stfi_rating_reason_id
		);

		$res = $dbr->query('SELECT FOUND_ROWS() as count');
		$row = $dbr->fetchRow( $res );
		$this->mUserRemainingCount = $row['count'];

		return $result;
	}

	private function getRatingReason( $pageId, $ratingReasonId ) {
		$dbr = wfGetDb( DB_REPLICA );

		$table = 'rating_reason';
		$vars = 'ratr_id, ratr_text';
		$conds = array( 'ratr_id' => $ratingReasonId );
		$options = array();
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
		$result = array();
		foreach ( $res as $row ) {
			$result[] = array(
				'ratingReasonId' => $row->ratr_id,
				'text' => $row->ratr_text,
				'pageId' => $pageId,
			);
		}
		return $result;
	}

	private function getArticleHtml( $pageId ) {
		if ( Misc::isMobileMode() ) {
			return '';
		}
		$html = '';
		$page = WikiPage::newFromId( $pageId );
		$out = $this->getOutput();
		$popts = $out->parserOptions();
		$popts->setTidy(true);
		$content = $page->getContent();
		if ($content) {
			$parserOutput = $content->getParserOutput($page->getTitle(), null, $popts, false)->getText();
			$html = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN));
			$header = Html::element( 'h2', array(), 'Full Article' );
			$html = $header . $html;
		}
		return $html;
	}

	private function getNextItemData() {
		$nextItem = $this->getNextItem();

		$ratingReasonId = $nextItem['ratingReasonId'];
		$pageId = $nextItem['pageId'];

		if ( !$pageId ) {

			$eoq = new EndOfQueue();
			$msg = $eoq->getMessage('tf');
			return array(
				'html' => Html::rawElement( 'div', array( 'class' => 'text-line empty-queue' ), $msg ),
				'remaining' => 0,
			);
		}

		$ratingReason = $this->getRatingReason( $pageId ,$ratingReasonId );

		$title = Title::newFromID( $pageId );
		$titleText = wfMessage( 'howto', $title->getText() )->text();

		$titleLink = Linker::link( $title, $titleText, ['target'=>'_blank'] );

		if ( Misc::isMobileMode() ) {
			$platformClass = "mobile";
		} else {
			$platformClass = "desktop";
		}
		$articleHtml = $this->getArticleHtml( $pageId );
		$articleLoaded = false;
		if ( $articleHtml ) {
			$articleLoaded = true;
		}
		$vars = [
			'items' => $ratingReason,
			'platformclass' => $platformClass,
			'title' => wfMessage( 'specialtechfeedbacktext', $titleLink )->text(),
			'pageId' => $pageId,
			'titleText' => $title->getText(),
			'tool_info' => class_exists( 'ToolInfo' ) ? ToolInfo::getTheIcon( $this->getContext() ) : ''
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );

		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );

		$html = $m->render( 'specialtechfeedback_inner', $vars );

		$remainingCount = $this->mUserRemainingCount;
		$result = array(
			'html' => $html,
			'articlehtml' => $articleHtml,
			'title' => $titleLink,
			'remaining' => $remainingCount,
			'pageId' => $pageId,
		);
		return $result;
	}

	public static function getRemainingCount() {
		$dbr = wfGetDb( DB_REPLICA );
		$table = self::STF_TABLE;
		$vars = "count('*')";
		$conds = array( "stfi_user_id" => '' );
		$options = array();
		$count = $dbr->selectField( $table, $vars, $conds, __METHOD__, $options );
		return $count;
	}

	private function getUserId() {
		$userId = $this->user->getID();
		if ( !$userId ) {
			$userId = WikihowUser::getVisitorId();
		}
		return $userId;
	}

	private function saveVote() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
		$ratingReasonId = $request->getInt( 'rrid' );
		$pageId = $request->getInt( 'pageid' );
		$userId = $this->getUserId();

		// if for some reason there is no user id then do not try to save the vote
		// because the empty user id is always assumed to have a vote of 0
		if (!$userId) {
			return;
		}

		$vote = $request->getInt( 'vote' );
		if ( $this->isPowerVoter() ) {
			$vote = $vote * 2;
		}

		$table =  self::STF_TABLE;
		$values = array(
			'stfi_page_id' => $pageId,
			'stfi_rating_reason_id' => $ratingReasonId,
			'stfi_user_id' => $userId,
			'stfi_vote' => $vote
		);

		$dbw->insert( $table, $values, __METHOD__ );
		return;
	}

	// count votes on the item that was vote upon
	private function updateVoted() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
		$ratingReasonId = $request->getInt( 'rrid' );
		$pageId = $request->getInt( 'pageid' );

		// if the user skipped then we do not need to recalculate
		$vote = $request->getInt( 'vote' );
		if ( $vote == 0 ) {
			$this->mLogActions[] = 'not_sure';
			return;
		} elseif ( $vote > 0 ) {
			$this->mLogActions[] = 'vote_up';
		} else {
			$this->mLogActions[] = 'vote_down';
		}

		$table =  self::STF_TABLE;
		$var = 'SUM(stfi_vote)';
		$cond = array(
			'stfi_page_id' => $pageId,
			'stfi_rating_reason_id' => $ratingReasonId,
		);

		$count = $dbw->selectField( $table, $var, $cond, __METHOD__ );
		if ( abs( $count ) >= self::MAX_VOTES ) {
			if ( $count >= self::MAX_VOTES ) {
				$this->mLogActions[] = 'approved';
			} else {
				$this->mLogActions[] = 'rejected';
			}
			// this item is completed.. remove it from the queue
			$conds = array(
				'stfi_page_id' => $pageId,
				'stfi_rating_reason_id' => $ratingReasonId,
				'stfi_vote' => 0
			);
			$dbw->delete( $table, $conds, __METHOD__ );

			$title = Title::newFromID( $pageId );
			Hooks::run("SpecialTechFeedbackItemCompleted", array($wgUser, $title, '0'));
		}

		// log the actions
		foreach ( $this->mLogActions as $action ) {
			$this->logVote( $action );
		}

		return;
	}

	private function logVote( $action ) {
		$request = $this->getContext()->getRequest();
		$ratingReasonId = $request->getInt( 'rrid' );
		$pageId = $request->getInt( 'pageid' );

		$title = Title::newFromId( $pageId );
		$logPage = new LogPage( 'tech_update_tool', false );
		$logData = array( $ratingReasonId );
		$logMsg = wfMessage( 'specialtechfeedbacklogentryvote', $title->getFullText(), $action, $ratingReasonId )->text();
		$logPage->addEntry( $action, $title, $logMsg, $logData );

		UsageLogs::saveEvent(
			array(
				'event_type' => 'tech_update_tool',
				'event_action' => $action,
				'article_id' => $pageId,
				'assoc_id' => $ratingReasonId
			)
		);
	}

	private function isPowerVoter() {
		if ( $this->user->isAnon() ) {
			return false;
		}
		//check groups
		$userGroups = $this->user->getGroups();
		if ( empty( $userGroups ) || !is_array( $userGroups ) ) {
			return false;
		}
		return ( in_array( 'staff', $userGroups ) || in_array( 'admin', $userGroups ) || in_array( 'newarticlepatrol', $userGroups ) );
	}

	/*
	 * posts voted in data to google spreadsheet
	 */
	public static function sendToSpreadsheet( $pageId, $date, $comment ) {
		$file = self::getSheetsFile();
		$sheet = $file->sheet('default');
		$data = self::getVoteSpreadsheetData( $pageId, $date, $comment );
		$sheet->insert( $data );
	}

	/*
	 * @return array data to be sent to feedback google sheet
	 */
	private static function getVoteSpreadsheetData( $pageId, $date, $comment ) {
		global $wgLanguageCode;
		$dbr = wfGetDB( DB_REPLICA );
		$table = array( 'titus_copy' );
		$vars = array( 'ti_page_title', 'ti_30day_views_unique', 'ti_helpful_percentage', 'ti_helpful_total' );
		$conds = array(
			'ti_page_id' => $pageId,
			'ti_language_code' => $wgLanguageCode,
		);
		$options = array();

		$row = $dbr->selectRow( $table, $vars, $conds, __METHOD__, $options );

		$date = date( 'Y-m-d', $date );
		$data = array(
			'pageid' => $pageId,
			'pageurl' => 'http://www.wikihow.com/'.$row->ti_page_title,
			'pv-30d-unique' => $row->ti_30day_views_unique,
			'helpful-vote-count' => $row->ti_helpful_total,
			'helpful-percent' => $row->ti_helpful_percentage,
			'comment-text' => $comment,
			'comment-approved-date' => $date,
		);
		return $data;
	}

	/**
	 * @return Google_Spreadsheet_File
	 */
	private static function getSheetsFile(): Google_Spreadsheet_File {
		global $wgIsProduction;

		$keys = (Object)[
			'client_email' => WH_GOOGLE_SERVICE_APP_EMAIL,
			'private_key' => file_get_contents(WH_GOOGLE_DOCS_P12_PATH)
		];
		$client = Google_Spreadsheet::getClient($keys);

		// Set the curl timeout within the raw google client.  Had to do it this way because the google client
		// is a private member within the Google_Spreadsheet_Client
		$rawClient = function(Google_Spreadsheet_Client $client) {
			return $client->client;
		};
		$rawClient = Closure::bind($rawClient, null, $client);
		$timeoutLength = 600;
		$configOptions = [
			CURLOPT_CONNECTTIMEOUT => $timeoutLength,
			CURLOPT_TIMEOUT => $timeoutLength
		];
		$rawClient($client)->setClassConfig('Google_IO_Curl', 'options', $configOptions);

		if ($wgIsProduction) {
			$fileId = '1wmyrN1fzNMqTiAnRSucVYBPL-khCr4mrTzexhyKmpfI';
		} else {
			$fileId = '1GOXHa5YBpHyZGObHnIoA724tl3ZHGjR5dnFbgwEk6M8';
		}
		$file = $client->file($fileId);

		return $file;
	}
}
