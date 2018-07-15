<?php

/*
 * 
 * Creates a list of articles that have been deleted via 
 * the nfd tool.
 * 
 */

require_once('commandLine.inc'); 


$dbr = wfGetDB(DB_SLAVE); 

$res = $dbr->select('logging', 'log_title', array('log_type' => 'nfd', 'log_action' => 'delete'));

$articles = array();
while($row = $dbr->fetchObject($res)) {
	$articles[] = $row->log_title;
}


foreach($articles as $article) {
	$title = Title::newFromText($article);
	if(!$title->exists()) {
		echo "http://www.wikihow.com/" . $article . "\n";
	}
}


?>
