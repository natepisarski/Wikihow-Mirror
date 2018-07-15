<?php

require_once __DIR__ . '/../../commandLine.inc';

$res = DatabaseHelper::batchSelect(Newarticleboost::NAB_TABLE, array('nap_page', 'nap_atlas_score'), array('nap_patrolled' => 0, 'nap_demote' => 0), __FILE__);


foreach ($res as $row) {
	$title = Title::newFromID($row->nap_page);

	if ($title) {
		echo $title->getFullURL() . ", " . $row->nap_atlas_score . "\n";
	}
}