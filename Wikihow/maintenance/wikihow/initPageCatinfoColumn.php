<?php

require_once __DIR__ . '/../commandLine.inc';

// turn off batching by default
$batch = '-';

$dbr = wfGetDB(DB_SLAVE);

// Get the list of titles
$opts = array(
	"ORDER BY" => "page_id",
	"LIMIT" => 10000,
	"OFFSET" => $batch * 10 * 1000
);
if ($batch == "-") {
	$opts = array(); 
}

$res = $dbr->select('page',
	array('page_namespace', 'page_title'), 
	array(
		'page_namespace' => NS_MAIN,
		'page_is_redirect' => 0,
		'page_catinfo' => 0),
	__FILE__,
	$opts);

$count = 0;
$updates = array();
$titles = array();
foreach ($res as $row) {
	$t = Title::makeTitle($row->page_namespace, $row->page_title);
	if (!$t) continue;
	$titles[] = $t;
}

// Figure out what the catinfo column is supposed to be
foreach ($titles as $t) {
	$val = Categoryhelper::getTitleCategoryMask($t);
	$count++;
	$updates[] = "UPDATE page set page_catinfo={$val} where page_id={$t->getArticleID()};";
}

// Do the updates
print "Doing " . sizeof($updates) . " page_catinfo updates\n";
$count = 0;
$dbw = wfGetDB(DB_MASTER);
foreach ($updates as $u) {
	$dbw->query($u, __FILE__);
	$count++;
}

