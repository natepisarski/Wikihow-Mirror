<?php
// Get all alfredo articles, that have an extra intro space
require_once("commandLine.inc");

$dbr = wfGetDB(DB_SLAVE);
$sql = "select distinct itj_to_aid from wikidb_112.image_transfer_job where itj_to_lang=" . $dbr->addQuotes($wgLanguageCode);
$res = $dbr->query($sql, __METHOD__);
$ids = array();
foreach($res as $row) {
	$ids[] = $row->itj_to_aid;
}
$lookupIds = array();
foreach($ids as $id) {
	if($id != 0) {
		$t = Title::newFromId($id);
		if($t) {
			$r = Revision::newFromTitle($t);
			if($r && preg_match("@^ +[^\s\[]@", $r->getText(), $matches)) {
				print($r->getText());
				$lookupIds[] = array('lang'=>$wgLanguageCode, 'id'=>$id);
			}
		}
	}
}
$pages = Misc::getPagesFromLangIds($lookupIds);
foreach($pages as $page) {
	print(Misc::getLangBaseURL($wgLanguageCode) . '/' . $page['page_title'] . "\n");
}
