<?php

class WriteAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if ($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:ListRequestedTopics' class='comdash-start'>Start";
		elseif ($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:ListRequestedTopics' class='comdash-login'>Login";
		elseif ($widgetStatus == DashboardWidget::WIDGET_DISABLED)
			$link = "<a href='/Become-a-New-Article-Booster-on-wikiHow' class='comdash-start'>Start";
		if ($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/owl/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	public function showMobileCount() {
		return true;
	}

	public function getMWName(){
		return "Write";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (sizeof($bots) > 0) {
			$bot = " AND fe_user NOT IN (" . $dbr->makeList($bots) . ", '0') ";
		}

		$sql = "SELECT fe_timestamp, fe_user ".
				"FROM firstedit left join page on fe_page = page_id left join suggested_titles on page_title= st_title " .
				"WHERE st_isrequest IS NOT NULL" . $bot . " ORDER BY fe_timestamp DESC LIMIT 1";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->fe_user;
			$timestamp = $row->fe_timestamp;
		}
		else {
			$user = '';
			$timestamp = '';
		}

		return $this->populateUserObject($user, $timestamp);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		$startdate = strtotime('7 days ago');
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
		$starttimestamp = $dbr->addQuotes($starttimestamp);
		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (sizeof($bots) > 0) {
			$bot = " AND fe_user NOT IN (" . $dbr->makeList($bots) . ", '0') ";
		}

		$sql = "SELECT fe_user_text, fe_user, count(fe_user) as fe_count, MAX(fe_timestamp) as fe_timestamp ".
				"FROM firstedit left join page on fe_page = page_id left join suggested_titles on page_title= st_title " .
				"WHERE fe_timestamp >= {$starttimestamp} AND st_isrequest IS NOT NULL" . $bot . " GROUP BY fe_user ORDER BY fe_count DESC";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->fe_user;
			$timestamp = $row->fe_timestamp;
		}
		else {
			$user = '';
			$timestamp = '';
		}

		return $this->populateUserObject($user, $timestamp);
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('WriteAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('WriteAppWidget.css');
	}

	/*
	 * Returns the number of videos left to be added.
	 */
	public function getCount(&$dbr){
		return ListRequestedTopics::getUnwrittenTopics(true);
	}

	public function getUserCount(&$dbr){
		global $wgUser, $wgLang;
		//can't do it with the usual Standings class because the sql query doesn't fit that model
		$ts_week = date('Ymd',strtotime('7 days ago')) . '000000';
		$timecorrection = $wgUser->getOption( 'timecorrection' );
		$ts_week = $wgLang->userAdjust( $ts_week, $timecorrection );
		$userId = $wgUser->getID();

		$sql = "SELECT count(fe_user) as C ".
				"FROM firstedit left join page on fe_page = page_id left join suggested_titles on page_title= st_title " .
				"WHERE st_isrequest IS NOT NULL and fe_user = '{$userId}' AND fe_timestamp >= '$ts_week'";
		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		return $row->C;
	}

	public function getAverageCount(){
		$standings = new RequestsAnsweredStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = LeaderboardStats::getRequestedTopics($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/requested_topics?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		return true;
	}

}
