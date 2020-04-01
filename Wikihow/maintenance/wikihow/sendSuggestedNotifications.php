<?php

require_once __DIR__ . '/../commandLine.inc';

$dbw = wfGetDB(DB_MASTER);

$ts = wfTimestamp(TS_MW, time() - 2*24*3600);
$res = $dbw->select(
	array('suggested_notify', 'page'),
	array('sn_page', 'page_title', 'page_namespace', 'sn_notify'), 
	array("sn_timestamp < '{$ts}'", 'page_id=sn_page'),
	__FILE__);

foreach ($res as $row) {
	$emails = array();
	$t = Title::newFromText($row->page_title);
	$title = Title::makeTitle($row->page_namespace, $row->page_title);
	$arr = explode(',', $row->sn_notify);
	foreach($arr as $e) {
		$emails[trim($e)] = $title;
	}

	if (sizeof($emails) > 0) {
		SuggestedTopicsHooks::sendRequestNotificationEmail($emails);
	}

	$dbw->delete('suggested_notify', array('sn_page' => $row->sn_page), __FILE__);
}

