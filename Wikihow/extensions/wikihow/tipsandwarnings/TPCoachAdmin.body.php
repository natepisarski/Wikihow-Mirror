<?php

class TPCoachAdmin extends UnlistedSpecialPage {
	var $ts = null;
	const DIFFICULTY_EASY = 1;
	const DIFFICULTY_MEDIUM = 2;
	const DIFFICULTY_HARD = 3;

	public function __construct() {
		parent::__construct( 'TPCoachAdmin' );
	}

	public function execute($par) {
		global $wgUser, $wgOut, $wgRequest;

		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		if ($wgRequest->wasPosted()) {
			$wgOut->disable();
			$result = array();
			$result['debug'][] = "posted to tpcoachadmin";
			if ($wgRequest->getVal("action") == "newtest") {
				$this->addNewTest($result);
			} else if ($wgRequest->getVal("action") == "unblockuser") {
				$this->unBlockUser($result);
			} else if ($wgRequest->getVal("action") == "blockuser") {
				$this->blockUser($result);
			} else if ($wgRequest->getVal("action") == "reset") {
				$this->resetState($result);
			//} else if ($wgRequest->getVal("action") == "tpc_toggle") {
			//	$this->toggleTPCoach($result);
			} else if ($wgRequest->getVal("action") == "delete_test") {
				$this->deleteTest($result);
			}
			echo json_encode($result);
			return;
		}

		$wgOut->setPageTitle('TipsPatrol Coach Admin');
		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$this->ts = wfTimestamp(TS_MW, time() - 24 * 3600 * $wgRequest->getVal("days", 7));
		$this->printData();
	}

	/* disabled functionality because changed ConfigStorage to MW message - Reuben 2016
	private function toggleTPCoach(&$result) {
		global $wgRequest;
		$setting = $wgRequest->getVal("setting");
		if ($setting == "on") {
			TipsPatrol::enableTPCoach();
		} else if ($setting == "off") {
			TipsPatrol::disableTPCoach();
		}
		$dbw = wfGetDB(DB_MASTER);
		$result['debug'][] = 'last query: '.$dbw->lastQuery();
		$result['success'] = true;
	}
	*/

	private function unBlockUser(&$result) {
		global $wgRequest;
		$userId = $wgRequest->getVal("userId");
		TipsPatrol::setUserBlocked($userId, false, $result);
	}

	private function blockUser(&$result) {
		global $wgRequest;
		$userId = $wgRequest->getVal("userId");
		TipsPatrol::setUserBlocked($userId, true, $result);
	}

	private function resetState(&$result) {
		global $wgRequest;
		$result['debug'][] = "will reset user view state";
		$userId = $wgRequest->getVal("userId");
		TipsPatrol::resetUserViews($userId, $result);
	}

	private function deleteTest(&$result) {
		global $wgRequest;
		$result['debug'][] = "will delete test";
		$testId = $wgRequest->getVal("testId");
		TipsPatrol::deleteTest($testId);
		$result['success'] = true;
	}

	private function addNewTest(&$result) {
		global $wgRequest;
		$result['debug'][] = "will addNewTest";
		$tip = $wgRequest->getVal("tip");
		$page = $wgRequest->getVal("page");
		$failMessage = $wgRequest->getVal("failMessage");
		$successMessage = $wgRequest->getVal("successMessage");
		$difficulty = $wgRequest->getVal("difficulty");
		$answer = $wgRequest->getVal("answer");
		TipsPatrol::addTest($tip, $page, $failMessage, $successMessage, $answer, $difficulty, $result);
	}

	function printData() {
		global $wgOut, $wgRequest;

		$coachEnabled = TipsPatrol::isTPCoachEnabled();
		$vars['disableDisplay'] = $coachEnabled ? "block":"none";
		$vars['enableDisplay'] = $coachEnabled ? "none":"block";
		$vars['scores'] = $this->getScores();
		$vars['tests'] = $this->getTests();
		$vars['days'] = $wgRequest->getVal("days", 7);
		$vars['css'] = HtmlSnips::makeUrlTag('/extensions/wikihow/tipsandwarnings/tpcoachadmin.css', true);
		$wgOut->addScript( HtmlSnips::makeUrlTag('/extensions/wikihow/tipsandwarnings/tpcoachadmin.js', true) );
		$html = EasyTemplate::html('TPCoachAdmin', $vars);
		$wgOut->addHTML($html);
	}

	function getTests() {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('tipspatrol_test', '*', '', __METHOD__, array("ORDER BY" => "tpt_id DESC"));

		$tests = array();
		while ($row = $dbr->fetchObject($res)) {
			$test = get_object_vars($row);
			$t = Title::newFromID($row->tpt_page_id);
			$test['page'] = (string)$t;
			$test['user'] = User::whoIs($row->tpt_user_id);
			if ($row->tpt_difficulty == TipsPatrol::TPC_DIFFICULTY_EASY) {
				$test['difficulty'] = "Easy";
			}

			if ($row->tpt_answer == TipsPatrol::TIP_ACTION_DELETE) {
				$test['answer'] = "Delete";
			} else if ($row->tpt_answer == TipsPatrol::TIP_ACTION_KEEP) {
				$test['answer'] = "Keep";
			} else if ($row->tpt_answer == TipsPatrol::TIP_ACTION_SKIP) {
				$test['answer'] = "Skip";
			}

			$tests[$test['tpt_id']] = $test;
		}
		return $tests;
	}

	function getScores() {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('tipspatrol_completed_test',
			array('tpc_user_id as user_id', 'tpc_test_id', 'count(tpc_user_id) as total'),
			array("tpc_timestamp >= '{$this->ts}'"),
			__METHOD__,
			array('GROUP BY' => 'tpc_user_id', 'ORDER BY' => 'total DESC'));

		$scores = array();
		while ($row = $dbr->fetchObject($res)) {
			$userId = intval($row->user_id);
			$score = get_object_vars($row);
			$rowData = $dbr->selectRow('tipspatrol_views', array("tpv_count", "tpv_user_blocked"), "tpv_user_id = $userId" );
			$user = User::newFromId($row->user_id);
			$score['user_name'] = $user->getName();
			$score['user_link'] = $user->getUserPage();
			$total = $score['total'];
			$score['easy'] = $total;
			$correct = $dbr->selectField('tipspatrol_completed_test', 'count(*)', array('tpc_user_id' => $userId, "tpc_score" => 1));
			$incorrect = $dbr->selectField('tipspatrol_completed_test', 'count(*)', array('tpc_user_id' => $userId, "tpc_score" => 0));
			$score['incorrect'] = $this->percent($incorrect, $total);
			$score['correct'] = $this->percent($correct, $total);
			$score['percent_easy'] = $this->percent($total, $total);
			$score['patrol_count'] = $rowData->tpv_count ?: 0;
			$score['block'] = $rowData->tpv_user_blocked ? "Unblock": "Block";
			$scores[$score['user_id']] = $score;
		}

		return $scores;
	}

	function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0) . "%";
	}

}
