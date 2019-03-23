<?php

class CategoryGuardianStandingsGroup extends StandingsGroup  {
	function __construct() {
		parent::__construct("current-stats");
	}

	function getSQL($ts) {
		global $wgSharedDB;
		$key = CategoryGuardian::LOG_TYPE;

		return "SELECT user_name, COUNT(*) as C FROM logging
			LEFT JOIN $wgSharedDB.user ON log_user = user_id
			WHERE log_type = '$key' AND log_timestamp >= '$ts'
			AND log_user != 0
			GROUP BY user_name ORDER BY C DESC LIMIT 25";
	}

	function getField() {
		return "user_name";
	}

	function getTitle() {
		return wfMessage('leaderboard-title')->text();
	}
}
