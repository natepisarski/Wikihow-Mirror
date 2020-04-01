<?php

class CategoryGuardianStandingsIndividual extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "CategoryGuardian";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMessage('current-stats')->text();
	}

	function getOpts($ts = null) {
		global $wgUser;
		$opts = array(
			'log_user' => $wgUser->getID(),
			'log_type' => CategoryGuardian::LOG_TYPE
		);
		if ($ts) {
			$opts[] = "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new CategoryGuardianStandingsGroup();
	}
}
