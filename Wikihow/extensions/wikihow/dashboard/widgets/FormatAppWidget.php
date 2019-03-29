<?php

class FormatAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if ($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:EditFinder/Format' class='comdash-start'>Start";
		elseif ($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:EditFinder/Format' class='comdash-login'>Login";
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
		return "format";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$sql = "";
		$bots = WikihowUser::getBotIDs();

		if (sizeof($bots) > 0) {
			$sql = "log_user NOT IN (" . $dbr->makeList($bots) . ")";
		}

		if ($sql != "")
			$res = $dbr->select('logging', array('log_user','log_timestamp'), array('log_type' => 'EF_format', $sql), 'FormatAppWidget::getLastContributor', array("ORDER BY"=>"log_timestamp DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('logging', array('log_user','log_timestamp'), array('log_type' => 'EF_format'), 'FormatAppWidget::getLastContributor', array("ORDER BY"=>"log_timestamp DESC", "LIMIT"=>1));
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

		$sql = "";
		$bots = WikihowUser::getBotIDs();

		if (sizeof($bots) > 0) {
			$sql = "log_user NOT IN (" . $dbr->makeList($bots) . ")";
		}

		if ($sql != "")
			$res = $dbr->select('logging', array('log_user', 'count(*) as C', 'MAX(log_timestamp) as log_recent'), array('log_type' => 'EF_format', 'log_timestamp >= "' . $starttimestamp . '"', $sql), 'FormatAppWidget::getTopContributor', array("GROUP BY" => 'log_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('logging', array('log_user', 'count(*) as C', 'MAX(log_timestamp) as log_recent'), array('log_type' => 'EF_format', 'log_timestamp >= "' . $starttimestamp . '"'), 'FormatAppWidget::getTopContributor', array("GROUP BY" => 'log_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));
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

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('FormatAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('FormatAppWidget.css');
	}

	/*
	 * Returns the number of images left to be added.
	 */
	public function getCount(&$dbr){
		return EditFinder::getUnfinishedCount($dbr, 'Format');
	}

	public function getUserCount(){
		$standings = new EditFinderStandingsIndividual('format');
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(){
		$standings = new EditFinderStandingsGroup('format');
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){

		$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'format');
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/repair_format?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		if (!$isLoggedIn)
			return false;
		else
			return true;
	}

}
