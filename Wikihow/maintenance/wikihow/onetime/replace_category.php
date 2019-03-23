<?php
//For replacing one category string with another on articles

global $IP;
require_once __DIR__ . '/../../commandLine.inc';
$wgUser = User::newFromName('MiscBot');

$old_cat = 'In progress articles removed from NAB';
$new_cat = 'Articles in Quality Review';

$dbr = wfGetDB(DB_REPLICA);
// $sql = "SELECT page_title, page_id 
			// FROM (page, categorylinks cl1)
			// WHERE cl1.cl_from = page_id 
			// AND cl1.cl_to = " . $dbr->addQuotes(str_replace(' ','-',$old_cat));
// $res = $dbr->query($sql,__METHOD__);
			
$res = DatabaseHelper::batchSelect(
			array('page','categorylinks'),
			array('page_title','page_id'), 
			array('categorylinks.cl_from = page_id', 'categorylinks.cl_to' => str_replace(' ','-',$old_cat) )
);

$total = 0;
$count = 0;

foreach ($res as $row) {
	print $row->page_title."\n";
	$page_id = $row->page_id;
	if (replaceCat($page_id)) $count++;
	$total++;
}

print "$count out of $total articles updated\n";



function replaceCat($id) {
	global $wgContLang, $new_cat, $old_cat, $dbr;
	
	//grab the wikitext	
	$t = Title::newFromID($id);
	if (!$t || !$t->exists()) return false;
	$wikitext = Wikitext::getWikitext($dbr, $t);

	//now, just the intro
	$intro = Wikitext::getIntro($wikitext);
	$old_intro = $intro;
	
	//replace
	$intro = str_replace("[[" . $wgContLang->getNSText(NS_CATEGORY) . ":" . $old_cat . "]]", "[[" . $wgContLang->getNSText(NS_CATEGORY) . ":" . $new_cat . "]]", $intro);
	if ($intro == $old_intro) return false;
	
	$wikitext = Wikitext::replaceIntro($wikitext, $intro);
	$result = Wikitext::saveWikitext($t, $wikitext, 'Renaming a category');
	
	if ($result !='') return false;
	
	return true;
}
