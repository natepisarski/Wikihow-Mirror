<?php
// I realize this belongs in the portal branch, but super-quick, one-time need. Sorry, everyone.
global $IP;
require_once __DIR__ . '/../../commandLine.inc';

$dbw = wfGetDB(DB_MASTER);

$res = $dbw->select(
	'cf.cf_documents',
	[
		'article_id',
		'doc_url'
	],
	[
		'type' => 'verification'
	],
	__METHOD__,
	[
		'ORDER BY' => 'article_id'
	]
	);

$last_id = 0;
foreach ($res as $row) {
	if ($last_id != $row->article_id) print "\n".$row->article_id;
	print ', '.$row->doc_url;
	$last_id = $row->article_id;
}
