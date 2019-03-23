<?php

// Get wikiphoto revision changes where a stub was removed
require_once('commandLine.inc');

// Wikiphoto Revision changes where a stub was removed
$sql = "select page_title, r.rev_id as r1_id, r.rev_parent_id as r2_id, r.rev_timestamp as ts from revision r join page on r.rev_page=page_id and page_namespace=0 where r.rev_user_text in ('Wikiphoto','Wikivid', 'Wikivisual') and page_namespace=0 and r.rev_parent_id is not NULL";
$dbr = wfGetDB(DB_REPLICA);
$res = $dbr->query($sql, __METHOD__);
$revIdLookup = array();
foreach($res as $row) {
	$revIdLookup[$row->r2_id] = $row;
	$r = Revision::newFromId($row->r1_id);
	$r2 = Revision::newFromId($row->r2_id);
	$txt = $r->getText();
}
print("Page Title\tRevision 2 id\tTimestamp\n");
foreach($revIdLookup as $l => $v) {
	$r = Revision::newFromId($v->r2_id);
	if(!$r) {
		continue;
	}
	$txt = $r->getText();
	if(preg_match("@{{stub[^}]+}}@i", $txt, $matches)) {
		print($v->page_title . "\t" . $v->r1_id . "\t" .  $v->ts . "\n");
	}
}
