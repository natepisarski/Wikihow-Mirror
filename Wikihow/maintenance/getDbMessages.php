<?php
// Get all the messages in the database
require_once('commandLine.inc');

$dbr = wfGetDB(DB_SLAVE);

$sql = "select page_title from page where page_namespace=" . NS_MEDIAWIKI;
$res = $dbr->query($sql);
$msgs = array();
foreach($res as $row) {
	$msgs[] = $row->page_title;
}
foreach($msgs as $msg) {
	$m = wfMsg($msg);
	print( $dbr->addQuotes($msg) . ',' . $dbr->addQuotes($m) . "\n");
}
