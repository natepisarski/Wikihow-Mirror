<?php

class RCTestAdmin extends UnlistedSpecialPage {

	var $ts = null;

	const DIFFICULTY_EASY = 1;
	const DIFFICULTY_MEDIUM = 2;
	const DIFFICULTY_HARD = 3;

	public function __construct() {
		parent::__construct( 'RCTestAdmin' );
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

		$out->setPageTitle('Patrol Coach Test Scores');
		$out->addModules('ext.wikihow.rcTestAdmin');
		EasyTemplate::set_path( __DIR__.'/' );
		switch ($req->getVal("a", "report")) {
			case "report":
				$this->ts = wfTimestamp(TS_MW, time() - 24 * 3600 * $req->getVal("days", 7));
				$this->printReport();
				break;
			case "detail":
				$this->printDetail();
		}
	}

	private function printReport() {
		$vars['results'] = $this->getScores();
		$vars['days'] = $this->getRequest()->getVal("days", 7);
		$html = EasyTemplate::html('RCTestAdmin.tmpl.php', $vars);
		$this->getOutput()->addHTML($html);
	}

	private function printDetail() {
		$dbr = wfGetDB(DB_REPLICA);
		$uid = $this->getRequest()->getInt("uid");
		$res = $dbr->select(array('rctest_scores', 'rctest_quizzes'),
			array('rs_timestamp', 'rq_difficulty', 'rs_quiz_id', 'rs_correct', 'rs_response'),
			array('rq_id = rs_quiz_id', 'rs_user_id' => $uid),
			__METHOD__,
			array('ORDER BY' => 'rs_timestamp DESC, rq_difficulty, rs_correct'));

		$scores = array();
		foreach ($res as $row) {
			$score = get_object_vars($row);
			$score['rs_correct'] = $score['rs_correct'] ? "Yes" : "No";
			$score['rs_response'] = RCTestGrader::getButtonText($score['rs_response']);
			$score['rs_timestamp'] = date('Y-m-d', wfTimestamp(TS_UNIX, $score['rs_timestamp']));
			$scores[] = $score;
		}

		$vars['results'] = $scores;
		$html = EasyTemplate::html('RCTestAdminDetail.tmpl.php', $vars);
		$this->getOutput()->setArticleBodyOnly(true);
		$this->getOutput()->addHTML($html);
	}

	private function getScores() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('rctest_scores',
			array('rs_user_id', 'rs_user_name', 'count(rs_user_name) as total', 'sum(rs_correct) as correct'),
			array("rs_timestamp >= '{$this->ts}'"),
			__METHOD__,
			array('GROUP BY' => 'rs_user_name', 'ORDER BY' => 'total DESC, correct ASC'));

		$scores = array();
		foreach ($res as $row) {
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

	private function addOtherScores(&$scores) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(array('rctest_scores','rctest_quizzes'), array('rs_user_name', 'rs_user_id', 'rq_difficulty', 'count(*) as total', 'sum(rs_correct) as correct'),
			array("rs_timestamp >= '{$this->ts}'", "rs_quiz_id = rq_id"),
			__METHOD__,
			array('GROUP BY' => 'rs_user_name, rq_difficulty'),
			array('ORDER BY' => 'rs_user_id, rq_difficulty'));

		$total = 0;
		$correct = 0;
		$uid = 0;
		foreach ($res as $row) {
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

	private function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0) . "%";
	}

}
