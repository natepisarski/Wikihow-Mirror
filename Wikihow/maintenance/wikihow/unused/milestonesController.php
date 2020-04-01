<?php

require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/Milestones.class.php");


$action = $argv[0];

if($action == "update") {
	$yesterday = $argv[1];
	if(preg_match('@[^0-9]@', $yesterday)) {
		echo "Must include a date (yyyymmdd) as last parameter\n";
		exit;
	}
	$milestone = new Milestones();
	$milestone->updateEditMilestones($yesterday);
} elseif ($action == "email") {
	$yesterday = $argv[1];
	if(preg_match('@[^0-9]@', $yesterday)) {
		echo "Must include a date (yyyymmdd) as last parameter\n";
		exit;
	}
	$milestone = new Milestones();
	$milestone->sendMilestoneEmails($yesterday);
} else {
	$yesterdayUnix = strtotime('midnight yesterday');	
	$yesterday = substr(wfTimestamp(TS_MW, $yesterdayUnix), 0, 8);
	
	$milestone = new Milestones();
	$milestone->updateEditMilestones($yesterday);
	$milestone->sendMilestoneEmails($yesterday);
}
