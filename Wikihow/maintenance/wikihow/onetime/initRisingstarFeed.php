<?php

require_once '../../commandLine.inc';
$dbr = wfGetDB(DB_SLAVE);
$dbw = wfGetDB(DB_MASTER);

$res = $dbr->select(
		array('templatelinks', 'page'),
		array('page_title', 'page_namespace'),
		array('page_id=tl_from', 'tl_title'=>'Rising-star-discussion-msg-2'));
$ids = array();
while ($row = $dbr->fetchObject($res)) {
	$talk = Title::makeTitle($row->page_namespace, $row->page_title);
	$title = $talk->getSubjectPage();
	#echo "{$title->getFullURL()}\n";
	$ids[] = $title->getArticleId();
}	
foreach ($ids as $i) {
	$dbw->insert('pagelist', array('pl_page'=>$i, 'pl_list'=>'risingstar'));
}
echo "inserted " . sizeof($ids) . " into list\n";
