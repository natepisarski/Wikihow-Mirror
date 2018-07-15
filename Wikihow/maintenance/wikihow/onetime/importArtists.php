<?php

require_once __DIR__ . '/../../commandLine.inc';
require_once __DIR__ . '/../../../extensions/wikihow/WikiVisualLibrary/WikiVisualLibrary.php';

if(!isset($argv[0])) {
	echo "You must provide a file name with artists names and types\n";
	return;
}

$fin = fopen($argv[0], "r");
while($line = fgets($fin)) {
	$artistInfo = explode(",", $line);
	$artistInfo[0] = trim($artistInfo[0]);
	$artistInfo[1] = trim($artistInfo[1]);
	$creatorArray[] = $artistInfo;
}

$dbw = wfGetDB(DB_MASTER);

foreach($creatorArray as $artistInfo) {
	$dbw->insert(WVL\Util::DB_TABLE_CREATORS, ['wvlc_name' => $artistInfo[0], 'wvlc_eligible' => 1, 'wvlc_type' => $artistInfo[1]], __FILE__);
	$id = $dbw->insertId();
	$dbw->update(WVL\Util::DB_TABLE_ASSETS, ['wvla_creator_id' => $id], ['wvla_creator' => $artistInfo[0]], __METHOD__);
}
