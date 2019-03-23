<?php

require_once __DIR__ . '/../../commandLine.inc';

echo "Article,Sample\n";

$prefix = 'http://www.wikihow.com';

$dbr = wfGetDB(DB_REPLICA);
$res = $dbr->select('dv_links', '*', '', __METHOD__);

foreach ($res as $row) {
	$t = Title::newFromId($row->dvl_page);
	if ($t && $t->exists() && !$t->isRedirect()) {
		echo $prefix.$t->getLocalUrl().','.$prefix.'/Sample/'.$row->dvl_doc."\n";
	}
}

echo "done.\n";
