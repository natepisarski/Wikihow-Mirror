<?php
global $IP;
require_once __DIR__ . '/../../commandLine.inc';

$dbw = wfGetDB(DB_MASTER);
$count = 0;
$res = $dbw->query('select title from mmk.keywords group by title having count(*) > 1',__METHOD__	);
foreach ($res as $row) {
	$dups[] = $row->title;
}
foreach ($dups as $t) {
	$pos = $dbw->query('select position from mmk.keywords where title = '.$dbw->addQuotes($t).' order by position desc', __METHOD__);
	foreach ($pos as $p) {
		$dbw->delete('mmk.keywords',array('position' => $p->position), __METHOD__);
		$count++;
		break;
	}
}
print "done. Deleted rows: ".$count."\n";