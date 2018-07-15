<?php

require_once __DIR__ . '/../commandLine.inc';
global $IP;
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

$dbr = wfGetDB(DB_SLAVE);

$res = $dbr->select('quizzes','quiz_name', array('quiz_active' => true), __FILE__, array('GROUP BY' => 'quiz_name'));

$lines = array();
foreach($res as $row) {
	$url = 'http://www.wikihow.com/Quiz/'.$row->quiz_name;
	$lines[] = $url;
}

foreach ($lines as $line) {
	print "$line\n";
}
