<?php

/******************
 * 
 * Outputs a list of all users that have earned
 * the featured author badge
 * 
 *****************/


require_once('commandLine.inc');
require_once('../extensions/wikihow/DatabaseHelper.class.php');

$dbr = wfGetDB(DB_SLAVE);

$users = DatabaseHelper::batchSelect('user', 'user_id');

foreach($users as $userObj) {
	$user = User::newFromId($userObj->user_id);
	
	$resFA = $dbr->select(array('firstedit', 'templatelinks'), '*', array('fe_page=tl_from', 'fe_user' => $user->getID(), ('tl_title = "Fa" OR tl_title = "FA"') ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
	$resRS = $dbr->select(array('firstedit', 'pagelist'), '*', array('fe_page=pl_page', 'fe_user' => $user->getID() ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
	if($dbr->numRows($resFA) + $dbr->numRows($resRS) >= 5) {
		$lastTime = $dbr->selectField(array('logging'), array('log_timestamp'), array('log_user' => $userObj->user_id), __FUNCTION__, array("ORDER BY" => "log_timestamp DESC", "LIMIT" => 1));
		$lastDate = date("n/j/Y", wfTimestamp(TS_UNIX, $lastTime));
		echo $user->getUserPage()->getFullURL() . " " . $lastDate . "\n";
	}
}