<?php

require_once __DIR__ . '/../commandLine.inc';

$dbr = wfGetDB(DB_SLAVE);

# refresh images on the CDN that have been recently 
# uploaded or changed, just in case. images that have been reuploaded
# need to be purged from the CDN
$ts = wfTimestamp(TS_MW, time() - 60*60*1);
$res = $dbr->select('logging',
		array('log_title'),
		array('log_namespace' => NS_IMAGE, "log_timestamp >='$ts'", "log_action"=>"overwrite"), 
		"debug"
	);
$files = array();
foreach ($res as $row) {
	$files[$row->log_title] = 1;
}

if (sizeof($files) == 0) 
	exit;

// build command
$args = "";
foreach ($files as $f=>$v) {
	$title = Title::makeTitle(NS_IMAGE, $f);
	$file = wfFindFile($title);
	if (!$file) continue;
	$url = $file->getUrl();
	//use find command to find all of the files that will be affected
	$dir = preg_replace("@(.*)/.*@", "$1", $url);
	$dir = str_replace("/images", "/opt/images/images_en/thumb", $dir);
	$name = preg_replace("@.*/@", "", $url);
	$cmd =  "/usr/bin/find  $dir -type f -name \"*$name*\"";
	#echo "executing: $cmd\n";
	$err = wfShellExec( $cmd, $retval );
	echo "returned $err\n";
	$args .= $url . "\n";
	if ($retval == 0) {
		$args .= preg_replace("@/opt/images/images_en@", "/images", $err);
	}
}
global $IP;
$cmd = wfEscapeShellArg("/usr/local/bin/php") . 
	' ' . wfEscapeShellArg("$IP/maintenance/cdn_flush.php") .
	" --user='".WH_CDNETWORKS_USERNAME."' --password='".WH_CDNETWORKS_PASSWORD."' --location=\"$args\"";
echo "executing: $cmd\n";
$err = wfShellExec( $cmd, $retval );
echo $err;
