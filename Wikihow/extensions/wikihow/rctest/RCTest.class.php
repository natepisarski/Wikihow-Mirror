<?php

/*
* Class that is used to inject test patrols into the RC Patrol tool.
*/
class RCTest {

	var $adjustedPatrolCount = null;
	var $basePatrolCount = null;
	var $userInfo = null;
	var $testInfo = null;
	var $moreTests = null;

	function __construct() {
	}


	// Get the number of patrols total - number of patrols a user had when they first start using the rc test tool
	public function getAdjustedPatrolCount() {
		if (is_null($this->adjustedPatrolCount) || $this->adjustedPatrolCount < 0) {
			$this->adjustedPatrolCount = $this->getTotalPatrols() - $this->getBasePatrolCount();
		}
		return $this->adjustedPatrolCount;
	}

	public function getTotalPatrols() {
		$dbr = wfGetDB(DB_REPLICA);
		$total = $dbr->selectField('logging', 'count(*)', RCPatrolStandingsIndividual::getOpts());
		return $total;
	}

	// Gets the number of patrols a user has when they first start using the rc test tool
	private function getBasePatrolCount() {
		if (is_null($this->userInfo)) {
			if ($this->userExists()) {
				$this->fetchUserInfo();
			}
			else {
				return $this->getTotalPatrols();
			}

		}
		$userInfo = $this->userInfo;
		return $userInfo['ru_base_patrol_count'];
	}

	public function getUserInfo() {
		if (is_null($this->userInfo)) {
			$this->fetchUserInfo();
		}
		return $this->userInfo;
	}

	private function fetchUserInfo() {
		global $wgUser;

		if (!$this->userExists()) {
			$this->addUser($wgUser);
		}

		// Use the master so we can fetch the user if it was just created
		$dbw = wfGetDB(DB_MASTER);
		$row = $dbw->selectRow('rctest_users', array('*'), array('ru_user_id' => $wgUser->getId()));
		if (is_object($row)) {
			$this->userInfo = get_object_vars($row);
		}
		else {
			throw new MWException("Couldn't retrieve test user");
		}
	}

	private function userExists() {
		global $wgUser;
		$dbr = wfGetDB(DB_REPLICA);
		$exists = $dbr->selectField('rctest_users', array('count(*) as C'), array('ru_user_id' => $wgUser->getId()));
		return $exists > 0;
	}

	private function addUser(&$user) {
		$dbw = wfGetDB(DB_MASTER);
		$basePatrolCount = $this->getBasePatrolCount();
		$dbw->insert('rctest_users', array('ru_user_id' => $user->getId(), 'ru_user_name' => $user->getName(), 'ru_base_patrol_count' => $basePatrolCount, 'ru_next_test_patrol_count' => 5));
	}

	public function getResultParams() {
		$testInfo = $this->getTestInfo();

		$r = Revision::newFromId($testInfo['rq_rev_new']);
		if (!$r) {
			throw new Exception("Unable to create revision from testInfo['rq_rev_new'] = {$testInfo['rq_rev_new']}");
		}
		$params['title'] = $r->getTitle();
		$params['old'] = $testInfo['rq_rev_old'];
		$params['new'] = $testInfo['rq_rev_new'];
		return $params;
	}

	public function getTestInfo($testId = null) {
		if (is_null($this->testInfo)) {
			$this->fetchTestInfo($testId);
		}
		return $this->testInfo;
	}

