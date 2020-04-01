<?php

class AnswerQuestionsAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus) {
		if ($widgetStatus == DashboardWidget::WIDGET_ENABLED || $widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:AnswerQuestions' class='comdash-start'>Start";
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

	public function getMWName() {
		return "answerquestions";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr) {
		return $this->populateUserObject(0, 0);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr) {
		return $this->populateUserObject(0, 0);
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('AnswerQuestionsAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('AnswerQuestionsAppWidget.css');
	}

	/*
	 * Returns the number of images left to be added.
	 */
	public function getCount(&$dbr) {
		$dbr = wfGetDB(DB_REPLICA);

		$count = $dbr->selectField([AnswerQuestions::TABLE_QUEUE, QADB::TABLE_SUBMITTED_QUESTIONS], 'count(*)', ['aqq_page = qs_article_id', 'qs_ignore' => 0, 'qs_curated' => 0, 'qs_proposed' => 0], __METHOD__);
		$count = floor($count/10);
		return $count;
	}

	public function getUserCount() {
		return 0;
	}

	public function getWeatherClass($count) {
		return wfMessage('cd-answerquestions-weather');
	}

	public function getAverageCount() {
		return 0;
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp) {

		$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'topic');
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle() {
		return "";
	}

	public function isAllowed($isLoggedIn, $userId=0) {
		return true;
	}

	/*******
	 * We don't want this widget's data to show up in user contributions
	 ******/
	public function getUserStats(&$dbr) {
		return null;
	}

}
