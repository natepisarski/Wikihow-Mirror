<?php
/*
 * Import articles
 */

global $IP;
require_once('commandLine.inc');

function checkForEmpty($t){
	$deleteComment = "Deleting unused profile box page";
	if($t){
		$a = new Article($t);
		if($a->exists()){
			$content = $a->getContent();
			if($content == ""){
				if($a->doDeleteArticle($deleteComment)){
					echo $deleteComment . " " . $t->getText() . "\n";
					return true;
				}
			}
		}
	}
	return false;
}

$dbr = wfGetDB(DB_SLAVE);
$res = $dbr->select('user', 'user_id', array('user_registration >= 20110501000000'), 'removeEmptyProfilePages');

$users = array();
while($result = $dbr->fetchObject($res)){
	$users[] = $result->user_id;
}
$dbr->freeResult( $res );

$articleTitles = array("/profilebox-live", "/profilebox-aboutme", "/profilebox-occupation");
$totalDeleted = 0;
foreach($users as $user){
	$u = User::newFromId($user);
	if($u){
		$userPage = $u->getUserPage();
		foreach($articleTitles as $title){
			$t = Title::newFromText($userPage->getBaseText() . $title, NS_USER);
			$totalDeleted += checkForEmpty($t)?1:0;
		}
	}
}

echo "Total number of pages deleted: " . $totalDeleted . "\n";