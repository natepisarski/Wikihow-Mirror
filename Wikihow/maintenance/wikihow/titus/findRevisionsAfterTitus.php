<?php

require_once __DIR__ . "/../../commandLine.inc";
require_once "$IP/extensions/wikihow/titus/Titus.class.php";
require_once "$IP/extensions/wikihow/DatabaseHelper.class.php";

global $wgLanguageCode;
$sql = "select rev_page, max(rev_timestamp) as last_revision, min(rev_timestamp) as first_revision_after, ti_last_patrolled_edit_timestamp as last_patrolled from titusdb.titus_intl ti join " . Misc::getLangDB($wgLanguageCode) .  ".revision on ti_page_id = rev_page WHERE rev_timestamp > ti_last_patrolled_edit_timestamp  AND ti_language_code='$wgLanguageCode' AND not (rev_comment like '%move%') group by rev_page";

$dbr = DatabaseBase::factory('mysql');
$dbr->open(TitusDB::getDBHost(), WH_DATABASE_MAINTENANCE_USER, WH_DATABASE_MAINTENANCE_PASSWORD, TitusDB::getDBName());

$res = $dbr->query($sql);
$rows = array();
foreach($res as $row) {
	$rows[] = $row;
}
if(sizeof($rows) > 0) {
	$now = wfTimestampNow();
	$msg = "<table><thead><tr><td>Title</td><td>Page Id</td><td>Latest Revision</td><td>First Revision After Patrolled</td><td>Last Patrolled Revision</td></tr></thead>\n<tbody>\n";
	foreach($rows as $row) {
		$lts = wfTimestamp(TS_MW, $row->first_revision_after);
		if($lts < $now - 24*60*60) {
			$t = Title::newFromId($row->rev_page);
			$msg .= "<tr><td>" . $t->getText() . "</td><td>" . $row->rev_page . "</td><td>" . wfTimestamp(TS_DB, $row->last_revision) . "</td><td>" . wfTimestamp(TS_DB, $row->first_revision_after) . "</td><td>" . wfTimestamp(TS_DB, $row->last_patrolled) . "</td></tr>\n";
		}
	}
	$msg .= "</tbody></table>\n";
	if($msg != "") {
		$msg = "<p>The following pages on $wgLanguageCode have been modified over 24 hours ago, but aren't showing up in Titus</p>\n" . $msg;
		print $msg;
		$to = new MailAddress("eng@wikihow.com");
		$from = new MailAddress("alerts@wikihow.com");
		$subject = "Out of date fields in titus:\n";
	        $content_type = "text/html; charset=UTF-8";

		UserMailer::send($to, $from, $subject, $msg, false, $content_encoding);
	}
}