	private function fetchTestInfo($testId = null ) {
		global $wgRequest;

		//  If a specific test id is specified, just fetch that
		if ($testId) {
			$this->fetchSpecificTestInfo($testId);
			return;
		}

		// Make sure it's test time
		if (!$this->isTestTime()) {
			throw new Exception("Can't fetch test info if it's not a valid test patrol count");
		}

		$userInfo = $this->getUserInfo();

		// If debugging, use the debug fetcher
		if ($wgRequest->getVal('rct_mode')) {
			$this->fetchDebugTestInfo();
			return;
		}

		// If guided tour, use the guided tour fetcher
		if ($wgRequest->getVal('gt_mode')) {
			$this->fetchGuidedTourTestInfo();
			return;
		}

		$difficulty = (int) $this->getTestDifficulty();

		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT * FROM rctest_quizzes WHERE rq_deleted = 0 AND rq_difficulty <= $difficulty ";
		// Exclude any quizzes already taken
		if (!empty($userInfo['ru_quiz_ids'])) {
			$sql .= " AND rq_id NOT IN (" . $userInfo['ru_quiz_ids'] . ") ";
		}
		$sql .= " ORDER BY rq_difficulty LIMIT 1";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);
		if ($row) {
			$this->testInfo = get_object_vars($row);
			$this->setTestActive(true);
		} else {
			//do we need to clear tests out?
			$takenTestIds = explode(",", $userInfo['ru_quiz_ids']);

			$sql = "SELECT COUNT(*) AS C FROM rctest_quizzes WHERE rq_difficulty = 1";
			$res = $dbr->query($sql);
			$row = $dbr->fetchObject($res);

			if (count($takenTestIds) >= $row->C) {
				//reached limit; reset scores
				if ($this->resetTestScores()) {
					//start again...
					$this->fetchTestInfo();
					return;
				}
			}
			throw new Exception("Couldn't fetch Test: $sql");
		}
	}

	private function getTestDifficulty() {
		$nextTestPatrolCount = $this->getNextTestPatrolCount();

		if ($nextTestPatrolCount >= 1000) {
			$difficulty = 3; //hard
		}
		elseif ($nextTestPatrolCount >= 500) {
			$difficulty = 2; //medium
		}
		else {
			$difficulty = 1; //easy
		}
		return $difficulty;
	}

	private function setNextTestPatrolCount() {
		$userInfo = $this->getUserInfo();
		$nextPatrolCount = $userInfo['ru_next_test_patrol_count'];
		// Increment next patrol count by 500 after the 1000 mark
		if ($nextPatrolCount >= 1000) {
			$newNextPatrolCount = $nextPatrolCount - ($nextPatrolCount % 500) + 500;
		}
		else {
			//MORE TESTS!!!
			//$testPatrolCounts = array (5, 25, 50, 100, 150, 200, 250, 500, 750, 1000);
			$testPatrolCounts = array (5, 10, 15, 25, 40, 50, 75, 100, 125, 150, 175, 200, 250, 350, 500, 750, 1000);
			for ($i = 0; $i < sizeof($testPatrolCounts); $i++) {
				if ($nextPatrolCount >= $testPatrolCounts[$i] && $nextPatrolCount < $testPatrolCounts[$i + 1]) {
					$newNextPatrolCount = $testPatrolCounts[$i + 1];
					break;
				}
			}
		}
		if ($nextPatrolCount == $newNextPatrolCount) {
			throw new Exception('rctest next test patrol count not updating properly');
		}
		if ($newNextPatrolCount == 0) {
			//something went wrong; let's just hard-code it to 5
			$newNextPatrolCount = 5;
		}
		$dbw = wfGetDB(DB_MASTER);
		$userInfo['ru_next_test_patrol_count'] = $newNextPatrolCount;
		$dbw->update('rctest_users', array('ru_next_test_patrol_count' => $newNextPatrolCount), array('ru_user_id' => $userInfo['ru_user_id']));
	}

	private function getNextTestPatrolCount() {
		$userInfo = $this->getUserInfo();
		return $userInfo['ru_next_test_patrol_count'];
	}

	/*
	* Sets a cookie that denotes whether a test is currently active/administered.
	*/
	public function setTestActive($active) {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		$expiration = $active ? 0 : time() - (3600 * 24);
		$value = $active ? '1' : '';
		setcookie( $wgCookiePrefix.'_rct_a', $value, $expiration, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
	}

	/*
	* Checks for the existence of a cookie which denotes that there's an active test
	*/
	private function isTestActive() {
		global $wgCookiePrefix;
		$active  = $_COOKIE[$wgCookiePrefix . '_rct_a'];
		return !is_null($active);

	}

	public function debugGradeTest($testId, $response) {
		$testInfo = $this->getTestInfo($testId);
		$correctIds = $testInfo['rq_ideal_responses'] . ',' . $testInfo['rq_acceptable_responses'];
		$correctIds = explode(",", $correctIds);

		// See if the response was in the ideal or acceptable responses.  Skip and a link press are always acceptable responses.
		$correct = false !== array_search($response, $correctIds) || $response == RCTestGrader::RESP_SKIP || $response == RCTestGrader::RESP_LINK;

		// Turn off the active flag for the test
		$this->setTestActive(false);

		$result['ideal_responses'] = $testInfo['rq_ideal_responses'];
		$result['correct'] = intVal($correct);
		$result['exp'] = $testInfo['rq_explanation'];
		$result['coach'] = $testInfo['rq_coaching'];
		return $result;
	}

	public function gradeTest($testId, $response) {
		global $wgRequest;

		if ($wgRequest->getVal('rct_mode') || $wgRequest->getVal('gt_mode')) {
			return $this->debugGradeTest($testId, $response);
		}

		$testInfo = $this->getTestInfo($testId);
		$correctIds = $testInfo['rq_ideal_responses'] . ',' . $testInfo['rq_acceptable_responses'];
		$correctIds = explode(",", $correctIds);

		// See if the response was in the ideal or acceptable responses.  Skip and a link press are always acceptable responses.
		$correct = false !== array_search($response, $correctIds) || $response == RCTestGrader::RESP_SKIP || $response == RCTestGrader::RESP_LINK;

		// Don't record a score for this test if the user skipped the patrol
		// ***The if statement below doesn't work as intended, but it's become a feature (according to our CMs)
		// ***Keeping it in although it doesn't really do much [sc - 2/5/2014]
		if ($response != RCTestGrader::RESP_SKIP || $response != RCTestGrader::RESP_LINK) {
			$this->recordScore($correct, $response);

			if (empty($correct) && $testInfo['rq_difficulty'] == 1) {
				$this->unpatrolbatch();
			}
			else {
				// Update the patrol count necessary for the next test to be displayed
				$this->setNextTestPatrolCount();
			}
		}

		// Record that the user has taken the test
		$this->setTaken($testInfo['rq_id']);

		// Turn off the active flag for the test
		$this->setTestActive(false);

		$result['correct'] = intVal($correct);
		$result['ideal_responses'] = $testInfo['rq_ideal_responses'];
		$result['exp'] = $testInfo['rq_explanation'];
		$result['coach'] = $testInfo['rq_coaching'];

		return $result;
	}

	private function recordScore($correct, $response) {
		$ui = $this->getUserInfo();
		$ti = $this->getTestInfo();
		$correct = intVal($correct);
		$timestamp = wfTimestampNow();
		$dbw = wfGetDB(DB_MASTER);
		$response = $dbw->strencode($response);

		$userId = (int) $ui['ru_user_id'];
		$userName = $dbw->strencode($ui['ru_user_name']);
		$rqId = (int) $ti['rq_id'];

		$sql = "
			INSERT IGNORE INTO rctest_scores
				(rs_user_id, rs_user_name, rs_quiz_id, rs_correct, rs_response, rs_timestamp)
			VALUES (
				{$userId},
				'{$userName}',
				{$rqId},
				$correct,
				$response,
				'$timestamp'
			)";
		$dbw->query($sql);
	}

	//failed test; unpatrol their last patrols
	private function unpatrolbatch() {
		global $wgUser, $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;

		$start = $this->getGoodStartDate();
		$end = wfTimestampNow(TS_MW);
		$max_to_unpatrol = 250;

		$unpatrolled = Unpatrol::doTheUnpatrol($wgUser, $start, $end, $max_to_unpatrol);

		if (!empty($unpatrolled)) {
			//send talk page note
			$from_user = User::newFromName('Patrol-Coach');
			$comment = "Oops! It looks like you accidentally approved a bad edit in while patrolling
						recent changes just now. That's okay though; you're still learning. I undid
						a batch of your recent patrols, to play it safe and have those edits reviewed
						again. I just want to make sure no bad edits were accepted while you're still
						learning your way around RC patrol.\r\n
						If you haven't already, read our article on [[Patrol Recent Changes on wikiHow|How to Patrol Recent Changes]] and
						give it another try! I'm happy to answer any questions you might have about
						patrolling. And remember, if you're not sure what to do, just press the \"skip\"
						button and you'll do fine :)\r\n
						The Patrol Coach";
			Misc::adminPostTalkMessage($wgUser,$from_user,$comment);
		}
	}

	private function getGoodStartDate() {
		global $wgUser;

		$dbr = wfGetDB(DB_REPLICA);

		//get the date of the last successful easy test
		$last_easy_test = $dbr->selectField(
			'rctest_scores',
			'rs_timestamp',
			array('rs_user_id' => $wgUser->getId(), 'rs_correct' => 1),
			__METHOD__,
			array('ORDER BY' => 'rs_timestamp DESC'));

		//only go one week back max
		$max_back = wfTimestamp(TS_MW, strtotime('-1 week'));

		if (!empty($last_easy_test) && $last_easy_test > $max_back) {
			return $last_easy_test;
		}
		else {
			return $max_back ;
		}
	}

	private function setTaken($testId) {
		$userInfo = $this->getUserInfo();
		$takenTestIds = array();
		if ($userInfo['ru_quiz_ids']) {
			$takenTestIds = explode(",", $userInfo['ru_quiz_ids']);
		}
		if (false === array_search($testId, $takenTestIds)) {
			$takenTestIds[] = $testId;
		}
		$takenTestIds = implode(",", $takenTestIds);
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('rctest_users', array('ru_quiz_ids' => $takenTestIds), array('ru_user_id' => $userInfo['ru_user_id']));
		$this->userInfo['ru_quiz_ids'] = $takenTestIds;
	}

	/*
	* Loads a specific test given a test id
	*/
	private function fetchSpecificTestInfo($testId) {
		$dbr = wfGetDB(DB_REPLICA);
		$testId = $dbr->strencode($testId);
		$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $testId));
		$this->testInfo = get_object_vars($row);
	}

	private function fetchGuidedTourTestInfo() {
		global $wgRequest;

		$dbr = wfGetDB(DB_REPLICA);
		$id = $wgRequest->getVal('rct_id');
		$this->fetchSpecificTestInfo($id);
	}

	private function fetchDebugTestInfo() {
		global $wgRequest;

		$dbr = wfGetDB(DB_REPLICA);
		$mode = $wgRequest->getVal('rct_mode');
		$id = $wgRequest->getVal('rct_id');
		// Dev server doesn't have rc test revisions in database yet.  Fake it
		if ($id) {
			$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $id));
			$this->testInfo = get_object_vars($row);
		}
		else {
			$numTests = 55;
			$id = 1 + (rand(0,$numTests) % $numTests);
			$row = $dbr->selectRow("rctest_quizzes", array("*"), array("rq_id" => $id, "rq_deleted" => 0));
			if ($row) {
				$this->testInfo = get_object_vars($row);
			}
			else {
				$this->fetchDebugTestInfo();
			}
		}
	}


	/*
	* Returns true if browser is anything other than IE6 or IE7, false otherwise.
	*/
	private function isCompatibleBrowser() {
		return !preg_match('@MSIE (6|7)@',$_SERVER['HTTP_USER_AGENT']);
	}

	/*
	* Returns true if a test should be displayed, false otherwise
	*/
	public function isTestTime() {
		global $wgRequest, $wgReadOnly;

		// RCPatrol Test doesn't work for IE 7 and IE 6 beecause of use of negative margins in the css.
		// Don't allow RC Test to show for users of these browsers.
		if (!$this->isCompatibleBrowser()) {
			return false;
		}

		// Don't run this if site is in read-only mode
		if ($wgReadOnly) {
			return false;
		}

		$userInfo = $this->getUserInfo();

		// It's always test time when we're debugging!
		if ($wgRequest->getVal('rct_mode')) {
			return $this->isDebugTestTime();
		}

		// Guided Tour Mode
		if ($wgRequest->getVal('gt_mode')) {
			return $this->isGuidedTourTestTime();
		}


		// Return false if we are just marking an item as patrolled/skip.
		// We do this because the return data from this is ignored by the client
		// and overwritten by an immmediately following grabnext. See rcpatrol.js for
		// more details
		if (!$wgRequest->getVal('grabnext')) {
			return false;
		}

		// Check to see if the test is currently active
		if ($this->isTestActive()) {
			return false;
		}

		// Return false if the user has already taken all the tests
		if (!$this->isMoreTests()) {
			return false;
		}

		// If we're past the patrol count for the next test, it's test time
		// (offset by one because we have to cue one prior)
		return $this->getAdjustedPatrolCount() >= ($this->getNextTestPatrolCount() - 1) ? true : false;
	}

	private function isDebugTestTime() {
		global $wgRequest;
		return $wgRequest->getVal('rct_mode') ? true : time() % 3 == 0;
	}


	private function isGuidedTourTestTime() {
		return true;
	}

	private function isMoreTests() {
		if (is_null($this->moreTests)) {
			$dbr = wfGetDB(DB_REPLICA);
			$difficulty = (int) $this->getTestDifficulty();
			$sql = "SELECT count(*) as C FROM rctest_quizzes WHERE rq_deleted = 0 AND rq_difficulty <= $difficulty";
			// Exclude any quizzes already taken
			$userInfo = $this->getUserInfo();
			if (sizeof($userInfo['ru_quiz_ids'])) {
				$sql .= " AND rq_id NOT IN (" . $userInfo['ru_quiz_ids'] . ") ";
			}
			$res = $dbr->query($sql);
			$row = $dbr->fetchObject($res);
			$this->moreTests = $row->C > 0;
		}
		return $this->moreTests;
	}

	public function getTestHtml() {
		// Only add the html if RC Patrol is supposed to show a test
		if (!$this->isTestTime()) {
			return;
		}

		$testInfo = $this->getTestInfo();
		$html = "<div id='rct_data'>" . $testInfo['rq_id'] . "</div>";
		$html .= HtmlSnips::makeUrlTag('/extensions/wikihow/rctest/rctest.js');
		$html .= HtmlSnips::makeUrlTag('/extensions/wikihow/rctest/rctest.css');
		return $html;
	}

	/*
	* Returns true if rctest preference is set for user, false otherwise.
	* If preference hasn't been set, defaults preference to on
	*/
	static function isEnabled($userId = null) {
		global $wgUser;

		if (is_null($userId)) {
			$userId = $wgUser->getId();
		}

		if ($userId > 0) {
			$u = User::newFromId($userId);
			$option = $u->getOption('rctest');
			// If the option hasn't been initialized yet, set it to on (0) by default
			if ($option === '') {
				$u->setOption('rctest', 0);
				$u->saveSettings();
				$option = 0;
			}
		}
		else {
			// This preference doesn't apply to anons
			$option = 1;
		}
		return !intVal($option);
	}

	/*
	 * So it comes to this...
	 * All tests used up.  Gotta reset so we don't give an error message.
	 * - returns boolean
	 */
	private function resetTestScores() {
		$userInfo = $this->getUserInfo();

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->update('rctest_users', array('ru_quiz_ids' => ''), array('ru_user_id' => $userInfo['ru_user_id']));

		if ($res) $this->userInfo['ru_quiz_ids'] = '';

		return $res;
	}
}
