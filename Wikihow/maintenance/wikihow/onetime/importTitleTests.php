<?php
//
// A script to import the titles of a bunch of articles and to which test
// cohort they belong.
//

print 'TitleTest changed to CustomTitle. This script needs to be changed if we want to run it again.';
return;

require_once('commandLine.inc');

global $IP;
require_once("$IP/extensions/wikihow/CustomTitle/CustomTitle.class.php");

$filename = '/home/reuben/categories_expt_groups.csv';
$fp = fopen($filename, 'r');
if (!$fp) {
	die("error: cannot open $filename for reading\n");
}

$dbw = wfGetDB(DB_MASTER);

$i = 1;
while (($data = fgetcsv($fp)) !== false) {
	if ($i++ == 1) continue; // skip first line
	$pageid = intval($data[0]);
	$title = Title::newFromID($pageid);
	if (!$title) {
		print "bad title: $pageid\n";
		continue;
	}
	$pageid = $title->getArticleId();
	if (!$pageid) {
		print "not found: $pageid\n";
		continue;
	}
	$experiment = intval($data[2]);
	#$test = intval($data[1]) + 10;
	//print $title->getText()."\n";
	if ($experiment > 0) {
		CustomTitle::dbAddRecord($dbw, $title, $experiment);
		//print $title->getText() . "\n";
	}
}

