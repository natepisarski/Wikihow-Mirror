<?php

/*
 * This is a weird widget.
 * It's more of a static link to the Quiz Yourself web app
 * Nothing to update or anything
 */
class QuizYourselfWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	public function getStartLink($showArrow, $widgetStatus){
		$link = "<a href='/Special:QuizYourself' class='comdash-start'>Start";
		if ($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/owl/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	public function showMobileCount() {
		return false;
	}

	public function getMWName(){
		return "qy";
	}

	public function getLastContributor(&$dbr){
		return [];
	}

	public function getTopContributor(&$dbr){
		return [];
	}

	public function getJSFiles() {
		return [];
	}

	public function getCSSFiles() {
		return [];
	}

	public function getCount(&$dbr){
		return 0;
	}

	public function getUserCount(){
		return 0;
	}

	public function getAverageCount(){
		return 0;
	}

	public function getLeaderboardData(&$dbr, $starttimestamp){
		return [];
	}

	public function getLeaderboardTitle(){
		return '';
	}

	public function isAllowed($isLoggedIn, $userId=0){
		return true;
	}

}
