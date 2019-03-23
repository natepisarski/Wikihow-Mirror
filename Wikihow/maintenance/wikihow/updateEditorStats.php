<?php

/*********************************************
 *
 * This script is used to gather editor data.
 * It aggregates three pieces of data:
 *
 * 1) Number of main namespace edits
 * 2) Number of articles created
 * 3) Number of articles nabbed
 * 4) Number of edits patrolled
 *
 * These stats are gather each week and then are compared
 * with the previous week to see if any users have crossed
 * the specific thresholds.
 *
 * There are 3 possible input values:
 * 1) populate - This asks the user for the end date to use.
 * 2) startup - This fills the table with all data from the
 *				"beginning of time" until now. Its fills the
 *				table one week at a time so as not to lock
 *				the table for too long.
 * 3) update -	This gets new data for the table. Looks for the
 *				last date used and then gathers data between then
 *				and now.
 *
 * This data is then emailed to krystle@wikihow.com
 *
 */

require_once __DIR__ . '/../commandLine.inc';


global $table;
$table = 'editor_stats';

$dbw = wfGetDB(DB_MASTER);
$dbr = wfGetDB(DB_REPLICA);

$editLevels = array(10, 25, 50, 100, 1000, 5000, 10000, 50000, 100000, 300000, 500000);
$createdLevels = array(1, 5, 10, 50, 100, 300, 500, 1000, 5000, 10000);
$patrolLevels = array(10000, 50000, 100000, 300000, 500000, 1000000);
$nabLevels = array(50000, 100000, 300000, 500000);

echo "In update_editor_stats with " . $argv[0] . "\n";

if ($argv[0] == "populate") {
	//poplulate the db with stats between the given dates
	//this is really just used for testing
	$startDate = getLastDate($dbr);
	if ($startDate == null)
		echo "Table is empty, no data exists yet. \n";
	else
		echo "Last date used is: " . date("n/j/Y", wfTimestamp(TS_UNIX, $startDate)) . "\n";
	echo "What end date would you like to use? (format yyyymmdd)\n";
	$endDate = trim(fgets(STDIN));

	if (preg_match('@[^0-9]@', $endDate) > 0)
		return;

	$endDate = $endDate . "000000";

	updateStats($dbw, $dbr, $startDate, $endDate);
	return;

}
elseif ($argv[0] == "startup") {
	//This is used to fill up the table for the first time.
	$sql = "TRUNCATE {$table}";
	$dbw->query($sql);

	echo "\n\nSTARTING STARTUP\n\n";
	echo "{$table} emptied\n\n";

	$dateYear = "2005";
	$dateMonth = "01";
	$dateDay = "01";
	$endDate = $dateYear . $dateMonth . $dateDay . "000000";
	$startDate = null;

	$now = wfTimestamp(TS_MW, time());
	//$now = "20120323000000";  //TESTING

	while ($endDate < $now) {
		updateStats($dbw, $dbr, $startDate, $endDate);

		$startDate = $endDate;
		if ($dateDay == "01") {
			$dateDay = "15";
		}
		elseif ($dateDay == "15") {
			$dateDay = "01";
			$dateMonth = intval($dateMonth) + 1;
			if ($dateMonth > 12) {
				$dateMonth = 1;
				$dateYear = intval($dateYear) + 1;
			}
			if ($dateMonth < 10)
				$dateMonth = "0" . $dateMonth;
		}

		$endDate = $dateYear . $dateMonth . $dateDay . "000000";
	}
	echo "\n\nFINISHED STARTUP\n\n";
	return;
}
elseif ($argv[0] == "update") {
	//This is used to update the table from the last date
	//data was entered until now

	echo "\n\nSTARTING UPDATE\n\n";

	$startDate = getLastDate($dbr);
	if ($argv[1] != null && preg_match('@[^0-9]@', $endDate) == 0)
		$now = $argv[1] . "000000";
	else
		$now = wfTimestamp(TS_MW, time());

	//Grab the old stats so we can compare
	$oldStats = getStats($dbr);

	//update the new stats
	updateStats($dbw, $dbr, $startDate, $now);

	//Grab the new stats
	$newStats = getStats($dbr);


	$editedStats = array();
	$createdStats = array();
	$nabStats = array();
	$patrolStats = array();

	//Compare the stats and output
	foreach ($newStats as $userId => $data) {
		if ($oldStats[$userId] == null) {
			$oldStats[$userId] = array();
			$oldStats[$userId]['edited'] = 0;
			$oldStats[$userId]['created'] = 0;
			$oldStats[$userId]['nab'] = 0;
			$oldStats[$userId]['patrol'] = 0;
		}

		//first check edited
		foreach ($editLevels as $level) {
			if (checkLevel($level, $oldStats[$userId]['edited'], $newStats[$userId]['edited']))	{
				if ($editedStats[$level] == null)
					$editedStats[$level] = array();
				$editedStats[$level][] = $userId;
			}
		}

		//now check created
		foreach ($createdLevels as $level) {
			if (checkLevel($level, $oldStats[$userId]['created'], $newStats[$userId]['created']))	{
				if ($createdStats[$level] == null)
					$createdStats[$level] = array();
				$createdStats[$level][] = $userId;
			}
		}

		//now check nab
		foreach ($nabLevels as $level) {
			if (checkLevel($level, $oldStats[$userId]['nab'], $newStats[$userId]['nab']))	{
				if ($nabStats[$level] == null)
					$nabStats[$level] = array();
				$nabStats[$level][] = $userId;
			}
		}

		//now check patrol
		foreach ($patrolLevels as $level) {
			if (checkLevel($level, $oldStats[$userId]['patrol'], $newStats[$userId]['patrol']))	{
				if ($patrolStats[$level] == null)
					$patrolStats[$level] = array();
				$patrolStats[$level][] = $userId;
			}
		}


	}

	createEmail($editedStats, $createdStats, $nabStats, $patrolStats, $newStats, $startDate, $now);

	echo "\n\nFINISHED UPDATE\n\n";
	return;
}

