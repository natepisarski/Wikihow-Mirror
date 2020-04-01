<?php

class QcAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if ($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:QG' class='comdash-start'>Start";
		elseif ($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:QG' class='comdash-login'>Login";
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
		return "qc";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$res = $dbr->select('qc_vote', array('qcv_user','qc_timestamp'), array(), 'QcAppWidget::getLastContributor', array("ORDER BY"=>"qc_timestamp DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->qcv_user;
			$timestamp = $row->qc_timestamp;
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
		global $wgSharedDB;
		$startdate = strtotime("7 days ago");
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
		$starttimestamp = $dbr->addQuotes($starttimestamp);
		$sql = "SELECT qcv_user, SUM(C) as C, MAX(qc_timestamp) as qc_recent  FROM
			( (SELECT qcv_user, count(*) as C, MAX(qc_timestamp) as qc_timestamp FROM qc_vote LEFT JOIN $wgSharedDB.user ON qcv_user=user_id
				WHERE qc_timestamp > {$starttimestamp} GROUP BY qcv_user ORDER BY C DESC LIMIT 25)
			UNION
			(SELECT qcv_user, count(*) as C, MAX(qc_timestamp) as qc_timestamp from qc_vote_archive LEFT JOIN $wgSharedDB.user ON qcv_user=user_id
				WHERE qc_timestamp > {$starttimestamp} GROUP BY qcv_user ORDER BY C DESC LIMIT 25) ) t1
			GROUP BY qcv_user  order by C desc limit 1";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->qcv_user;
			$timestamp = $row->qc_recent;
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
		return array('QcAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('QcAppWidget.css');
	}

	/*
	 * Returns the number of images left to be added.
	 */
	public function getCount(&$dbr){
		return QG::getUnfinishedCount($dbr);
	}

	public function getUserCount(){
		$standings = new QCStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(){
		$standings = new QCStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){

		$data = LeaderboardStats::getQCPatrols($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/qc?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		if (!$isLoggedIn)
			return false;
		else
			return true;
	}

}
