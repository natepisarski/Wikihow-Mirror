<?php

class SortQuestionsStandingsIndividualNew extends StandingsIndividual {

	function __construct() {
		$this->mLeaderboardKey = "questionssorted";
	}

	function getTable() {
		return "logging";
	}

	function getTitle() {
		return wfMessage( 'sortquestions_currentstats' )->text();
	}

	function getOpts( $ts = null ) {
		global $wgUser;
		$opts = array();
		$opts['log_user'] =$wgUser->getID();
		$opts['log_type'] ='sort_questions_tool';

		if ( $ts ) {
			$opts[]= "log_timestamp >'{$ts}'";
		}
		return $opts;
	}

	function getGroupStandings() {
		return new SortQuestionsStandingsGroup();
	}
}