function createEmail(&$editedStats, &$createdStats, &$nabStats, &$patrolStats, &$stats, $startDate, $endDate) {
	$users = array();

	$email = "";

	$email = "Editor data from " . date("n/j/Y", wfTimestamp(TS_UNIX, $startDate)) . " to " . date("n/j/Y", wfTimestamp(TS_UNIX, $endDate)) . "<br /><br />";

	ksort($editedStats);
	foreach ($editedStats as $level => $levelData) {
		$email .= "Edited - " . $level;
		$email .= "<ol>";
		foreach ($levelData as $userId) {
			$user = getUser($users, $userId);
			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $user->getName() );
			$email .= "<li>";
			$email .= "<a href='" . $user->getUserPage()->getFullURL() . "'>" . $user->getName() . "</a>, ";
			$email .= $stats[$userId]['edited'] . " edits, ";
			$email .= "<a href='" . $user->getTalkPage()->getFullURL() . "'>Talk</a>, ";
			$email .= "<a href='" . $contribsPage->getFullURL() . "'>contribs</a></li>\n";
		}
		$email .= "</ol>";
	}

	ksort($createdStats);
	foreach ($createdStats as $level => $levelData) {
		$email .= "Articles Created - " . $level;
		$email .= "<ol>";
		foreach ($levelData as $userId) {
			$user = getUser($users, $userId);
			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $user->getName() );
			$email .= "<li>";
			$email .= "<a href='" . $user->getUserPage()->getFullURL() . "'>" . $user->getName() . "</a>, ";
			$email .= $stats[$userId]['created'] . " articles created, ";
			$email .= "<a href='" . $user->getTalkPage()->getFullURL() . "'>Talk</a>, ";
			$email .= "<a href='" . $contribsPage->getFullURL() . "'>contribs</a></li>\n";
		}
		$email .= "</ol>";
	}

	ksort($nabStats);
	foreach ($nabStats as $level => $levelData) {
		$email .= "Articles NABed - " . $level;
		$email .= "<ol>";
		foreach ($levelData as $userId) {
			$user = getUser($users, $userId);
			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $user->getName() );
			$email .= "<li>";
			$email .= "<a href='" . $user->getUserPage()->getFullURL() . "'>" . $user->getName() . "</a>, ";
			$email .= $stats[$userId]['nab'] . " articles nabed, ";
			$email .= "<a href='" . $user->getTalkPage()->getFullURL() . "'>Talk</a>, ";
			$email .= "<a href='" . $contribsPage->getFullURL() . "'>contribs</a></li>\n";
		}
		$email .= "</ol>";
	}

	ksort($patrolStats);
	foreach ($patrolStats as $level => $levelData) {
		$email .= "Articles Patrolled - " . $level;
		$email .= "<ol>";
		foreach ($levelData as $userId) {
			$user = getUser($users, $userId);
			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $user->getName() );
			$email .= "<li>";
			$email .= "<a href='" . $user->getUserPage()->getFullURL() . "'>" . $user->getName() . "</a>, ";
			$email .= $stats[$userId]['patrol'] . " articles patrolled, ";
			$email .= "<a href='" . $user->getTalkPage()->getFullURL() . "'>Talk</a>, ";
			$email .= "<a href='" . $contribsPage->getFullURL() . "'>contribs</a></li>\n";
		}
		$email .= "</ol>";
	}

	$to = new MailAddress("reports@wikihow.com");
	$from = new MailAddress("reports@wikihow.com");
	$subject = "Editor Stats";
	$content_type = "text/html; charset=UTF-8";

	UserMailer::send($to, $from, $subject, $email, null, $content_type);

}

