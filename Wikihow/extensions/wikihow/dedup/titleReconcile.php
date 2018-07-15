<?php

/**
 * Class for reconciling the article titles in the Dedup system, and article titles 
 * on the site.
 */
class TitleReconcile
{
	public static function reconcile() {
		global $wgLanguageCode;

		//Deal with titles turned into deletes, redirects, or re-named
		$dbr = wfGetDB(DB_SLAVE);
		$sql = "select tq_title from dedup.title_query left join page on tq_page_id=page_id and replace(page_title,'-',' ')=tq_title and page_namespace=0 and page_is_redirect=0 where page_title is NULL";
		$res = $dbr->query($sql, __METHOD__);
		$deletedTitles = array();
		foreach($res as $row) {
			$deletedTitles[] = $row->tq_title;
		}
		foreach($deletedTitles as $pageTitle) {
			print("Removing title from system " . $pageTitle . "\n");
			DedupQuery::removeTitle($pageTitle,$wgLanguageCode);	
		}

		// Add titles missing from our system with associated keywords
		$dbr = wfGetDB(DB_SLAVE);
		$sql = "select page.* from page left join dedup.title_query on tq_page_id=page_id AND tq_lang=" . $dbr->addQuotes($wgLanguageCode) . " where page_namespace=0 and page_is_redirect=0 and tq_title is NULL group by page.page_id";
		$res = $dbr->query($sql, __METHOD__);
		$missingTitles = array();
		foreach($res as $row) {
			$missingTitles[] = $row;
		}
		foreach($missingTitles as $title) {
			print("Adding title to system " . $title->page_title . "\n");
			$t = Title::newFromRow($title);
			DedupQuery::addTitle($t, $wgLanguageCode);	
		}

	}
}
