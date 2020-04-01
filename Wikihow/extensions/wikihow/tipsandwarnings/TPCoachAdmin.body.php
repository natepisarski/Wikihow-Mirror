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
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$result = array();
			$result['debug'][] = "posted to tpcoachadmin";
			$action = $req->getVal("action");
			if ($action == "newtest") {
				$this->addNewTest($result);
			} elseif ($action == "unblockuser") {
				$this->unBlockUser($result);
			} elseif ($action == "blockuser") {
				$this->blockUser($result);
			} elseif ($action == "reset") {
				$this->resetState($result);
			//} elseif ($action == "tpc_toggle") {
			//	$this->toggleTPCoach($result);
			} elseif ($action == "delete_test") {
				$this->deleteTest($result);
			}
			print json_encode($result);
			return;
		}

		$out->setPageTitle('TipsPatrol Coach Admin');
		EasyTemplate::set_path( __DIR__.'/' );
		$this->ts = wfTimestamp(TS_MW, time() - 24 * 3600 * $req->getVal("days", 7));
		$this->printData();
	}

	private function unBlockUser(&$result) {
		$userId = $this->getRequest()->getVal("userId");
		TipsPatrol::setUserBlocked($userId, false, $result);
	}

	private function blockUser(&$result) {
		$userId = $this->getRequest()->getVal("userId");
		TipsPatrol::setUserBlocked($userId, true, $result);
	}

	private function resetState(&$result) {
		$result['debug'][] = "will reset user view state";
		$userId = $this->getRequest()->getVal("userId");
		TipsPatrol::resetUserViews($userId, $result);
	}

	private function deleteTest(&$result) {
		$result['debug'][] = "will delete test";
		$testId = $this->getRequest()->getVal("testId");
		TipsPatrol::deleteTest($testId);
		$result['success'] = true;
	}

	private function addNewTest(&$result) {
		$result['debug'][] = "will addNewTest";
		$tip = $this->getRequest()->getVal("tip");
		$page = $this->getRequest()->getVal("page");
		$failMessage = $this->getRequest()->getVal("failMessage");
		$successMessage = $this->getRequest()->getVal("successMessage");
		$difficulty = $this->getRequest()->getVal("difficulty");
		$answer = $this->getRequest()->getVal("answer");
		TipsPatrol::addTest($tip, $page, $failMessage, $successMessage, $answer, $difficulty, $result);
	}

	private function printData() {
		$coachEnabled = TipsPatrol::isTPCoachEnabled();
		$vars['disableDisplay'] = $coachEnabled ? "block":"none";
		$vars['enableDisplay'] = $coachEnabled ? "none":"block";
		$vars['scores'] = $this->getScores();
		$vars['tests'] = $this->getTests();
		$vars['days'] = $this->getRequest()->getVal("days", 7);
		$vars['css'] = HtmlSnips::makeUrlTag('/extensions/wikihow/tipsandwarnings/tpcoachadmin.css', true);
		$this->getOutput()->addScript( HtmlSnips::makeUrlTag('/extensions/wikihow/tipsandwarnings/tpcoachadmin.js', true) );
		$html = EasyTemplate::html('TPCoachAdmin', $vars);
		$this->getOutput()->addHTML($html);
	}

	private function getTests() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('tipspatrol_test', '*', '', __METHOD__, ["ORDER BY" => "tpt_id DESC"]);

		$tests = array();
		foreach ($res as $row) {
			$test = get_object_vars($row);
			$t = Title::newFromID($row->tpt_page_id);
			$test['page'] = (string)$t;
			$test['user'] = User::whoIs($row->tpt_user_id);
			if ($row->tpt_difficulty == TipsPatrol::TPC_DIFFICULTY_EASY) {
				$test['difficulty'] = "Easy";
			}

			if ($row->tpt_answer == TipsPatrol::TIP_ACTION_DELETE) {
				$test['answer'] = "Delete";
			} elseif ($row->tpt_answer == TipsPatrol::TIP_ACTION_KEEP) {
				$test['answer'] = "Keep";
			} elseif ($row->tpt_answer == TipsPatrol::TIP_ACTION_SKIP) {
				$test['answer'] = "Skip";
			}

			$tests[$test['tpt_id']] = $test;
		}
		return $tests;
	}

	private function getScores() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('tipspatrol_completed_test',
			array('tpc_user_id as user_id', 'tpc_test_id', 'count(tpc_user_id) as total'),
			array("tpc_timestamp >= '{$this->ts}'"),
			__METHOD__,
			array('GROUP BY' => 'tpc_user_id', 'ORDER BY' => 'total DESC'));

		$scores = array();
		foreach ($res as $row) {
			$userId = (int)$row->user_id;
			$score = get_object_vars($row);
			$rowData = $dbr->selectRow('tipspatrol_views', array("tpv_count", "tpv_user_blocked"), "tpv_user_id = $userId", __METHOD__ );
			$user = User::newFromId($row->user_id);
			$score['user_name'] = $user->getName();
			$score['user_link'] = $user->getUserPage();
			$total = $score['total'];
			$score['easy'] = $total;
			$correct = $dbr->selectField('tipspatrol_completed_test', 'count(*)', ['tpc_user_id' => $userId, 'tpc_score' => 1], __METHOD__);
			$incorrect = $dbr->selectField('tipspatrol_completed_test', 'count(*)', ['tpc_user_id' => $userId, 'tpc_score' => 0], __METHOD__);
			$score['incorrect'] = $this->percent($incorrect, $total);
			$score['correct'] = $this->percent($correct, $total);
			$score['percent_easy'] = $this->percent($total, $total);
			$score['patrol_count'] = $rowData->tpv_count ?: 0;
			$score['block'] = $rowData->tpv_user_blocked ? "Unblock": "Block";
			$scores[$score['user_id']] = $score;
		}

		return $scores;
	}

	private function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0) . "%";
	}

}