function getUser(&$users, $userId) {
	if ($users[$userId] == null) {
		$user = User::newFromId($userId);
		$users[$userId] = $user;
	}

	return $users[$userId];
}

function getStats(&$dbr) {
	global $table;

	$res = $dbr->select($table, '*', '', __METHOD__);
	$stats = array();
	while ($row = $dbr->fetchObject($res) ) {
		$stats[$row->es_user] = array();
		$stats[$row->es_user]['edited'] = $row->es_edits;
		$stats[$row->es_user]['created'] = $row->es_created;
		$stats[$row->es_user]['nab'] = $row->es_nab;
		$stats[$row->es_user]['patrol'] = $row->es_patrol;
	}

	return $stats;
}

function checkLevel($level, $oldLevel, $newLevel) {
	if ($oldLevel < $level && $newLevel >= $level) {
		return true;
	}
	return false;
}

function updateStats(&$dbw, &$dbr, $startDate = null, $endDate = null) {
	global $table;

	$stats = array();

	if ($endDate == null){
		$start = time();
		$now = wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 7); // 7 days
	}
	else {
		$now = $endDate;
	}

	//get edits
	$sql = "SELECT rev_user from revision, page WHERE page_id = rev_page AND page_namespace = 0";
	if ($startDate != null)
		$sql = $sql . " AND rev_timestamp > '{$startDate}'";
	if ($endDate != null)
		$sql = $sql . " AND rev_timestamp <= '{$endDate}'";

	$lastTime = microtime(true);

	echo "Running query at " . $lastTime . "\n";
	echo $sql . "\n";

	$res = $dbr->query($sql, __METHOD__);

	$newTime = microtime(true);

	echo "Query done at " . $newTime . " for a total time of " . ($newTime - $lastTime) . "\n";

	while ($row = $dbr->fetchObject($res)) {
		addData($stats, $row->rev_user, 'edits');
	}

	echo "Done parsing revision data. There are " . count($stats) . " number of rows to update.\n";

	//get articles created
	$sql = "SELECT fe_user from firstedit WHERE ";
	if ($startDate != null)
		$sql = $sql . " fe_timestamp > '{$startDate}'";
	if ($startDate != null && $endDate != null)
		$sql = $sql . " AND ";
	if ($endDate != null)
		$sql = $sql . " fe_timestamp <= '{$endDate}'";

	$lastTime = microtime(true);
	echo "Running query at " . $lastTime . "\n";
	echo $sql . "\n";

	$res = $dbr->query($sql, __METHOD__);

	$newTime = microtime(true);
	echo "Query done at " . $newTime . " for a total time of " . ($newTime - $lastTime) . "\n";

	while ($row = $dbr->fetchObject($res)) {
		addData($stats, $row->fe_user, 'created');
	}

	echo "Done getting first edit data. Total number of rows to update: " . count($stats). "\n";

	//get NAB
	$sql = "SELECT log_user from logging WHERE log_type='nap' ";
	if ($startDate != null)
		$sql = $sql . " AND log_timestamp > '{$startDate}'";
	if ($endDate != null)
		$sql = $sql . " AND log_timestamp <= '{$endDate}'";

	$lastTime = microtime(true);
	echo "Running query at " . $lastTime . "\n";
	echo $sql . "\n";

	$res = $dbr->query($sql, __METHOD__);

	$newTime = microtime(true);
	echo "Query done at " . $newTime . " for a total time of " . ($newTime - $lastTime) . "\n";

	while ($row = $dbr->fetchObject($res)) {
		addData($stats, $row->log_user, 'nab');
	}

	echo "Done getting nab data. Total number of rows to update: " . count($stats). "\n";

	//get Patrol
	$sql = "SELECT log_user from logging WHERE log_type='patrol' ";
	if ($startDate != null)
		$sql = $sql . " AND log_timestamp > '{$startDate}'";
	if ($endDate != null)
		$sql = $sql . " AND log_timestamp <= '{$endDate}'";

	$lastTime = microtime(true);
	echo "Running query at " . $lastTime . "\n";
	echo $sql . "\n";

	$res = $dbr->query($sql, __METHOD__);

	$newTime = microtime(true);
	echo "Query done at " . $newTime . " for a total time of " . ($newTime - $lastTime) . "\n";

	while ($row = $dbr->fetchObject($res)) {
		addData($stats, $row->log_user, 'patrol');
	}

	echo "Done getting patrol data. Total number of rows to update: " . count($stats). "\n";

	//now put all that data back into the editor_stats table
	$lastTime = microtime(true);
	echo "Starting update table at " . $lastTime . "\n";

	foreach ($stats as $userid => $data) {
		$sql = "INSERT INTO {$table} (es_user, es_edits, es_created, es_nab, es_patrol, es_timestamp) VALUES (" . $userid . ", {$data['edits']}, {$data['created']}, {$data['nab']}, {$data['patrol']}, '{$now}') ON DUPLICATE KEY UPDATE es_edits = es_edits + {$data['edits']}, es_created = es_created + {$data['created']}, es_nab = es_nab + {$data['nab']}, es_patrol = es_patrol + {$data['patrol']}, es_timestamp = '{$now}'";
		$dbw->query($sql, __METHOD__);
	}

	$newTime = microtime(true);

	echo "Finished updateStats at " . microtime(true) . " with update taking " . ($newTime - $lastTime) . "\n";
}

function addData(&$stats, $userId, $field) {
	if ($userId > 0) {
		if ($stats[$userId] == null){
			$stats[$userId] = array();
			$stats[$userId]['edits'] = 0;
			$stats[$userId]['created'] = 0;
			$stats[$userId]['nab'] = 0;
			$stats[$userId]['patrol'] = 0;
		}

		$stats[$userId][$field]++;
	}
}

function getLastDate(&$dbr) {
	global $table;

	$date = $dbr->selectField($table, 'es_timestamp', '' , __METHOD__, array("ORDER BY" => "es_timestamp DESC", "LIMIT" => 1));

	if ($date === false)
		return null;

	return $date;
}

/***

CREATE TABLE `wikidb_112`.`editor_stats` (
`es_user` int(10) NOT NULL,
`es_edits` int(11) NOT NULL default '0',
`es_created` int(11) NOT NULL default '0',
`es_nab` int(11) NOT NULL default '0',
`es_patrol` int(11) NOT NULL default '0',
`es_timestamp` varchar(14) NOT NULL,
UNIQUE KEY `es_user` (`es_user`),
KEY `es_timestamp` (`es_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

***/
