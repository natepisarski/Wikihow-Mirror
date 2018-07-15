<?php

class RCTestAdmin extends UnlistedSpecialPage {
	var $ts = null;
	const DIFFICULTY_EASY = 1;
	const DIFFICULTY_MEDIUM = 2;
	const DIFFICULTY_HARD = 3;

	function __construct() {
		parent::__construct( 'RCTestAdmin' );
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgRequest;

		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$wgOut->setPageTitle('Patrol Coach Test Scores');
		$wgOut->addModules('ext.wikihow.rcTestAdmin');
		EasyTemplate::set_path( dirname(__FILE__).'/' );
		switch ($wgRequest->getVal("a", "report")) {
			case "report":
				$this->ts = wfTimestamp(TS_MW, time() - 24 * 3600 * $wgRequest->getVal("days", 7));
				$this->printReport();
				break;
			case "detail":
				$this->printDetail();
		}
	}

	function printReport() {
		global $wgOut, $wgRequest;

		$vars['results'] = $this->getScores();
		$vars['days'] = $wgRequest->getVal("days", 7);
		$html = EasyTemplate::html('RCTestAdmin', $vars);
		$wgOut->addHTML($html);
	}

	function printDetail() {
		global $wgRequest, $wgOut;

		$dbr = wfGetDB(DB_SLAVE);
		$uid = intVal($wgRequest->getVal("uid"));
		$res = $dbr->select(array('rctest_scores', 'rctest_quizzes'), array('rs_timestamp', 'rq_difficulty', 'rs_quiz_id', 'rs_correct', 'rs_response'), array('rq_id = rs_quiz_id', 'rs_user_id' => $uid), 
			"RCTestAdmin::printDetail", array('ORDER BY' => 'rs_timestamp DESC, rq_difficulty, rs_correct'));

		$scores = array();
		while ($row = $dbr->fetchObject($res)) {
			$score = get_object_vars($row);
			$score['rs_correct'] = $score['rs_correct'] ? "Yes" : "No";
			$score['rs_response'] = RCTestGrader::getButtonText($score['rs_response']);
			$score['rs_timestamp'] = date('Y-m-d', wfTimestamp(TS_UNIX, $score['rs_timestamp']));
			$scores[] = $score;
		}

		$vars['results'] = $scores;
		$html = EasyTemplate::html('RCTestAdminDetail', $vars);
		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHTML($html);
	}

	function getScores() {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('rctest_scores', 
			array('rs_user_id', 'rs_user_name', 'count(rs_user_name) as total', 'sum(rs_correct) as correct'), 
			array("rs_timestamp >= '{$this->ts}'"), 
			'RCTestAdmin::getScores', 
			array('GROUP BY' => 'rs_user_name', 'ORDER BY' => 'total DESC, correct ASC'));

		$scores = array();
		while ($row = $dbr->fetchObject($res)) {
			$score = get_object_vars($row);
			$total = $score['total'];
			$score['incorrect'] = $this->percent($total - $score['correct'], $total);
			$score['correct'] = $this->percent($score['correct'], $total);
			$score['correct_easy'] = 'N/A'; 
			$score['correct_other'] = 'N/A'; 
			$scores[$score['rs_user_id']] = $score;
		}
		$this->addOtherScores($scores);

		return $scores;
	}

	function addOtherScores(&$scores) {
		$dbr = wfGetDB(DB_SLAVE);	
		$res = $dbr->select(array('rctest_scores','rctest_quizzes'), array('rs_user_name', 'rs_user_id', 'rq_difficulty', 'count(*) as total', 'sum(rs_correct) as correct'),
			array("rs_timestamp >= '{$this->ts}'", "rs_quiz_id = rq_id"), 
			__METHOD__, 
			array('GROUP BY' => 'rs_user_name, rq_difficulty'),
			array('ORDER BY' => 'rs_user_id, rq_difficulty'));
		
		$total = 0;
		$correct = 0;
		$uid = 0;
		while ($row = $dbr->fetchObject($res)) {
			if ($uid != $row->rs_user_id) {
				$uid = $row->rs_user_id;
				$total = $correct = 0;
			}
			switch($row->rq_difficulty) {
				case self::DIFFICULTY_EASY:
					$scores[$uid]['correct_easy'] = $this->percent($row->correct, $row->total);
					$scores[$uid]['failed_easy'] = $row->total - $row->correct;
					break;
				case self::DIFFICULTY_MEDIUM:
					$scores[$uid]['correct_other'] = $this->percent($row->correct, $row->total);
					$correct = $row->correct;
					$total = $row->total;
					break;
				case self::DIFFICULTY_HARD:
					$scores[$uid]['correct_other'] = $this->percent($correct + $row->correct, $total + $row->total);
					$correct = $total = 0;	
					break;
			}
		}
	}

	function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0) . "%";
	}

}
