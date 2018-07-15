<?php

# Load the verified query matches from the spreadhseet into the database
require_once('../commandLine.inc');
global $IP;

require_once("$IP/extensions/wikihow/titus/GoogleSpreadsheet.class.php");
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

class VQMFromTop10k {

	public static function addQueryMatch($query1, $query2) {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "insert ignore into dedup.verified_query_match(vqm_query1, vqm_query2, vqm_date_added) values(" . $dbw->addQuotes($query1) . "," . $dbw->addQuotes($query2) . "," . $dbw->addQuotes(wfTimestampNow()) . ")";
		$dbw->query($sql, __METHOD__);
	}
	public function execute() {
		$gs = new GoogleSpreadsheet();
		$startColumn = 1;
		$endColumn = 5;
		$startRow = 2;
		$cols = $gs->getColumnData( WH_TITUS_TOP10K_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
		foreach ( $cols as $col ) {
			if ($col[2] == "en" ) {
				$queries =  preg_split("@, *@",$col[0]);
				$url = $col[4];
				if(preg_match("@http://www\.wikihow\.com/(.+)@", $url, $matches)) {
					$titleKW = "how to " . str_replace("-"," ",urldecode($matches[1]));
					if ( !in_array($titleKW, $queries) ) {
						$queries[] = $titleKW;
					}
					foreach ( $queries as $query1 ) {
						foreach ( $queries as $query2 ) {
							$this->addQueryMatch($query1, $query2);
						}
					}
				}
			}
		}
	}
}

$v = new VQMFromTop10k();
$v->execute();
