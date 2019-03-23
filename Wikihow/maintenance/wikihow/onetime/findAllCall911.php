<?php

/******
 * Find all articles that contain the words "call 911"
*****/

require_once __DIR__ . '/../../commandLine.inc';

$res = DatabaseHelper::batchSelect('page', array('page_id'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => '0'), __FILE__);

//$dbr = wfGetDB(DB_REPLICA);
//$res = $dbr->select('page', array('page_id'), array('page_id' => '3537'), __FILE__);

$articleCount = 0;
foreach ($res as $row) {
	$title = Title::newFromID($row->page_id);
	if($title) {
		$revision = Revision::newFromTitle($title);

		if($revision) {
			$flattenText = Wikitext::flatten(ContentHandler::getContentText($revision->getContent()));

			if (stripos($flattenText, 'call 911') !== false ) {
				echo $title->getLocalURL() . "\n";
			}
		}
	}

	if ($articleCount % 1000 == 0) {
		usleep(500000);
	}
	$articleCount++;
}
