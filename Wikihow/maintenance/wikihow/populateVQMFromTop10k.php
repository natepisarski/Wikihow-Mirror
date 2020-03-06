<?php
#
# Load the verified query matches from the spreadhseet into the database
#

require_once __DIR__ . '/../Maintenance.php';


class VQMFromTop10k extends Maintenance {

	public function execute() {
		$this->processSpreadsheet();
	}

	public static function addQueryMatch($query1, $query2) {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "insert ignore into dedup.verified_query_match(vqm_query1, vqm_query2, vqm_date_added) values(" . $dbw->addQuotes($query1) . "," . $dbw->addQuotes($query2) . "," . $dbw->addQuotes(wfTimestampNow()) . ")";
		$dbw->query($sql, __METHOD__);
	}

	public function processSpreadsheet() {
		$cols = GoogleSheets::getRows( WH_TITUS_TOP10K_GOOGLE_DOC, 'Super Titus!A2:E' );
		foreach ( $cols as $col ) {
			if ($col[2] == "en" ) {
				$queries =  preg_split("@, *@",$col[0]);
				$url = $col[4];
				if (preg_match("@https?://www\.wikihow\.com/(.+)@", $url, $matches)) {
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

$maintClass = 'VQMFromTop10k';
require_once RUN_MAINTENANCE_IF_MAIN;
