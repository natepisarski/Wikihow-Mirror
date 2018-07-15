<?php
global $IP;
require_once __DIR__ . '/../../commandLine.inc';
require_once("$IP/extensions/wikihow/titus/GoogleSpreadsheet.class.php");

$dbw = wfGetDB(DB_MASTER);

$gs = new GoogleSpreadsheet();
$startColumn = 1;
$endColumn = 4;
$startRow = 2;
$cols = $gs->getColumnData( WH_TITUS_TOP10K_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
$updated = 0;
foreach($cols as $col) {
	if(is_numeric($col[1]) && $wgLanguageCode == $col[2]) {
		$page_id = $col[1];
		
		//parse for comma-separated keywords on the same line
		$kws = explode(',',$col[0]);
		if (count($kws) > 1) {
			foreach ($kws as $keywords) {
				$kwl[] = array('keywords' => trim($keywords), 'page_id' => $page_id);
			}
		}
	}
}
print 'keywords: '.count($kwl)."\n";

foreach ($kwl as $k) {
	$keywords = urldecode($k['keywords']);
	$page_id = $k['page_id'];

	if (empty($page_id)) {
		//print '-- '.$keywords.' - bad page id = '.$page_id."\n";
		continue;
	}

	//already in the mmk_manager table? nope
	$alreadyIn = $dbw->selectField('mmk.mmk_manager', array('count(*)'), array('mmk_keyword' => $keywords, 'mmk_page_id <> 0'), __METHOD__);
	if ((int)$alreadyIn > 0) {
		//print '-- '.$keywords." - found in MMK\n";
		continue;
	}
	
	//grab position
	$pos = $dbw->selectField('mmk.keywords', array('position'),array('title' => $keywords), __METHOD__);
	if ($pos) {
		//grab page title
		$page_title = $dbw->selectField('page', array('page_title'), array('page_id' => $page_id), __METHOD__);
		if (!$page_title) {
			print '-- '.$keywords.' - no page title - '.$page_id.' - '.$page_title."\n";
			continue;
		}
		
		//print $keywords.' !!! '.$page_title."\n";
		$position = array('mmk_position' => $pos);
		$updates = array(
				'mmk_keyword' => $keywords,
				'mmk_status' => 3,
				'mmk_page_title' => $page_title,
				'mmk_page_id' => $page_id,
				'mmk_old_page_id' => 0,
				'mmk_rating' => '',
				'mmk_rating_date' => '',
				'mmk_language_code' => 'en',
				'mmk_last_updated' => 0
				);	
		$dbw->upsert('mmk.mmk_manager',array_merge($position,$updates),$position,$updates,__METHOD__);
		
		$updated++;
	}
	else {
		print '-- '.$keywords." - no position\n";
	}
}
print "DONE. Updated: $updated\n";
