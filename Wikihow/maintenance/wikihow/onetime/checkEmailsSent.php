<?

require_once('commandLine.inc');
$ts = wfTimestamp(TS_MW, time() - 60*60);
$dbr = wfGetDB(DB_MASTER);
$dbr->selectDB('whdata');
$count = $dbr->selectField("sent_email", array('count(*)'), array('se_timestamp > "' . $ts . '"'));
echo $count;
