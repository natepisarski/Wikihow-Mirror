<?

require_once('commandLine.inc');

# User/bot who did the deleting
$user = 'Miscbot';
# Namespace where deleting was done
$ns = NS_USER;
# lookback to this date
$startDate = '201204140000';

$wgUser = User::newFromName($user);
$wgUser->load();

$dbr = wfGetDB(DB_SLAVE);
$sql = "select log_title from logging where log_timestamp > '$startDate' and log_user = {$wgUser->getId()} and log_type = 'delete' and log_namespace = $ns order by log_timestamp asc limit 1";
$res = $dbr->query($sql);

$pages = array();
foreach ($res as $row) {
	$pages[] = $row->log_title;	
}
var_dump($pages);exit;


foreach ($pages as $p) {
	$t = Title::newFromText($p, $ns);
	if ($t) {
		$archive = new PageArchive($t);	
		$result = $archive->undelete(array(), "undeleting mistakenly deleted user page");
		echo "Undeleting: $p\N";
		if ($result === false) {
			logError("Couldn't undelete $p");
	} else {
		logError("Title not found for page $p");	
	}
}


function logError($msg) {
	echo "ERROR: $msg\n";
}
