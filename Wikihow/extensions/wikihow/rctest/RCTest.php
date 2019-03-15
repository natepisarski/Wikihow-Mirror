<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['RCTest'] = __DIR__ . '/RCTest.class.php';

// Hook to mark quizzes deleted that reference pages no longer in the database
$wgHooks['ArticleDelete'][] = array("wfMarkRCTestDeleted");

function wfMarkRCTestDeleted($wikiPage) {
	try {
		$dbw = wfGetDB(DB_MASTER);
		$id = $wikiPage->getId();
		$dbw->update('rctest_quizzes', array('rq_deleted' => 1), array('rq_page_id' => $id));
	} catch (Exception $e) {}

	return true;
}
