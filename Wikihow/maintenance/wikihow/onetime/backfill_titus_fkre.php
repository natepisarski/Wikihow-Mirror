<?php
global $IP;
require_once __DIR__ . '/../../commandLine.inc';
require_once ("$IP/extensions/wikihow/DatabaseHelper.class.php");

$dbw = wfGetDB(DB_MASTER);
$count = 0;

$log = fopen("$IP/maintenance/wikihow/onetime/backfill_titus_fkre.log", 'a');
$error_log = fopen("$IP/maintenance/wikihow/onetime/backfill_titus_fkre_errors.log", 'a');

$limit = (int)$options['limit'];
if (count($options['limit']) == 0 || $limit <= 0) {
	print "please specify limit like --limit=100\n";
	return;
}

print "grabbing...\n";
$res = DatabaseHelper::batchSelect('titusdb2.titus_intl',
			array('ti_page_id'),
			array('ti_language_code' => 'en'),
			__METHOD__, array('LIMIT' => $limit));

print "processing...\n";
foreach ($res as $row) {
	$t = Title::newFromID($row->ti_page_id);
	if (!$t || !$t->exists()) {
		fwrite($error_log, $row->ti_page_id." - bad title\n");
		continue;
	}
	if ($t->isRedirect()) {
		fwrite($error_log, $row->ti_page_id." - redirect\n");
		continue;
	}
	$r = Revision::newFromTitle($t);
	if (!$r) {
		fwrite($error_log, $row->ti_page_id." - bad revision\n");
		continue;
	}
	$res = AdminReadabilityScore::cleanUpText($t, $r->getText());
	if (!$res) {
		fwrite($error_log, $row->ti_page_id." - error with cleanUpText\n");
		continue;
	}
	
	//get the score
	$fkre = AdminReadabilityScore::getFKReadingEase($res);

	if ($fkre) {
		$res = $dbw->update('titusdb2.titus_intl',array('ti_fk_reading_ease' => $fkre),array('ti_page_id' => $row->ti_page_id, 'ti_language_code' => 'en'),__METHOD__);
		if ($res) $count++;
		fwrite($log, "http://www.wikihow.com/{$t->getDBKey()} = $fkre\n");
	}
	else {
		fwrite($error_log, $row->ti_page_id." - no score\n");
	}
}
print "DONE. $count records updated.\n";

fclose($log);
fclose($error_log);