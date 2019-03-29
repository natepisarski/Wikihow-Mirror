<?php

class CategoryGuardianAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	public function getMWName(){
		return "catch";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$res = $dbr->select('logging', array('log_user','log_timestamp'), array('log_type' => CategoryGuardian::LOG_TYPE), __FUNCTION__, array("ORDER BY"=>"log_timestamp DESC", "LIMIT" => 1));
		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->log_user;
			$timestamp = $row->log_timestamp;
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

		$startdate = strtotime("7 days ago");
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
		$res = $dbr->select(
			'logging',
			array(
				'log_user',
				'count(*) as C', 'MAX(log_timestamp) as log_recent'
			),
			array(
				'log_type' => CategoryGuardian::LOG_TYPE,
				"log_timestamp >= '$starttimestamp'"
			),
			__FUNCTION__,
			array(
				"GROUP BY" => 'log_user',
				"ORDER BY"=>"C DESC",
				"LIMIT"=>1
			));

		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->log_user;
			$timestamp = $row->log_recent;
		}
		else {
			$user = '';
			$timestamp = '';
		}

		return $this->populateUserObject($user, $timestamp);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if ($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:CategoryGuardian' class='comdash-start'>Start";
		elseif ($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:CategoryGuardian' class='comdash-login'>Login";
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

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('CategoryGuardianAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('CategoryGuardianAppWidget.css');
	}

	/*
	 * Returns the number of changes left to be patrolled.
	 */
	public function getCount(&$dbr){
		return CategoryGuardian::getRemainingCount();
	}

	public function getUserCount(&$dbr){
		$standings = new CategoryGuardianStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(&$dbr){
		$standings = new CategoryGuardianStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = LeaderboardStats::getCategoryguarded($starttimestamp);
		arsort($data);
		return $data;

	}

	public function getLeaderboardTitle(){
		return $this->getTitle();
	}

	public function isAllowed($isLoggedIn, $userId=0){
		// anons are allowed to category check
		return true;
	}

}
