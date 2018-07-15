<?php

require_once __DIR__ . '/../../commandLine.inc';

$dbw = wfGetDB(DB_MASTER);

$res = DatabaseHelper::batchSelect([UnitGuardian::TABLE_NAME_TOOL, 'page'], ['ug_page'], ['ug_page = page_id', 'page_namespace != ' . NS_MAIN]);

$i = 1;
$deleteIds = [];
foreach($res as $row) {
	$deleteIds[] = $row->ug_page;
	if($i % 1000 == 0) {
		$dbw->delete(UnitGuardian::TABLE_NAME_TOOL, ['ug_page IN (' . $dbw->makeList($deleteIds) . ")"], __FILE__);
		$dbw->delete(UnitGuardian::TABLE_NAME_CONVERSIONS, array('ugc_page IN (' . $dbw->makeList($deleteIds) . ")", 'ugc_resolved' => 0), __METHOD__);
		$deleteIds = [];
		usleep(1000);
	}
	$i++;
}

if(count($deleteIds) > 0) {
	$dbw->delete(UnitGuardian::TABLE_NAME_TOOL, ['ug_page IN (' . $dbw->makeList($deleteIds) . ")"], __FILE__);
	$dbw->delete(UnitGuardian::TABLE_NAME_CONVERSIONS, array('ugc_page IN (' . $dbw->makeList($deleteIds) . ")", 'ugc_resolved' => 0), __METHOD__);
}

echo "done! Deleted " . $i . " rows\n";

