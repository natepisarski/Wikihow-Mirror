<?php

class WelcomeWagonWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	public function getMWName(){
		return "welcomewagon";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$res = $dbr->select('logging', array('log_timestamp', 'log_user'), array("log_type" => "welcomewag"), __METHOD__, array("ORDER BY"=>"log_timestamp DESC"));
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
		$res = $dbr->select('logging', array('log_user', 'count(*) as C', 'MAX(log_timestamp) as recent_timestamp'),
			array("log_type" => "welcomewag",  "log_timestamp > '$starttimestamp'"), __METHOD__, array("GROUP BY" => 'log_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));

		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->log_user;
			$timestamp = $row->recent_timestamp;
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
			$link = "<a href='/Special:WelcomeWagon' class='comdash-start'>Start";
		elseif ($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:WelcomeWagon' class='comdash-login'>Login";
		elseif ($widgetStatus == DashboardWidget::WIDGET_DISABLED)
			$link = "<a href='/Become-a-Welcome-Wagon-User-on-wikiHow' class='comdash-start'>Start";
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
		return array('WelcomeWagonWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array();
	}

	/*
	 * Returns the number of changes left to be patrolled.
	 */
	public function getCount(&$dbr){
		return WelcomeWagon::getCount();
	}

	public function getUserCount(&$dbr){
		$standings = new CategorizationStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(&$dbr){
		$standings = new CategorizationStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = LeaderboardStats::getWelcomeWagon($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return $this->getTitle();
	}

	public function isAllowed($isLoggedIn, $userId=0){

		if (!$isLoggedIn || $userId <= 0) {
			return false;
		}

		$user = User::newFromId($userId);
		$userGroups = $user->getGroups();

		if ((in_array('staff', $userGroups) ||
			 in_array('staff_widget', $userGroups) ||
			 in_array('welcome_wagon', $userGroups))) {
			return true;
		}

		return false;
	}

}
