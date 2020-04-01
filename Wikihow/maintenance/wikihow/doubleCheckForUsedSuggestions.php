<?php

require_once __DIR__ . '/../commandLine.inc';

$dbr = wfGetDB(DB_REPLICA);
$dbw = wfGetDB(DB_MASTER);

$res = $dbr->query("select st_title, st_id, st_used, st_isrequest, st_notify, page_title, page_id  from suggested_titles left join page on st_title=page_title and page_namespace=0 where st_used=0 and page_title is not null;");

$ids = array();
foreach ($res as $row) {
	$ids[] = $row->st_id;
}

echo "got " .sizeof($ids) . " suggestions to update\n";
if (sizeof($ids) > 0) {
	$dbw->query("update suggested_titles set st_used=1 where st_id in (" . implode($ids, ",") . ");");
}
