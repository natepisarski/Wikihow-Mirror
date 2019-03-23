<?php

require_once(__DIR__ . '/../../commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

$stats = TitusConfig::getAllStats();
$titus = new TitusDB();
print "Getting pages to calc for stats\n";
$ids = $titus->getPagesToCalcByStat($stats, wfTimestampNow());
print "Got pages to calc for various stats\n";
$idsToCalc = array();

$dbr = wfGetDB(DB_REPLICA);
print "Getting random ids to calc\n";
$sql = "select page_id from page where page_namespace=0 and page_is_redirect=0 order by rand() limit 5";
$res = $dbr->query($sql, __METHOD__);
foreach ( $res as $row ) {
	$idsToCalc[] = $row->page_id;
}

$sql = "select de_page_id from daily_edits where de_edit_type=0 order by rand() limit 5";
$res = $dbr->query($sql, __METHOD__);
foreach ( $res as $row ) {
	$idsToCalc[] = $row->de_page_id;
}

$sql = "select de_page_id from daily_edits where de_edit_type=1 order by rand() limit 5";
$res = $dbr->query($sql, __METHOD__);
foreach ( $res as $row ) {
	$idsToCalc[] = $row->de_page_id;
}

$sql = "select de_page_id from daily_edits where de_edit_type=2 order by rand() limit 5";
$res = $dbr->query($sql, __METHOD__);
foreach ( $res as $row ) {
	$idsToCalc[] = $row->de_page_id;
}

if ( $ids['ids'] ) {
	$newIds = $ids['ids'];
	if ( sizeof($ids['ids']) > 5 ) {
		$newIds = array_rand($newIds, 5);
	}
	$idsToCalc = array_merge($idsToCalc, $newIds);
}
print "Got ids to calc.\n";

$sql = "select * from page where page_id in (" . implode(',', $idsToCalc) . ")";

$dbr = wfGetDB(DB_REPLICA);
$res = $dbr->query($sql, __METHOD__);
print "Calculating for all stats\n";
print_r($stat);

foreach ( $res as $row ) {
	print_r($titus->calcPageStats($stats, $row));
}
print "Calculated page stats for all ids\n";
