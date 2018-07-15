<?php

# Updates the database to mark avatars that were deleted in the database
# Written By Gershon Bialer

require_once('commandLine.inc');

$dbr = wfGetDB(DB_SLAVE);
$sql = "select av_user from avatar where av_dateAdded is NOT NULL AND av_patrol=0";
$res = $dbr->query($sql, __METHOD__);
$ids = array();
foreach($res as $row) {
	$filename = $row->av_user.".jpg";
	$cropout = Avatar::getAvatarOutFilePath($filename) . $filename;
	if (file_exists($cropout)) {
		print "exists	" . $cropout . "	" . $row->av_user . "\n";	
	}
	else {
		print "doesn't exist exists	" . $cropout . "	" . $row->av_user . "\n";	
		$ids[] = $row->av_user;
	}
}
$dbw = wfGetDB(DB_MASTER);
$batch = array();
foreach($ids as $id) {
	$batch[] = $id;
	if(sizeof($batch) > 100) {
		break;	
	}
	if(sizeof($batch) > 1000) {
		$sql = "update avatar set av_patrol=2, av_patrolledBy=1857407, av_patrolledDate=" . $dbw->addQuotes(wfTimestampNow())   . " where av_user in (" . implode(',',$batch) .")";
		$batch = array();
		$dbw->query($sql, __METHOD__);
		print($sql);
		sleep(1);
	}
}
if($batch) {
	$sql = "update avatar set av_patrol=2, av_patrolledBy=1857407, av_patrolledDate=" . $dbw->addQuotes(wfTimestampNow())   . " where av_user in (" . implode(',',$batch) .")";
	print($sql);
	$dbw->query($sql,__METHOD__);
}

