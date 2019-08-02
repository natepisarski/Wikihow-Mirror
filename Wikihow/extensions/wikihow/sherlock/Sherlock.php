<?php
class Sherlock {

	const SHS_TABLE = 'whdata.sherlock_search';
	const SHA_TABLE = 'whdata.sherlock_article';

	public function __contstruct() {
	}

	// Make a new Sherlock Search entry. This corresponds to a
	// search being done against our WH search product.
	public static function logSherlockSearch($q, $vId, $numResults, $logged, $platform) {
		global $wgReadOnly;
		if ($wgReadOnly) {
			return '';
		}

		// Establish DB connection
		$dbw = wfGetDB(DB_MASTER);

		// Encode the query
		$q = $dbw->strencode($q);

		// Create data array for the entry
		$data = array(
			"shs_query"         => $q,
			"shs_results"       => $numResults,
			"shs_visitor_id"    => $vId,
			"shs_usr_logged"    => $logged,
			"shs_platform"      => $platform,
			"shs_timestamp"     => $dbw->timestamp()
		);

		// Insert data, get the entry key
		$dbw->insert(self::SHS_TABLE, $data, __METHOD__);
		$key = $dbw->insertId();

		return $key;
	}

	// Make a new Sherlock article entry. This corresponds to
	// an article being viewed (after a search has happened)
	// from our WH search index.
	public static function logSherlockArticle($pageId, $index, $shs_key, $pageTitle) {
		global $wgReadOnly;
		if ($wgReadOnly) {
			return;
		}

		$dbw = wfGetDB(DB_MASTER);

		$data = array(
			"shs_key"           => $shs_key,
			"sha_article_id"    => $pageId,
			"sha_title"         => $pageTitle,
			"sha_index"         => $index,
			"sha_timestamp"     => $dbw->timestamp()
		);

		$dbw->insert(self::SHA_TABLE, $data, __METHOD__);
	}

}
