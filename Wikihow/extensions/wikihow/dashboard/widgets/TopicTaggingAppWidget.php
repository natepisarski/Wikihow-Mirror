<?php
class TopicTaggingAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	public function getMWName() {
		return 'ttt';
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus) {
		if ($widgetStatus == DashboardWidget::WIDGET_ENABLED || $widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:TopicTagging' class='comdash-start'>Start";
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
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr) {
		$bots = WikihowUser::getBotIDs();
		$botsql = sizeof($bots) > 0 ? "log_user NOT IN (" . $dbr->makeList($bots) . ")" : '';

		$res = $dbr->select(
			'logging',
			[
				'log_user',
				'log_timestamp'
			],
			[
				'log_type' => 'topic_tagging',
				$botsql
			],
			__METHOD__,
			[
				"ORDER BY" => "log_timestamp DESC",
				"LIMIT" => 1
			]
		);
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
	public function getTopContributor(&$dbr) {
		$startdate = strtotime("7 days ago");
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$bots = WikihowUser::getBotIDs();
		$botsql = sizeof($bots) > 0 ? "log_user NOT IN (" . $dbr->makeList($bots) . ")" : '';

		$res = $dbr->select(
			'logging',
			[
				'log_user',
				'count(*) as C',
				'MAX(log_timestamp) as log_recent'
			],
			[
				'log_type' => 'topic_tagging',
				'log_timestamp >= "' . $starttimestamp . '"',
				$botsql
			],
			__METHOD__,
			[
				"GROUP BY" => 'log_user',
				"ORDER BY" => "C DESC",
				"LIMIT" => 1
			]
		);
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
		return array('TopicTaggingAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return [];
	}

	public function getCount(&$dbr) {
		return SensitiveArticle\SensitiveArticleVote::remainingCount();
	}

	public function getUserCount(&$dbr){
		$standings = new TopicTaggingStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(&$dbr){
		$standings = new TopicTaggingStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){
		$data = LeaderboardStats::getTopicsTagged($starttimestamp);
		arsort($data);
		return $data;
	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/topicstagged?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		return true;
	}

}
