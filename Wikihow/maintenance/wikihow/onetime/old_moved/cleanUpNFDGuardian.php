<?php

require_once( 'commandLine.inc' );

$res = DatabaseHelper::batchSelect("nfd", array('nfd_page'), array('nfd_status' => 0), __FILE__, array("GROUP BY" => 'nfd_page'));

foreach( $res as $result ) {
	$title = Title::newFromID($result->nfd_page);
	if(!$title) {
		NFDProcessor::markPreviousAsInactive($result->nfd_page);
		echo "Removing {$result->nfd_page}\n";
		usleep(500000);
	}
}
