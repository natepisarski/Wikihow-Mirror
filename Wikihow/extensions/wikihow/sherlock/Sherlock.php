<?php
class Sherlock {

	const SHS_TABLE = 'whdata.sherlock_search';
	const SHA_TABLE = 'whdata.sherlock_article';

	public function __contstruct() {

	}

	// Make a new Sherlock Search entry
	public static function logSherlockSearch($q, $vId, $numResults, $logged, $platform) {
		// Establish DB connection
		$dbw = wfGetDB(DB_MASTER);

		// Encode the query
		$q = $dbw->strencode($q);

		// Create data array for the entry
		$data = array(
			"shs_query"			=> $q,
			"shs_results"		=> $numResults,
			"shs_visitor_id"	=> $vId,
			"shs_usr_logged"	=> $logged,
			"shs_platform"		=> $platform,
			"shs_timestamp"		=> $dbw->timestamp()
		);

		// Insert data, get the entry key
		$dbw->insert(self::SHS_TABLE, $data, __METHOD__);
		$key = $dbw->insertId();

		return $key;
	}

	// Make a new Sherlock article entry
	public static function logSherlockArticle($pageId, $index, $shs_key, $pageTitle) {
		// Establish DB connection
		$dbw = wfGetDB(DB_MASTER);

		// Create data array for the entry
		$data = array(
			"shs_key"			=> $shs_key,
			"sha_article_id"	=> $pageId,
			"sha_title"			=> $pageTitle,
			"sha_index"			=> $index,
			"sha_timestamp"		=> $dbw->timestamp()
		);

		// Insert data
		$res = $dbw->insert(self::SHA_TABLE, $data, __METHOD__);
	}

	private static function checkSherlockSearchDuplicate() {
		//query the DB for the last entry for 
	}
}
