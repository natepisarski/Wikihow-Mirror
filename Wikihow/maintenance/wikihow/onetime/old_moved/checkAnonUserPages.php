<?php

/****
 * Script checks all anonymous user_talk talk pages. The page is deleted
 * if it matches either of the following conditions:
 * 1) The talk page has had no activity in the last 6 months
 * 2) The ip address in question has not made any edits in the last 6 months
 * 
 * Once deleted, the url for that talk page will return a 404 (by default)
 * 
 * All talk pages that are deleted will be echo'd out to be
 * stored in the log for this script.
 ****/

require_once('commandLine.inc');

global $IP, $wgUser;
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

$wgUser = User::newFromName("MiscBot");

$maxAge = 60*60*24*31*6; //6 months measured in seconds

$dbr = wfGetDB(DB_SLAVE);

echo "Checking talk pages from anonymous users on " . date("F j, Y") . "\n";

$articles = DatabaseHelper::batchSelect('page', array('page_id', 'page_title'), array('page_namespace' => NS_USER_TALK, 'page_is_redirect' => 0));

/*****
//TESTING CODE//

$res = $dbr->select('page', array('page_id', 'page_title'), array('page_namespace' => NS_USER_TALK, 'page_is_redirect' => 0), __FUNCTION__, array('limit' => 200));
$res = $dbr->select('page', array('page_id', 'page_title'), array('page_namespace' => NS_USER_TALK, 'page_is_redirect' => 0, 'page_title' => '67.168.160.94'), __FUNCTION__, array('limit' => 10));

echo "SQL command done.\n";
$articles = array();
while($row = $dbr->fetchObject($res)) {
	$articles[] = $row;
}
*****/

echo "About to check " . count($articles) . " pages\n";

$i = 0;
foreach($articles as $article) {
	$processed = false;
	
	//first check to see if the page in not anonymous user
	if(filter_var($article->page_title, FILTER_VALIDATE_IP) === false)
		continue;
	
	//Check to see if the talk page has been edited in the last $maxAge timeperiod (measured in seconds)
	$talkRevisions = $dbr->select('revision', array('rev_timestamp'), array('rev_page' => $article->page_id), __FUNCTION__, array('ORDER' => 'rev_timestamp DESC', 'LIMIT' => '1'));
	
	foreach($talkRevisions as $revision) {
		$revTime = wfTimestamp(TS_UNIX, $revision->rev_timestamp);
		$nowTime = wfTimestamp();
		if($nowTime - $revTime >= $maxAge){
			deleteTalkPage($article, "Old unused anonymous talk page");
			$i++;
			$processed = true;
		}
		
		break;
	}
	
	if($processed)
		continue;
	
	//Check to see if the user has done an edit in the last $maxAge timeperiod (measured in seconds)
	$userRevisions = $dbr->select('revision', array('rev_timestamp'), array('rev_user_text' => $article->page_title), __FUNCTION__, array('ORDER' => 'rev_timestamp DESC', 'LIMIT' => '1'));
	
	foreach($userRevisions as $revision) {
		$revTime = wfTimestamp(TS_UNIX, $revision->rev_timestamp);
		$nowTime = wfTimestamp();
		if($nowTime - $revTime >= $maxAge){
			deleteTalkPage($article, "Talk page from inactive anonymous user");
			$i++;
			$processed = true;
		}
		
		break;
	}
	
}

echo "Finished. Deleted " . $i . " anonymous talk pages.\n";

/**
 *
 * @param $article - object with the following fields (page_id and page_title)
 * @param $reason - reason for the deletion 
 */
function deleteTalkPage($article, $reason) {
	$title = Title::newFromID($article->page_id);
	if($title) {
		$article = new Article($title);
		if($article) {
			echo $title->getFullURL() . "\n";
			$article->doDelete($reason);
		}
	}
}



