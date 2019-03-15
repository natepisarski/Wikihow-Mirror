<?php

class QAPatrolWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if ($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:QAPatrol' class='comdash-start'>Start";
		elseif ($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:QAPatrol' class='comdash-login'>Login";
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
		return "qap";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$where = [];

		$qa_editor_ids = WikihowUser::getUserIDsByUserGroup('qa_editors');
		if (!empty($qa_editor_ids)) {
			$where[] = 'qapv_user_id IN ('.$dbr->makeList($qa_editor_ids).')';
		}

		$res = $dbr->select(
			'qap_vote',
			[
				'qapv_user_id',
				'qapv_timestamp'
			],
			$where,
			__METHOD__,
			[
				'ORDER BY' => 'qapv_timestamp DESC',
				'LIMIT' => 1
			]
		);
		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->qapv_user_id;
			$timestamp = $row->qapv_timestamp;
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
	public function getTopContributor(&$dbr) {
		global $wgSharedDB;
		$startdate = strtotime("7 days ago");
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';
		$starttimestamp = $dbr->addQuotes($starttimestamp);

		$qa_editor_ids = WikihowUser::getUserIDsByUserGroup('qa_editors');
		$qa_editor_ids_sql = !empty($qa_editor_ids) ? 'AND qapv_user_id IN ('.$dbr->makeList($qa_editor_ids).')' : '';

		$sql = "
			SELECT qapv_user_id, SUM(C) AS C, MAX(qapv_timestamp) AS qap_recent
			  FROM (SELECT qapv_user_id, count(*) AS C, MAX(qapv_timestamp) AS qapv_timestamp
					  FROM qap_vote LEFT JOIN $wgSharedDB.user ON qapv_user_id = user_id
					 WHERE qapv_timestamp > {$starttimestamp} $qa_editor_ids_sql
					 GROUP BY qapv_user_id
					 ORDER BY C DESC
					 LIMIT 25) t1
			GROUP BY qapv_user_id
			ORDER BY C DESC
			LIMIT 1";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		$res->free();

		if (!empty($row)) {
			$user = $row->qapv_user_id;
			$timestamp = $row->qap_recent;
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
		return array('QAPatrolWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('QAPatrolWidget.css');
	}

	/*
	 * Returns the number of images left to be added.
	 */
	public function getCount(&$dbr){
		return QAPatrol::getRemaining($dbr);
	}

	public function getUserCount(){
		$standings = new QAPatrolStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(){
		$standings = new QAPatrolStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){

		$data = LeaderboardStats::getQAPatrollers($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/qap?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		if (!$isLoggedIn)
			return false;
		elseif ($isLoggedIn && $userId == 0)
			return false;
		else{
			$user = User::newFromId($userId);
			return QAPatrol::isAllowed($user);
		}
	}

}



