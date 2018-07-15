<?php

require_once('commandLine.inc');
$dbw = wfGetDB(DB_MASTER);
$res = $dbw->query("SELECT page_id FROM page, templatelinks WHERE tl_from=page_id AND page_namespace=0 AND tl_title='Merge'", __METHOD__);

print "grabbing merge articles...\n";

while ($row = $res->fetchObject()) {
	$titles[] = Title::newFromID($row->page_id);
}

$file = '/tmp/merge_articles.csv';
$fp = fopen($file, 'w');
fputs($fp, "Merge Article,Target Article\n");

print "writing output to $file...\n";

foreach ($titles as $title) {
	$rev = Revision::newFromTitle($title);
	$wikitext = $rev->getText();
	if ( preg_match('@{{merge\|(.+?)\|@im', $wikitext, $m) ) {
		$url = 'http://www.wikihow.com/' . $title->getPartialURL();
		$target_url = 'http://www.wikihow.com/' . str_replace(' ','-',trim($m[1]));
		$out = array($url, $target_url);
		fputcsv($fp, $out);
	}
}

print 'Done.';
