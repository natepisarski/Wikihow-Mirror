<?php
global $IP;
require_once("$IP/extensions/wikihow/titus/GoogleSpreadsheet.class.php");
define('WH_KEYWORD_MASTER_GOOGLE_DOC','0Aoa6vV7YDqEhdDZXQ3RCaXJYWUdxN3RYelQzYVBfNnc/od6');
/**
 * Functions for inputting data from different sources into the Dedup system
 */
class DedupQueryInput {
	/**
	 * Load from the keyword master Google spreadsheet
	 */
	public static function addSpreadsheet() {
		$gs = new GoogleSpreadsheet();
		$startColumn = 1;
		$endColumn = 1;
		$startRow = 2;
		$cols = $gs->getColumnData( WH_KEYWORD_MASTER_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
		foreach($cols as $col) {
			DedupQuery::addQuery($col[0]);
		}
	}
	/**
	 * Dedup keywords from the top 2m list
	 */
	public static function addTopKeywords($min, $max) {
		$sql = "select title from dedup.keywords where position >= " . intVal($min) .  " and position <= " . intVal($max);
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$keywords = array();
		foreach ( $res as $row ) {
			$keywords[] = $row->title;
		}
		foreach ( $keywords as $keyword ) {
			DedupQuery::addQuery($keyword);
		}
		DedupQuery::matchQueries($keywords);
	}
}
