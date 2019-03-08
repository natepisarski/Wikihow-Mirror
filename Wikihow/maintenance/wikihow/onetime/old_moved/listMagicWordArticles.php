<?php
//get a list of ids for all articles with a specific magic word in them
require_once('commandLine.inc');

$magicword = 'parts';

$db = wfGetDB(DB_SLAVE);
$res = DatabaseHelper::batchSelect('page', array('page_title'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ));

foreach ($res as $row) {	
	$title = Title::newFromText($row->page_title);
	$wikitext = Wikitext::getWikitext($db, $title);

	$mw = MagicWord::get( $magicword );
	if ($mw->match( $wikitext ) ) print $title->getArticleID()." http://www.wikihow.com/".$title->getDBKey()."\n";
}
print "DONE.\n";