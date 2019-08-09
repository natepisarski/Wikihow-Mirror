<?php

class SortQuestionsStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct( "sortquestions_standings" );
	}

	function getSQL( $ts ) {
		global $wgSharedDB;
		$sql = "SELECT user_name, count(*) as C ".
			"FROM logging left join ".$wgSharedDB.".user on log_user = user_id ".
			"WHERE log_type = 'sort_questions_tool' and log_timestamp >= '$ts' ".
			"GROUP BY user_name ORDER by C desc limit 25";
		return $sql;
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage( 'sortquestionsstats_leaderboard_title' )->text();
	}
}
