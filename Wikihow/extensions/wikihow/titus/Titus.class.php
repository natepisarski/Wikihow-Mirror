<?php
/*
 * Titus is a meta db of stats pertaining to our articles.  This file includes the classes
 * that store and retreive data from the db
 */

// TODO: in this class, there are a TON of database queries that don't use
// the standard Mediawiki database interface. This should be fixed in all
// cases where it's possible.

if ( !defined('MEDIAWIKI') ) die();

require_once __DIR__ . "/../DatabaseHelper.class.php";
require_once __DIR__ . "/../TranslationLink.php";
require_once __DIR__ . '/GoogleSpreadsheet.class.php';

class TitusDB {
	var $db = [];
	var $debugOutput;
	var $dataBatch = array();
	var $dataBatchMulti = array();
	var $statClasses = array();

	const TITUS_TABLE_NAME = 'titus_intl as titus';
	const TITUS_INTL_TABLE_NAME = 'titus_intl';
	const TITUS_HISTORICAL_TABLE_NAME = 'titus_historical_intl';
	const DAILY_EDIT_IDS = "dailyeditids";
	const ALL_IDS = "allids";

	static function getDBName() {
		return 'titusdb2';
	}

	static function getDBHost() {
		$host = WH_DATABASE_MASTER;
		return $host;
	}

	function __construct( $debugOutput = false ) {
		$this->debugOutput = $debugOutput;
	}

	/**
	 * Gets a singleton of a particular stat class to avoid re-instantiation
	 */
	function getStatClass( $name ) {
		if ( isset( $this->statClasses[$name] ) ) {
			return $this->statClasses[$name];
		} else {
			$tsName = "TS" . $name;
			$this->statClasses[$name] = new $tsName();
			return $this->statClasses[$name];
		}
	}

	public function getErrors( $activeStats ) {
		$errors = "";
		foreach ( $activeStats as $stat => $isOn ) {
			if ( $isOn ) {
				$statCalculator = $this->getStatClass( $stat );
				$error = $statCalculator->getErrors();
				if ( $error ) {
					$errors .= $stat . " errors:\n";
					$errors .= $error;
					$errors .= "\n";
				}
			}
		}
		return $errors;
	}

	/**
	 * Get pages to calculate by statistic
	 * returns arrays of stats which fall into 3 groups:
	 * stats to run on all pages on the site
	 * stats to run only on things edited today
	 * stats to run on "other" - things that have a custom set of ids to act on
	 * @return (all_id_stats => (array of stats), daily_edit_stats =>(everything edited today), ids=>(array map of stuff edited today), id_stats=>(list of stats calculated for a limited number of ids))
	 */
	public function getPagesToCalcByStat( $activeStats, $date ) {
		$ret = array(
			"all_id_stats" => TitusConfig::getBasicStats(),
			"daily_edit_stats" => TitusConfig::getBasicStats(),
			"id_stats" => TitusConfig::getBasicStats(),
			"custom_id_stats" => array()
		);

		$dbr = $this->getTitusDB('read');

		foreach ( $activeStats as $stat => $isOn ) {
			if ( $isOn ) {
				$statCalculator = $this->getStatClass( $stat );

				$ids = null;
				try {
					$ids = $statCalculator->getPageIdsToCalc( $dbr, $date );
				} catch ( Exception $e ) {
					// report this exception and move on
					$this->printPageIdsToCalcException( $stat, $e );
					$statCalculator->storeError( $e, true );
					$ids = array();

					// we are setting the ignore errors to false again here because of a bug with mediawiki
					// that sets the ignore to true and never sets it back
					// it will be fixed in mw 1.25. you are not advised to call this function from outside the
					// Database.php class but this is done on purpose here
					// the mediawiki changeid for this is I41508127f74e1bbee4c020546fed85ab53318ab7
					// TODO: remove this call after MW Upgrade 2019
					$dbr->ignoreErrors( false );
				}

				if ( is_array( $ids ) ) {
					// check if the all ids or daily edit is part of the return array
					if ( array_key_exists( TitusDB::DAILY_EDIT_IDS, $ids ) && $ids[TitusDB::DAILY_EDIT_IDS] == 1 ) {
						$ret["daily_edit_stats"][$stat] = 1;
						unset( $ids[TitusDB::DAILY_EDIT_IDS] );
					}
					if ( array_key_exists( TitusDB::ALL_IDS, $ids ) && $ids[TitusDB::ALL_IDS] == 1 ) {
						$ret["all_id_stats"][$stat] = 1;
						unset( $ids[TitusDB::ALL_IDS] );
					}
					$ret["id_stats"][$stat] = 1;
					$ret["custom_id_stats"][$stat] = $ids;
				} elseif ( $ids == TitusDB::DAILY_EDIT_IDS ) {
					$ret["daily_edit_stats"][$stat] = 1;
				} elseif ( $ids == TitusDB::ALL_IDS ) {
					$ret["all_id_stats"][$stat] = 1;
				} else {
					throw new Exception( "Return type of getPageIds from " . $stat . " was not found" );
				}
			}
		}

		return $ret;
	}


	/*
	 * This function gets a list of pageIds use for calculating the latest edits
	 *
	 */
	private function getDailyEditIds( $lookBack = 1 ) {
		$dbr = $this->getWikiDB();
		// Offset to convert times to Pacific Time DST
		// Titus runs after midnight PDT, and we want to ensure Titus runs before this is called
		$PDST_OFFSET = 7*60*60;
		$lowDate = wfTimestamp( TS_MW, strtotime( "-$lookBack day", strtotime( date( 'Ymd', time() ) ) ) + $PDST_OFFSET );
		$highDate = wfTimestamp( TS_MW, strtotime( date( 'Ymd', time() ) ) + $PDST_OFFSET );
		$rows = DatabaseHelper::batchSelect(
			'daily_edits',
			'de_page_id',
			array(
				"de_timestamp >= '$lowDate'",
				"de_timestamp < '$highDate'",
				"(de_edit_type <> " . DailyEdits::DELETE_TYPE . " )"
			),
			__METHOD__,
			array(),
			1000,
			$dbr
		);
		$pageIds = array();
		foreach ($rows as $row) {
			$pageIds[] = $row->de_page_id;
		}
		return $pageIds;
	}

	/*
	 * This function calcs Titus stats for pages that have been most recently edited on wikiHow.
	 * See DailyEdits.class.php for more details
	 */
	public function calcLatestEdits( $statsToCalc, $pageIds = null ) {
		if ( $pageIds == null ) {
			$pageIds = $this->getDailyEditIds();
		}

		if ( !$pageIds || sizeof( $pageIds ) == 0 ) {
			return;
		}

		$pageChunks = array_chunk($pageIds, 1000);
		foreach ( $pageChunks as $chunk ) {
			$this->calcStatsForPageIds( $statsToCalc, $chunk );
		}
	}

	/*
	 * Get page ids to calculate stats.
	 * Calc Titus stats for an array of $pageIds
	 */
	public function calcStatsForPageIds( $statsToCalc,  $pageIds ) {
		if ( sizeof( $pageIds ) > 1000 ) {
			throw new Exception( "\$pageIds must be an array of 1000 or fewer page ids" );
		}

		if ( sizeof( $pageIds ) == 0 ) {
			decho( "no pages to process for stats", $statsToCalc, false );
			return;
		}

		$dbr = $this->getWikiDB();
		$pageIds = implode( ",", $pageIds );


		$rows = DatabaseHelper::batchSelect( 'page',
			array( 'page_id', 'page_title', 'page_counter', 'page_is_featured', 'page_catinfo', 'page_len'),
			array( 'page_namespace' => 0, 'page_is_redirect' => 0, "page_id IN ($pageIds)" ),
			__METHOD__,
			array(),
			DatabaseHelper::DEFAULT_BATCH_SIZE,
			$dbr );

		foreach ( $rows as $row ) {
			$fields = $this->calcPageStats( $statsToCalc, $row );

			if ( !empty( $fields ) ) {
				$this->batchStoreRecordWithError( $fields );
			}
		}
		// flush out current batch
		$this->flushDataBatch();
		$this->flushDataBatchMulti();
	}

	/*
	 * Calc Titus stats for all pages in the page table that are NS_MAIN and non-redirect.
	 * WARNING:  Use this with caution as calculating all Titus stats takes many hours
	 * $statsToCalc - an array of stats which will be calculated which correspond to a TitusStat class
	 * $pageIds - optional argument to force the code to run on a specific list of page ids
	 */
	public function calcStatsForAllPages( $statsToCalc, $pageIds = null ) {
		$dbr = $this->getWikiDB();
		if ( $statsToCalc == TitusConfig::getBasicStats() ) {
			echo "no stats to calculate for all pages...skipping\n";
			return;
		}

		$options = '';

		$conditions = array( 'page_namespace' => 0, 'page_is_redirect' => 0 );

		// if the pageIds argument exists add it to the conditions
		if ( $pageIds != null && is_array( $pageIds ) ) {
			decho( 'running allId stats on custom set of ids', $pageIds, false );
			$conditions['page_id'] = $pageIds;
		}

		$rows = DatabaseHelper::batchSelect(
			'page',
			array( 'page_id', 'page_title', 'page_counter', 'page_is_featured', 'page_catinfo', 'page_len' ),
			$conditions,
			__METHOD__,
			$options,
			DatabaseHelper::DEFAULT_BATCH_SIZE,
			$dbr
		);

		foreach ($rows as $row) {
			$fields = $this->calcPageStats($statsToCalc, $row);

			if (!empty($fields)) {
				$this->batchStoreRecordWithError( $fields );
			}
		}

		// flush out current batch
		$this->flushDataBatch();
		$this->flushDataBatchMulti();
	}

	/*
	 ** FOR TESTING PURPOSES **
	 * Calc Titus stats for all pages in the page table that are NS_MAIN and non-redirect.
	 * WARNING:  Use this with caution as calculating all Titus stats takes many hours
	 */
	public function calcStatsForAllPagesWithOutput($statsToCalc, $limit = array()) {
		$dbr = $this->getWikiDB();

		$rows = DatabaseHelper::batchSelect('page',
			array('page_id', 'page_title', 'page_counter', 'page_is_featured', 'page_catinfo', 'page_len'),
			array('page_namespace' => 0, 'page_is_redirect' => 0),
			__METHOD__,
			$limit,
			DatabaseHelper::DEFAULT_BATCH_SIZE,
			$dbr);

		print "page id\t";
		foreach ($statsToCalc as $stat=>$val) {
			print $stat."\t";
		}
		print "\n";
		foreach ($rows as $row) {
			$fields = $this->calcPageStats($statsToCalc, $row);

			if (!empty($fields)) {
				print $row->page_id."\t";
				foreach ($fields as $v) {
					print $v."\t";
				}
				print "\n";
			}
		}
	}

	private function printPageIdsToCalcException( $stat, $e ) {
		global $wgLanguageCode;
		echo "warning:\nexception getting page ids to calc: $stat\n".
			"in language: $wgLanguageCode\n".
			"exception is: $e.\n";
	}

	private function printPageCalcException( $stat, $t, $e ) {
		global $wgLanguageCode;
		echo "warning:\nexception calculating stat: $stat\n".
			"with title: $t\n".
			"with pageId: {$t->getArticleID()}\n".
			"in language: $wgLanguageCode\n".
			"exception is: $e.\n";
	}

	/*
	 * Calc stats for a given article.  An article is represented by a subset of its page data from the page table,
	 * but this should probably be abstracted in the future to something like TitusArticle with the appropriate fields
	 */
	public function calcPageStats( $statsToCalc, $row ) {
		$dbr = $this->getWikiDB();

		$t = Title::newFromId( $row->page_id );
		$goodRevision = GoodRevision::newFromTitle( $t, $row->page_id );
		$revId = 0;
		if ( $goodRevision ) {
			$revId = $goodRevision->latestGood();
		}
		$r = $revId > 0 ? Revision::loadFromId( $dbr, $revId ) : Revision::loadFromPageId( $dbr, $row->page_id );

		$fields = array( "error" => false );
		if ( $r && $t && $t->exists() ) {
			foreach ( $statsToCalc as $stat => $isOn ) {
				if ( $isOn ) {
					$statCalculator = $this->getStatClass( $stat );
					$statResult = array();
					try {
						$statResult = $statCalculator->calc( $dbr, $r, $t, $row );
					} catch ( Exception $e ) {
						// report this exception and move on
						$this->printPageCalcException( $stat, $t, $e );
						$statCalculator->storeError( $e, true );
						$fields['error'] = true;

						// we are setting the ignore errors to false again here because of a bug with mediawiki
						// that sets the ignore to true and never sets it back
						// it will be fixed in mw 1.25. you are not advised to call this function from outside the
						// Database.php class but this is done on purpose here
						// the mediawiki changeid for this is I41508127f74e1bbee4c020546fed85ab53318ab7
						// TODO: remove this call after MW Upgrade 2019
						$dbr->ignoreErrors( false );
					}
					if ( $statResult && is_array( $statResult ) ) {
						$fields = array_merge( $fields, $statResult );
					}
				}
			}
		}
		return $fields;
	}

	private function batchStoreMulti( $data ) {
		for ( $i = 0; $i < sizeof( $this->dataBatchMulti ); $i++ ) {
			$dataBatch = $this->dataBatchMulti[$i];
			if ( !empty( $dataBatch ) && empty( array_diff_key( $data, $dataBatch[0] ) ) ) {
				$this->dataBatchMulti[$i][] = $data;
				return;
			}
		}
		$this->dataBatchMulti[] = array( $data );
	}

	/*
	 * Stores records in batches sized as specified by the $batchSize parameter
	 * NOTE:  This method buffers the data and only stores data once $batchSize threshold has been
	 * met.  To immediately store the bufffered data call flushDataBatch()
	 */
	public function batchStoreRecord( $data, $batchSize = 1000 ) {
		if ( empty( $data ) ) {
			return;
		}

		$this->dataBatch[] = $data;
		if ( sizeof( $this->dataBatch ) >= $batchSize ) {
			$this->flushDataBatch();
		}
	}

	/*
	 * Stores records in batches sized as specified by the $batchSize parameter
	 * NOTE:  This method buffers the data and only stores data once $batchSize threshold has been
	 * met.  To immediately store the bufffered data call flushDataBatch()
	 */
	public function batchStoreRecordWithError( $data, $batchSize = 1000 ) {
		$error = $data['error'];
		unset( $data['error'] );
		if ( $error === true ) {
			$this->batchStoreRecordMulti( $data );
			return;
		} else {
			$this->batchStoreRecord( $data );
		}
	}

	public function batchStoreRecordMulti( $data, $batchSize = 1000 ) {
		if ( empty( $data ) ) {
			return;
		}

		$this->batchStoreMulti( $data );

		foreach ( $this->dataBatchMulti as $dataBatch ) {
			if ( sizeof( $this->dataBatch ) >= $batchSize ) {
				$this->flushDataBatchMulti();
			}
		}
	}

	/*
	 * Returns all records currently in titus
	 */
	public function getRecords() {
		$dbr = $this->getTitusDB('read');

		$rows = DatabaseHelper::batchSelect(
			TitusDB::TITUS_INTL_TABLE_NAME,
			'*',
			array(),
			__METHOD__,
			array(),
			2000,
			$dbr
		);

		return $rows;
	}

	public function getOldRecords( $datestamp ) {
		$dbr = $this->getTitusDB('read');

		$rows = DatabaseHelper::batchSelect(
			TitusDB::TITUS_HISTORICAL_TABLE_NAME,
			'*',
			array( 'ti_datestamp' => $datestamp ),
			__METHOD__,
			array(),
			2000,
			$dbr
		);

		return $rows;
	}

	/*
	 * Stores multiple records of data.  IMPORTANT:  All data records must contain identical fields of data to insert
	 */
	public function storeRecords( $dataBatch ) {
		if ( !sizeof( $dataBatch ) ) {
			return;
		}

		$fields = join( ",", array_keys( $dataBatch[0] ) );
		$set = array();
		foreach ( $dataBatch[0] as $col => $val ) {
			$set[] = "$col = VALUES($col)";
		}
		$set = join( ",", $set );

		$values = array();
		foreach ( $dataBatch as $data ) {
			$values[] = "('" . join( "','", array_values( $data ) ) . "')";
		}
		$values = implode( ",", $values );

		$dbw = $this->getTitusDB();
		$sql = "INSERT INTO " . TitusDB::TITUS_INTL_TABLE_NAME . " ($fields) VALUES $values ON DUPLICATE KEY UPDATE $set";
		if ( $this->debugOutput ) {
			if ( !is_array( $dataBatch ) ) {
				print "Warning: this-" . ">dataBatch is not an array! var_dumping:\n";
				var_dump( $dataBatch );
			} else {
				foreach ( $dataBatch as $i => $arr ) {
					$row = str_replace( "\n", "", print_r( $arr, true ) );
					$row = preg_replace( "@\s{2,}@", "\t", $row );
					print "row $i: $row\n";
				}
			}
		}
		$res = $dbw->query( $sql, __METHOD__ );
		if ( !$res ) {
			throw new ErrorException( "Error insert into titus: " . $dbw->lastError() );
		}
	}

	/*
	 * Get the connection for titus db
	 */
	public function getTitusDB($type = 'write') {
		$handleType = 'titus' . $type;
		$user = $type == 'write' ? WH_DATABASE_MAINTENANCE_USER : WH_DATABASE_USER;
		$password = $type == 'write' ? WH_DATABASE_MAINTENANCE_PASSWORD : WH_DATABASE_PASSWORD;
		$dbname = self::getDBName();
		return $this->getDBHandle($handleType, $user, $password, $dbname);
	}

	private function getDBHandle($handleType, $user, $password, $dbname) {
		if ( !isset($this->db[$handleType]) || !$this->db[$handleType]->ping() ) {
			$this->db[$handleType] = DatabaseBase::factory( 'mysql' );
			$this->db[$handleType]->open( self::getDBHost(), $user, $password, $dbname );
		}
		return $this->db[$handleType];
	}

	// given an english page id get the target language page id, if it exists
	// $id - page id of en article
	// $lang - target language
	public function getLangPageId( $id, $lang ) {
		$db = $this->getTitusDB('read');
		$table = "titus_intl";
		$var = "ti_tl_{$lang}_id";
		$cond = "ti_language_code = 'en' and ti_page_id = $id";
		$res = $db->selectField( $table, $var, $cond, __METHOD__ );
		return $res;
	}

	/**
	 * Get connection to the current wiki database. Wiki-db connections are always read-only
	 */
	private function getWikiDB() {
		global $wgDBname;
		$handleType = 'wikiread';
		$user = WH_DATABASE_USER;
		$password = WH_DATABASE_PASSWORD;
		$dbname = $wgDBname;
		return $this->getDBHandle($handleType, $user, $password, $dbname);
	}

	public function performTitusQuery( $sql, $type, $method ) {
		$db = $this->getTitusDB($type);
		return $db->query( $sql, $method );
	}

	/*
	 * Store records currently queued in $this->dataBatchMulti
	 */
	private function flushDataBatchMulti() {
		while ( sizeof( $this->dataBatchMulti ) ) {
			$dataBatch = array_pop( $this->dataBatchMulti );
			$this->storeRecords( $dataBatch );
		}
	}

	/*
	 * Store records currently queued in $this->dataBatch
	 */
	private function flushDataBatch() {
		if ( !sizeof( $this->dataBatch ) ) {
			return;
		}

		// Flush out remaining records to database
		$this->storeRecords( $this->dataBatch );
		$this->dataBatch = array();
	}
}

/*
 * Returns configuration for TitusController represented by an associative array.   of stats available in the TitusDB that can be calculated
 * The key of each row represents a TitusStat that can be calculated and the value represents whether to calculate (1 for calc, 0 for don't calc)
 */
class TitusConfig {

	/*
	 * Get config for stats that we want to calculate on a nightly basis
	 */
	public static function getDailyEditStats() {
		$stats = self::getAllStats();
		// Social stats are slow to calc, so remove them from the calcs
		$stats['Social'] = 0;

		// Stu stats don't make sense to calculate on a page edit.  This should be done nightly via
		// across all pages
		$stats['Stu'] = 0;
		$stats['Stu2'] = 0;
		$stats['PageViews'] = 0;
		$stats['GooglebotViews'] = 0;

		return $stats;
	}

	public static function getAllStats() {
		global $wgLanguageCode;
		$stats = array (
			"PageId" => 1,
			"Timestamp" => 1,
			"LanguageCode" => 1,
			"Title" => 1,
			"Views" => 1,
			"NumEdits" => 1,
			"AltMethods" => 1,
			"ByteSize" => 1,
			"Helpful" => 1,
			"Stu" => 1,
			"Stu2" => 1,
			"PageViews" => 1,
			"GooglebotViews" => 1,
			"Intl" => 0,
			"Video" => 1,
			"Summarized" => 1,
			"EventLog" => 1,
			"ItemRatings" => 1,
			"FirstEdit" => 1,
			"LastEdit" => 1,
			"TopLevelCat" => 1,
			"ParentCat" => 1,
			"NumSteps" => 1,
			"NumTips" => 1,
			"NumWarnings" => 1,
			"NumSourcesCites" => 1,
			"RetranslationComplete" => 0,
			"Photos" => 1,
			"Featured" => 1,
			"RobotPolicy" => 1,
			"RisingStar" => 1,
			"Templates" => 1,
			"RushData" => 1,
			"Social" => 0,
			"Translations" => 1,
			"Sample" => 1,
			"RecentWikiphoto" => 1,
			"Top10k" => 1,
			"Ratings"=> 1,
			"LastFellowEdit" => 1,
			"EditingStatus" => 1,
			"LastFellowStubEdit" => 1,
			"Librarian" => 1,
			"LastPatrolledEditTimestamp" => 1,
			"BabelfishData" => 0,
			"NABPromoted" => 1,
			"NABDemoted" => 1,
			"NABScore" => 1,
			"WikiVideo" => 1,
			"PetaMetrics" => 0,
			"UCIImages" => 1,
			"RateTool" => 1,
			"KeywordRank" => 1,
			"ExpertVerified" => 1,
			"StaffReviewed" => 1,
			"ExpertVerifiedSince" => 1,
			"QuickSummaryCreated" => 1,
			"FKReadingEase" => 1,
			"EditFish" => 1,
			"ChocoFish" => 1,
			"Concierge" => 1,
			"QA" => 1,
			"UserReview" => 1,
			"MagicWords" => 1,
			"Quizzes" => 1,
			"SensitiveArticle" => 1,
			"SearchVolume" => 1,
			"InboundLinks" => 1,
			"GreenBox" => 1,
			"ActiveCoauthor" => 1,
		);

		if ( $wgLanguageCode != "en" ) {
			$stats["Stu"] = 0;
			$stats["Stu2"] = 0;
			$stats["RushData"] = 0;
			$stats["RecentWikiphoto"] = 0;
			$stats["Ratings"] = 0;
			$stats["LastFellowEdit"] = 0;
			$stats["LastFellowStubEdit"] = 0;
			$stats["Librarian"] = 0;
			$stats["BabelfishData"] = 1;
			$stats["PetaMetrics"] = 0;
			$stats["UCIImages"] = 0;
			$stats["RateTool"] = 0;
			$stats["ExpertVerified"] = 0;
			$stats["StaffReviewed"] = 0;
			$stats["ExpertVerifiedSince"] = 0;
			$stats["UserReview"] = 0;
			$stats["Quizzes"] = 0;
			$stats["SensitiveArticle"] = 0;
			$stats["SearchVolume"] = 0;
		}

		return $stats;
	}

	public static function getBasicStats() {
		$stats = array (
			"PageId" => 1,
			"LanguageCode" => 1,
			"Timestamp" => 1,
			"Title" => 1,
			);

		return $stats;
	}
}

/*
 * Abstract class representing a stat to be calculated by TitusDB
 */
abstract class TitusStat {
	// Abstract function that gets a list of page ids we want to calculate for this stat
	// @return Either an array of page ids, "all" to run through all pages, or "dailyedits"
	abstract function getPageIdsToCalc( $dbr, $date );

	// TODO: we should add a langCode reference in titus so that we don't need to
	// declare and use $wgLanguageCode everywhere.

	private $_error = false;

	const ERROR_MAX_SIZE = 1000;

	public function reportError( $msg ) {
		global $wgLanguageCode;
		print("Reporting error on " . $wgLanguageCode . "  : $msg\n");
		$this->storeError( $msg );
	}

	/* store the error for this stat
	 * $msg - the string corresponding to the error
	 * $limitMaxSize - this is useful for certain stats where many exceptions would be the same like in try/catch blocks for db errors
	 */
	public function storeError( $msg, $limitMaxSize = false ) {
		if (!$this->_error) {
			$this->_error = "";
		}

		// limit the number of lines in the error message
		if ( $limitMaxSize && strlen( $this->_error ) > self::ERROR_MAX_SIZE ) {
			return;
		}

		$this->_error .=  " " . $msg . "\n";
	}

	public function getErrors() {
		return $this->_error;
	}

	function checkForRedirects($dbr, $ids) {
		global $wgLanguageCode;

		if (!$ids) {
			$this->reportError('TitusStat::checkForRedirects was called with an empty $ids param. Backtrace: '.wfBacktrace());
			return;
		}

		// TODO: we should be using the MW Database wrapper for this
		$query = "SELECT page_id,page_title FROM " . Misc::getLangDB($wgLanguageCode) . ".page WHERE page_is_redirect=1 AND page_id IN (" . implode($ids,",") . ")";
		$res = $dbr->query($query,__METHOD__);
		$redirects = "";
		foreach ($res as $row) {
			if ($redirects == "") {
				$redirects = "The following pages are redirects:\n";
			}
			$redirects .= $this->getBaseUrl() . '/' . $row->page_title . "|" . $row->page_id . "\n\n";
		}
		if ($redirects) {
			$this->reportError($redirects);
		}
	}

	function checkForMissing($dbr, $ids) {
		global $wgLanguageCode;

		if (!$ids) {
			$this->reportError('TitusStat::checkForRedirects was called with an empty $ids param. Backtrace: '.wfBacktrace());
			return;
		}

		// TODO: we should be using the MW Database wrapper for this
		$query = "SELECT page_id FROM " . Misc::getLangDB($wgLanguageCode) . ".page WHERE page_id IN (" . implode($ids,",") . ")";
		$res = $dbr->query($query,__METHOD__);
		$foundIds = array();
		foreach ($res as $row) {
			$foundIds[] = $row->page_id;
		}
		$missing = array_diff($ids, $foundIds);
		if (sizeof($missing) > 0) {
			$error = "The following articles were not found and may have been deleted (" . implode($missing,',') . ")";
			$this->reportError($error);
		}
	}

	// Abstract function that returns calculated stats.  IMPORTANT: All status must be returned with a
	// default value or batch insertion of records will break
	abstract function calc( $dbr, $r, $t, $pageRow );

	function getBaseUrl() {
		global $wgLanguageCode;
		if ($wgLanguageCode == "en") {
			return "http://www.wikihow.com";
		}
		else {
			return Misc::getLangBaseURL($wgLanguageCode);
		}
	}
	/**
	 * Remove the templates
	 */
	function removeTemplates() {
		return true;
	}

	public function cleanText($txt) {
		// Remove images
		$txt = preg_replace( "@\[\[image:[^\]]+\]\]@i","", $txt);
		// Remove templates
		$txt = preg_replace( "@{{[^}]+}}@","",$txt);
		// Remove bold triple-single quotes
		$txt = preg_replace( "@'''@","", $txt);
		// Remove wikilinks
		$txt = preg_replace( "@\[\[[^|\]]+\|([^\]]+)\]\]@", "$1", $txt);
		$txt = preg_replace( "@\[[^\]]+\]+@", "", $txt);
		$txt = preg_replace( "@<[^>]+>@", "", $txt);
		$txt = htmlspecialchars_decode($txt);
		return $txt;
	}

	protected function fixDatePart( $part ) {
		if ( $part < 10 ) {
			return '0' . $part;
		} else {
			return $part;
		}
	}

	protected function fixDate( $d ) {
		if ( is_numeric( $d ) && sizeof( $d ) == 14 ) {
			return substr( $d, 0, 8 );
		} else {
			$p = date_parse( $d );
			if ( isset( $p['year'] ) && isset( $p['month'] ) && isset( $p['day'] ) ) {
				return $p['year'] . $this->fixDatePart( $p['month'] ) . $this->fixDatePart( $p['day'] );
			} else {
				return NULL;
			}
		}
	}
}

/*
 * Determines whether or not it is an EditFish article
 * alter table titus_intl add column ti_is_editfish_complete varchar(255) NOT NULL default '';
 * TODO combine this with ti_editing_status since they both query from editfish_articles table
 */
class TSEditFish extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		// get the table name we will use to get ids from
		$table = Misc::getLangDB($wgLanguageCode) . '.editfish_articles';

		// make sure this table exists in this language
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}

		// get ids from this table
		$res = $dbr->select( $table, 'ct_page_id', '', __METHOD__ );
		$pageIds = array();
		foreach ( $res as $row ) {
			$pageIds[] = $row->ct_page_id;
		}

		$table = array( 'wikidb_112.editfish_articles', 'titusdb2.titus_intl' );
		$vars = 'ti_page_id';
		$conds = array(
			"ti_language_code" => $wgLanguageCode,
			"ti_is_editfish_complete" => array('incomplete', 'complete'),
			"editfish_articles.ct_page_id" => null
		);
		$options = array();
		$join_conds = array( 'wikidb_112.editfish_articles' => array( 'LEFT JOIN', 'ti_page_id = editfish_articles.ct_page_id' ) );
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options, $join_conds );

		foreach ( $res as $row ) {
			$pageIds[] = $row->ti_page_id;
		}

		return $pageIds;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$table = 'editfish_articles';

		// because in some languages this table  does not exist and this will cause
		// a database error, check to see if it exists before using it
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}

		$res = $dbr->select(
			$table,
			array( 'ct_completed' ),
			array( 'ct_page_id' => $pageRow->page_id )
			,__METHOD__
		);

		$row = $dbr->fetchObject( $res );

		if ( $row == false ) {
			$status = '';
		} else {
			if ( $row->ct_completed ) {
				$status = 'complete';
			} else {
				$status = 'incomplete';
			}
		}

		return array( 'ti_is_editfish_complete' => $status );
	}
}

/*
 * Determines whether or not it is an ChocoFish article
 * alter table titus_intl add column ti_is_chocofish_complete varchar(255) NOT NULL default '';
 */
class TSChocoFish extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		// get the table name we will use to get ids from
		$table = Misc::getLangDB($wgLanguageCode) . '.chocofish_articles';

		// make sure this table exists in this language
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}

		// get ids from this table
		$res = $dbr->select( $table, 'ct_page_id', '', __METHOD__ );
		$pageIds = array();
		foreach ( $res as $row ) {
			$pageIds[] = $row->ct_page_id;
		}

		$table = array( 'wikidb_112.chocofish_articles', 'titusdb2.titus_intl' );
		$vars = 'ti_page_id';
		$conds = array(
			"ti_language_code" => $wgLanguageCode,
			"ti_is_chocofish_complete" => array('incomplete', 'complete'),
			"chocofish_articles.ct_page_id" => null
		);
		$options = array();
		$join_conds = array( 'wikidb_112.chocofish_articles' => array( 'LEFT JOIN', 'ti_page_id = chocofish_articles.ct_page_id' ) );
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options, $join_conds );

		foreach ( $res as $row ) {
			$pageIds[] = $row->ti_page_id;
		}

		return $pageIds;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$table = 'chocofish_articles';

		// because in some languages this table  does not exist and this will cause
		// a database error, check to see if it exists before using it
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}

		$res = $dbr->select(
			$table,
			array( 'ct_completed' ),
			array( 'ct_page_id' => $pageRow->page_id ),
			__METHOD__
		);

		$row = $dbr->fetchObject($res);

		if ( $row == false ) {
			$status = '';
		} else {
			if ( $row->ct_completed ) {
				$status = 'complete';
			} else {
				$status = 'incomplete';
			}
		}

		return array( 'ti_is_chocofish_complete' => $status );
	}
}

/*
 * Determines whether or not it is a Concierge article
 * alter table titus_intl add column ti_is_concierge_complete varchar(255) NOT NULL default '';
 */
class TSConcierge extends TitusStat {
	// for this stat we want to run on all page ids unless the table it uses does not exist
	// in which case we just return an empty array
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		// get the table name we will use to get ids from
		$table = Misc::getLangDB($wgLanguageCode) . '.concierge_articles';

		// make sure this table exists in this language
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}

		// get ids from this table
		$res = $dbr->select( $table, 'ct_page_id', '', __METHOD__ );
		$pageIds = array();
		foreach ( $res as $row ) {
			$pageIds[] = $row->ct_page_id;
		}

		$table = array( 'wikidb_112.concierge_articles', 'titusdb2.titus_intl' );
		$vars = 'ti_page_id';
		$conds = array(
			"ti_language_code" => $wgLanguageCode,
			"ti_is_concierge_complete" => array('incomplete', 'complete'),
			"concierge_articles.ct_page_id" => null
		);
		$options = array();
		$join_conds = array( 'wikidb_112.concierge_articles' => array( 'LEFT JOIN', 'ti_page_id = concierge_articles.ct_page_id' ) );
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options, $join_conds );

		foreach ( $res as $row ) {
			$pageIds[] = $row->ti_page_id;
		}

		return $pageIds;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$table = 'concierge_articles';

		// because in some languages this table  does not exist and this will cause
		// a database error, check to see if it exists before using it
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}

		$res = $dbr->select(
			$table,
			array( 'ct_completed' ),
			array( 'ct_page_id' => $pageRow->page_id ),
			__METHOD__
		);
		$row = $dbr->fetchObject($res);

		if ( $row == false ) {
			$status = '';
		} else {
			if ( $row->ct_completed ) {
				$status = 'complete';
			} else {
				$status = 'incomplete';
			}
		}
		return array( 'ti_is_concierge_complete' => $status );
	}
}

/*
 * Provides stats on whether es, pt or de articles have been created for this article
 */
class TSIntl extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$stats = array("ti_langs" => "");
		$langs = implode("|", explode("\n", trim(wfMessage('titus_langs'))));
		if (preg_match_all("@\[\[($langs):@i", $txt, $matches)) {
			$matches = $matches[1];
			$stats["ti_langs"] = strtolower(implode(",", $matches));
		}

		return $stats;
	}
}

/*
 * Provides top level category for Article
 */
class TSTopLevelCat extends TitusStat {

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgCategoryNames;
		$topCats = array();
		$topCatDefault = "";
		$catMask = $pageRow->page_catinfo;
		if ( $catMask ) {
			foreach ( $wgCategoryNames as $bit => $cat ) {
				if ( $bit & $catMask ) {
					if ( $cat === "WikiHow" ) {
						$topCatDefault = $dbr->strencode( $cat );
					} else {
						$topCats[] = $dbr->strencode( $cat );
					}
				}
			}
		}

		$topCat = implode( ',', $topCats );
		if ( !$topCat && $topCatDefault != "" ) {
			$topCat = $topCatDefault;
		}

		return array( 'ti_top_cat' => $topCat );
	}
}

/*
 * Provides parent category for article
 */
class TSParentCat extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgContLang;
		$text = ContentHandler::getContentText( $r->getContent() );
		$parentCat = "";
		if (preg_match("/\[\[(?:" . $wgContLang->getNSText(NS_CATEGORY) . "|Category):([^\]]*)\]\]/im", $text, $matches)) {
			$parentCat = $dbr->strencode(trim($matches[1]));
		}
		return array('ti_cat' => $parentCat);
	}
}
/*
 * Language of this wiki
 */
class TSLanguageCode extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
	 global $wgLanguageCode;
   return array("ti_language_code" => $wgLanguageCode);
  }
}

/*
 * Number of views for an article
 */
class TSViews extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return array("ti_views" => $pageRow->page_counter);
	}
}

/*
 * Title of an article
 */
class TSTitle extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return array("ti_page_title" => $dbr->strencode($pageRow->page_title));
	}
}

/*
 * Page id of an article
 */
class TSPageId extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return array("ti_page_id" => $dbr->strencode($pageRow->page_id));
	}
}


/*
 * Number of bytes in in an article
 */
class TSByteSize extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$byteSize = $r->getSize();
		if (is_null($byteSize)) {
			$byteSize = strlen(ContentHandler::getContentText( $r->getContent() ));
		}
		return array("ti_bytes" => $byteSize);
	}
}

/*
 * Date of first edit
 */
class TSFirstEdit extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;
		$ret =	array(TitusDB::DAILY_EDIT_IDS => 1);

		// in addition to daily edit ids, select pages which have no daily edit data
		$table = "titus_intl";
		$var = "ti_page_id";
		$cond = array( "ti_language_code"  => $wgLanguageCode, "ti_first_edit_timestamp" => "" );
		$res = $dbr->select( $table, $var, $cond, __METHOD__ );

		foreach ( $res as $row ) {
			$ret[] = $row->ti_page_id;
		}

		return $ret;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$stats = array("ti_first_edit_timestamp" => "", "ti_first_edit_author" => "");
		$res = $dbr->select('firstedit', array('fe_timestamp', 'fe_user_text'), array('fe_page' => $pageRow->page_id), __METHOD__);
		if ($row = $dbr->fetchObject($res)) {
			$stats['ti_first_edit_timestamp'] = $row->fe_timestamp;
			$stats['ti_first_edit_author'] = $dbr->strencode($row->fe_user_text);
		}

		$revisionRow = null;
		if ( $stats['ti_first_edit_timestamp'] == "" )  {
			$revisionRow = $this->getOldestRevisionRow( $dbr, $pageRow->page_id );
			$stats['ti_first_edit_timestamp'] = $revisionRow->rev_timestamp;
			$this->reportError( "firstedit table has no data for page {$pageRow->page_id}" );
		}

		if ( $stats['ti_first_edit_author'] == "" ) {
			if ( $revisionRow == null ) {
				$revisionRow = $this->getOldestRevisionRow( $dbr, $pageRow->page_id );
			}
			$stats['ti_first_edit_author'] = $revisionRow->rev_user_text;
		}

		return $stats;
	}

	// looks in revision table for first editor and first edit time
	// used as a backup if this data is not in the firstedit table
	private function getOldestRevisionRow( $dbr, $pageId ) {
		$row = $dbr->selectRow(
			array( 'revision' ),
			array( 'rev_timestamp', 'rev_user_text' ),
			array( 'rev_page' => $pageId ),
			__METHOD__,
			array( 'ORDER BY' => 'rev_id ASC' )
		);
		return $row;
	}
}

/*
 * Total number of edits to an article
 */
class TSNumEdits extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}
	public function calc( $dbr, $r, $t, $pageRow ) {
		return array("ti_num_edits" =>
			$dbr->selectField('revision', array('count(*)'), array('rev_page' => $pageRow->page_id)));
	}
}

/*
 * Determines whether this article has been completed in retranslatefish
 */
class TSRetranslationComplete extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		$ts = wfTimestamp(TS_UNIX, $date);
		$lowerTS = wfTimestamp(TS_MW, strtotime('-2 day', strtotime(date('Ymd', $ts))));

		$rtfArticleTable = 'wikidb_112.retranslatefish_articles';

		$titusTable = 'titusdb2.titus_intl';

		$res = $dbr->select(
			array(
				'ra' => $rtfArticleTable,
				't_en' => $titusTable,
				't_tl' => $titusTable
			),
			array(
				'tl_page_id' => 't_tl.ti_page_id'
			),
			array(
				'ra.ct_lang_code' => $wgLanguageCode,
				't_en.ti_language_code' => 'en',
				't_tl.ti_language_code' => $wgLanguageCode,
				'ra.ct_completed' => 1,
				'ra.ct_completed_timestamp >= ' . $dbr->addQuotes($lowerTS)
			),
			__METHOD__,
			array(),
			array(
				't_en' => array(
					'INNER JOIN',
					array(
						't_en.ti_page_id = ra.ct_page_id'
					)
				),
				't_tl' => array(
					'INNER JOIN',
					array(
						't_tl.ti_page_id = t_en.'
						. $dbr->addIdentifierQuotes('ti_tl_' . $wgLanguageCode . '_id'),
						't_tl.ti_last_retranslation < ra.ct_completed_timestamp'
					)
				)
			)
		);

		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->tl_page_id;
		}

		return $ids;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		$rtfArticleTable = 'wikidb_112.retranslatefish_articles';

		$titusTable = 'titusdb2.titus_intl';

		if ($wgLanguageCode != 'en') {
			// This query only makes sense for international
			$lastCompletionRow = $dbr->selectRow(
				array(
					'ra' => $rtfArticleTable,
					't_en' => $titusTable
				),
				array(
					'completed_timestamp' => 'ra.ct_completed_timestamp',
					'en_id' => 't_en.ti_page_id'
				),
				array(
					'ra.ct_lang_code' => $wgLanguageCode,
					't_en.ti_language_code' => 'en',
					't_en.' . $dbr->addIdentifierQuotes('ti_tl_' . $wgLanguageCode . '_id') => $pageRow->page_id
				),
				__METHOD__,
				array('LIMIT' => 1),
				array(
					't_en' => array(
						'INNER JOIN',
						array(
							'ra.ct_page_id = t_en.ti_page_id'
						)
					)
				)
			);
		} else {
			$lastCompletionRow = false;
		}

		$lastCompletion = $lastCompletionRow === false ? 0 : $lastCompletionRow->completed_timestamp;

		$lastRetranslation = self::getLastRetranslation($dbr, $pageRow);

		$stat = max($lastCompletion, $lastRetranslation);

		if (!$stat) {
			$enaid = $lastCompletionRow === false ? 0 : $lastCompletionRow->en_id;
			$this->reportError("RetranslationComplete: No data found for ti_last_retranslation for page {$pageRow->page_id} (EN aid: $enaid)");
		}

		return array('ti_last_retranslation' => $stat);
	}

	// Used to ensure that Retranslatefish completions don't regress
	public static function getLastRetranslation($dbr, $pageRow) {
		global $wgLanguageCode;

		$lastRetranslation = $dbr->selectField(
			'titusdb2.titus_intl',
			array('ti_last_retranslation'),
			array(
				'ti_page_id' => $pageRow->page_id,
				'ti_language_code' => $wgLanguageCode
			),
			__METHOD__,
			array(
				'LIMIT' => 1
			)
		);

		return $lastRetranslation === false ? 0 : $lastRetranslation;
	}
}

/*
 * Date of last edit to this article
 */
class TSLastEdit extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return array("ti_last_edit_timestamp" =>
			$dbr->selectField('revision',
				array('rev_timestamp'),
				array('rev_page' => $pageRow->page_id),
				__METHOD__,
				array('ORDER BY' => 'rev_id DESC', 'LIMIT' => '1'))
		);
	}
}

/*
 * Number of alternate methods in the article
 */
class TSAltMethods extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		$ret =	array(TitusDB::DAILY_EDIT_IDS => 1);
		return $ret;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		// first remove any ingredients section since this will mess up the count..
		// remove between ingredients and steps
		$txt = ContentHandler::getContentText( $r->getContent() );
		$stepsSection = Wikitext::getStepsSection( $txt, true );
		$altMethods = intVal(preg_match_all("@^===@m", trim( $stepsSection[0] ), $matches));

		return array("ti_alt_methods" => $altMethods);
	}
}

/*
 * Whether the article has a video
 */
class TSVideo extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		if (get_class($r) == "RevisionNoTemplateWrapper") {
			$txt = $r->getOrigText();
		} else {
			$txt = ContentHandler::getContentText( $r->getContent() );
		}

		$video = strpos($txt, "{{Video") ? 1 : 0;
		return array("ti_video" => $video);
	}
}

/*
 * Whether the article has a summary video or summary section of any kind
 * ALTER TABLE titus_intl add column `ti_summary_video` tinyint(1) unsigned NOT NULL DEFAULT '0' after ti_video;
 * ALTER TABLE titus_intl add column `ti_summarized` tinyint(1) unsigned NOT NULL DEFAULT '0' after ti_templates;
 */
class TSSummarized extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$video = false;
		$summary_position = '';

		$text = Wikitext::getSummarizedSection( ContentHandler::getContentText( $r->getContent() ) );

		if (!empty($text)) {
			if ( strpos( $text, '{{whvid' ) !== false ) {
				$video = true;
			}

			$summary_data = SummarySection::summaryData($t->getText());
			$summary_position = $summary_data['at_top'] ? 'top' : 'bottom';
		}

		$result = array(
			'ti_summary_video' => $video ? 1 : 0,
			'ti_summarized' => $text ? 1 : 0,
			'ti_summary_position' => $summary_position
		);

		return $result;
	}
}

/*
 * data from the event_log table
 * ALTER TABLE titus_intl add column `ti_summary_video_views` int(10) unsigned NOT NULL DEFAULT '0' after ti_summary_video;
 * ALTER TABLE titus_intl add column `ti_summary_video_play` int(10) unsigned NOT NULL DEFAULT '0' after ti_summary_video_views;
 * ALTER TABLE titus_intl add column `ti_summary_video_ctr` tinyint(3) unsigned NOT NULL DEFAULT '0' after ti_summary_video_play;
 * ALTER TABLE titus_intl add column `ti_summary_video_views_mobile` int(10) unsigned NOT NULL DEFAULT '0' after ti_summary_video_ctr;
 * ALTER TABLE titus_intl add column `ti_summary_video_play_mobile` int(10) unsigned NOT NULL DEFAULT '0' after ti_summary_video_views_mobile;
 * ALTER TABLE titus_intl add column `ti_summary_video_ctr_mobile` tinyint(3) unsigned NOT NULL DEFAULT '0' after ti_summary_video_play_mobile;
 */
class TSEventLog extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		// get the table name we will use to get ids from
		$table = Misc::getLangDB( $wgLanguageCode ) . '.event_log';

		// make sure this table exists in this language
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}
		$options = array( 'DISTINCT' );
		$res = $dbr->select( $table, 'el_page_id', '', __METHOD__, $options );
		$pageIds = array();
		foreach ( $res as $row ) {
			$pageIds[] = $row->el_page_id;
		}

		return $pageIds;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		$result = array(
			'ti_summary_video_views' => 0,
			'ti_summary_video_play' => 0,
			'ti_summary_video_ctr' => 0,
		);

		if ( $wgLanguageCode != 'en' ) {
			return $result;
		}

		$data = $this->getSummaryVideoEventData( $dbr, $pageRow->page_id );
		$result = array_merge( $result, $data );

		return $result;
	}

	private function getSummaryVideoEventData( $dbr, $pageId ) {
		global $wgLanguageCode;
		global $wgIsDevServer;
		$result = array();

		$desktopDomain = wfCanonicalDomain( $wgLanguageCode );
		$mobileDomain = wfCanonicalDomain( $wgLanguageCode, true );
		$domains = array( 'desktop' => wfCanonicalDomain( $wgLanguageCode ), 'mobile' => wfCanonicalDomain( $wgLanguageCode, true ) );

		foreach ( $domains as $domainKey => $domain ) {
			$columnNameSuffix = '';
			$result += array( 'ti_summary_video_views'.$columnNameSuffix => 0, 'ti_summary_video_play'.$columnNameSuffix => 0, 'ti_summary_video_ctr'.$columnNameSuffix => 0 );

			$table = 'event_log';
			$var = array( 'el_count', 'el_action' );
			$cond = array(
				'el_page_id' => $pageId,
			);
			if ( $domain ) {
				$cond['el_domain'] = $domain;
			}
			$res = $dbr->select( $table, $var, $cond, __METHOD__ );
			foreach ( $res as $row ) {
				if ( $row->el_action == 'svideoview' ) {
					$result['ti_summary_video_views'.$columnNameSuffix] = $result['ti_summary_video_views'.$columnNameSuffix] + $row->el_count;
				} elseif ( $row->el_action == 'svideoplay' ) {
					$result['ti_summary_video_play'.$columnNameSuffix] = $result['ti_summary_video_play'.$columnNameSuffix] + $row->el_count;
				}
			}
			if ( $result['ti_summary_video_views'.$columnNameSuffix] > 0 ) {
			   $result['ti_summary_video_ctr'.$columnNameSuffix] = $result['ti_summary_video_play'.$columnNameSuffix] / $result['ti_summary_video_views'.$columnNameSuffix] * 100;
			}
		}
		return $result;
	}
}

/*
 * Whether the article has been featured and when
 */
class TSFeatured extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$stats = [
			'ti_featured' => $pageRow->page_is_featured,
			'ti_featured_date' => ''
		];

		if ($pageRow->page_is_featured) {
			$stats['ti_featured_date'] = $this->featuredDate($dbr, $t);
		}

		return $stats;
	}

	private function featuredDate($dbr, $t) {
		$date = '';

		$wikitext = Wikitext::getWikitext($dbr, $t->getTalkPage());

		preg_match('/{{featured\|(.*?)}}/i',$wikitext,$m);

		if ($m && isset($m[1])) {
			$date = str_replace('-','/',$m[1]);
			$date = str_replace(',',' ',$date);
			$date = strtotime($date);

			if (!empty($date)) {
				$date = date('Y-m-d', $date);
			}
		}

		return $date;
	}
}

/*
 *  Whether the article has a bad template
 */
class TSTemplates extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );

		$badTemplates = implode("|", explode("\n", trim(wfMessage('titus_bad_templates'))));
		$hasBadTemp = preg_match("@{{($badTemplates)[}|]@mi", $txt) == 1 ? 1 : 0;

		$badTransTemplates = implode("|", explode("\n", trim(wfMessage('titus_bad_translate_templates'))));
		$hasBadTransTemp = preg_match("@{{($badTransTemplates)[}|]@mi", $txt) == 1 ? 1 : 0;

		$templates = array();
		$articleTemplates = implode("|", explode("\n", trim(wfMessage('titus_templates'))));

		if (preg_match_all("@{{($articleTemplates)[}|]@mi", $txt, $matches)) {
			$templates = $matches[1];
		}

		$templates = sizeof($templates) ? $dbr->strencode(implode(",", $templates)) : '';

		return array("ti_bad_template" => intVal($hasBadTemp), 'ti_templates' => $templates, "ti_bad_template_translation" => intVal($hasBadTransTemp) );
	}
}

/*
 * Tracks if certain magic words are in the wikitext.
 * It uses these 2 columns:
 * ALTER TABLE titus_intl add column `ti_parts` tinyint(1) unsigned NOT NULL DEFAULT '0' after ti_templates;
 * ALTER TABLE titus_intl add column `ti_methods` tinyint(1) unsigned NOT NULL DEFAULT '0' after ti_templates;
 */
class TSMagicWords extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$result = array();

		$text = ContentHandler::getContentText( $r->getContent() );

		$result['ti_parts'] = preg_match( "@__parts?__@mi", $text ) ? 1 : 0;
		$result['ti_methods'] = preg_match( "@__methods?__@mi", $text ) ? 1 : 0;

		return $result;
	}
}

/*
 * Number of steps (including alt methods) in the article
 */
class TSNumSteps extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$wikiText = ContentHandler::getContentText( $r->getContent() );
		$text = Wikitext::getStepsSection( $wikiText, true );
		$text = $text[0];
		if ( !$text ) {
			//try to get steps section with all caps
			$stepsMsg = strtoupper( wfMessage( 'steps' )->inContentLanguage()->text() );
			$text = Wikitext::getSection( $wikiText, $stepsMsg, true );
			$text = $text[0];
		}
		$numSteps = 0;
		if ( $text ) {
			$numSteps = preg_match_all( '/^\#[^*^#]/im', $text, $matches );
		}
		// if no steps but we have an ol then try to count li inside it
		if ( $numSteps == 0 && strstr( $text, '<ol>' ) ) {
			$doc = phpQuery::newDocument( $text );
			$numSteps = pq('ol > li')->length;
		}
		$result = array( "ti_num_steps" => intVal( $numSteps ) );
		return $result;
	}
}

/*
 *  Number of tips in the article
 */
class TSNumTips extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = Wikitext::getSection(ContentHandler::getContentText( $r->getContent() ), wfMessage('tips'), true);
		$text = $text[0];
		if ($text) {
			$num_tips = preg_match_all('/^\*[^\*]/im', $text, $matches);
		}
		else {
			$num_tips = 0;
		}
		return array("ti_num_tips" => intVal($num_tips));
	}
}

/*
 * Number of warnings in the article
 */
class TSNumWarnings extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = Wikitext::getSection(ContentHandler::getContentText( $r->getContent() ), wfMessage('warnings'), true);
		$text = $text[0];
		$num_warnings = 0;
		if ($text) {
			$num_warnings = preg_match_all('/^\*[^\*]/im', $text, $matches);
		}
		return array("ti_num_warnings" => intVal($num_warnings));
	}
}

/*
 * Number of references in the article
 */
class TSNumSourcesCites extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = Wikitext::getSection(ContentHandler::getContentText( $r->getContent() ), wfMessage('sources'), true);
		$text = $text[0];
		$num_sac = 0;
		$num_sources = 0;

		// support a References section
		if ( !$text ) {
			$text = Wikitext::getSection(ContentHandler::getContentText( $r->getContent() ), wfMessage( 'references' ), true);
			$text = $text[0];
		}
		if ($text) {
			//sources
			$num_sources = preg_match_all('/^\*[^\*]/im', $text, $matches);
		}
		//citations
		$num_cites = preg_match_all('/<ref>/im', ContentHandler::getContentText( $r->getContent() ), $matches);

		//combine to form Voltron! ...I mean sources and citations
		$num_sac = $num_sources + $num_cites;
		return array("ti_num_sources_cites" => intVal($num_sac));
	}
}

/*
 * Helpful percentage, number of votes, and last reset date to accuracy
 *
 * additional rows added to track the all time helpfulness - including deleted ratings
 * alter table titus_intl add column `ti_helpful_percentage_including_deleted` tinyint(3) unsigned DEFAULT NULL after `ti_templates`;
 * alter table titus_intl add column `ti_helpful_total_including_deleted` int(10) unsigned DEFAULT NULL after `ti_templates`;
 * alter table titus_intl add column `ti_helpful_percentage_display_all_time` tinyint(3) unsigned DEFAULT NULL after `ti_helpful_total_including_deleted`;
 * alter table titus_intl add column `ti_helpful_percentage_display_soft_reset` tinyint(3) unsigned DEFAULT NULL after `ti_helpful_percentage_display_all_time`;
 * alter table titus_intl add column `ti_helpful_total_display_all_time` int(10) unsigned DEFAULT NULL after `ti_helpful_percentage_display_soft_reset`;
 * alter table titus_intl add column `ti_helpful_1_star` int(10) unsigned DEFAULT NULL after `ti_templates`;
 * alter table titus_intl add column `ti_helpful_2_star` int(10) unsigned DEFAULT NULL after `ti_helpful_1_star`;
 * alter table titus_intl add column `ti_helpful_3_star` int(10) unsigned DEFAULT NULL after `ti_helpful_2_star`;
 * alter table titus_intl add column `ti_helpful_4_star` int(10) unsigned DEFAULT NULL after `ti_helpful_3_star`;
 * alter table titus_intl add column `ti_helpful_5_star` int(10) unsigned DEFAULT NULL after `ti_helpful_4_star`;
 * alter table titus_intl add column `ti_display_stars` tinyint(3) unsigned DEFAULT NULL after `ti_helpful_5_star`;
 */
class TSHelpful extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$stats = array();
		$pageId = $pageRow->page_id;
		// TODO make this come from not in our code
		$displayResetDate = '2018-01-01';
		$sql = "
			select count(*) as C from rating where rat_page = $pageId and rat_rating = 1 and rat_isdeleted = 0
			UNION ALL
			select count(*) as C from rating  where rat_page  = $pageId and (rat_rating = 0 OR rat_rating=1) and rat_isdeleted = 0
			UNION ALL
			select count(*) as C from rating  where rat_page  = $pageId and rat_rating = 1 and rat_isdeleted = 0 and rat_source = 'mobile'
			UNION ALL
			select count(*) as C from rating  where rat_page  = $pageId and (rat_rating = 0 OR rat_rating=1) and rat_isdeleted = 0 and rat_source = 'mobile'
			UNION ALL
			select count(*) as C from rating  where rat_page  = $pageId and rat_rating = 1 and rat_isdeleted = 0 and rat_source = 'desktop'
			UNION ALL
			select count(*) as C from rating  where rat_page  = $pageId and (rat_rating = 0 OR rat_rating=1) and rat_isdeleted = 0 and rat_source = 'desktop'
			UNION ALL
			select max(rat_deleted_when) as C from rating where rat_page = $pageId
			UNION ALL
			select count(*) as C from rating  where rat_page  = $pageId
			UNION ALL
			select count(*) as C from rating  where rat_page  = $pageId and rat_rating = 1
			UNION ALL
			select count(*) as C from rating where rat_page = $pageId and rat_timestamp > '$displayResetDate'
			UNION ALL
			select count(*) as C from rating where rat_page = $pageId and rat_rating = 1 and rat_timestamp > '$displayResetDate'";

		$res = $dbr->query($sql);
		$row = $dbr->fetchObject($res);

		$accurate = intVal($row->C);
		$row = $dbr->fetchObject($res);
		$total = intVal($row->C);
		$stats['ti_helpful_percentage'] = $this->percent($accurate, $total);
		$stats['ti_helpful_total'] = $total;

		$row = $dbr->fetchObject($res);
		$mobileYesVote = intVal($row->C);
		$row = $dbr->fetchObject($res);
		$mobileTotal = intVal($row->C);
		$row = $dbr->fetchObject($res);
		$desktopYesVote = intVal($row->C);
		$row = $dbr->fetchObject($res);
		$desktopTotal = intVal($row->C);

		$stats['ti_helpful_mobile'] = $mobileTotal;
		$stats['ti_helpful_percentage_mobile'] = $this->percent($mobileYesVote, $mobileTotal);
		$stats['ti_helpful_desktop'] = $desktopTotal;
		$stats['ti_helpful_percentage_desktop'] = $this->percent($desktopYesVote, $desktopTotal);

		$row = $dbr->fetchObject($res);
		$lastReset = $row->C;
		$stats['ti_helpful_last_reset_timestamp'] = "";
		if (!is_null($lastReset) && '0000-00-00 00:00:00' != $lastReset) {
			$stats['ti_helpful_last_reset_timestamp'] = wfTimestamp(TS_MW, strtotime($row->C));
		}

		// helpfulness count including deleted rows
		$row = $dbr->fetchObject($res);
		$totalIncludingDeleted = intVal($row->C);

		// helpfulness yes votes including deleted rows
		$row = $dbr->fetchObject($res);
		$yesIncludingDeleted = intVal($row->C);

		// all votes since a given date
		$row = $dbr->fetchObject($res);
		$ratingDisplayCountTotal = intVal($row->C);

		// all yes votes since a given date
		$row = $dbr->fetchObject($res);
		$ratingDisplayCountYesTotal = intVal($row->C);

		$stats['ti_helpful_total_including_deleted'] = $totalIncludingDeleted;
		$stats['ti_helpful_votes_per_1000_pv'] = $this->getRatingsPerPV( $dbr, $r, $t, $pageRow );

		if (class_exists('PageHelpfulness')) {
			$detail = PageHelpfulness::getRatingsDetail($pageId);
			$stats['ti_helpful_didnt_work'] = $detail[1];
			$stats['ti_helpful_cant_follow'] = $detail[2];
			$stats['ti_helpful_wrong_problem'] = $detail[3];
			$stats['ti_helpful_stupid'] = $detail[4];
		}

		$starVotes = array();
		$ratingStarTable = "rating_star";
		for ( $i = 1; $i  <= 5; $i++) {
			$cond = ['rs_page' => $pageId, 'rs_rating' => $i, 'rs_isdeleted' => 0];
			$count = $dbr->selectField( $ratingStarTable, 'count( *)', $cond);
			$starVotes[$i] = intVal($count);
			$stats["ti_helpful_{$i}_star"] = $starVotes[$i];
		}

		$ratingStarTable = "rating_star";
		$var = array( "sum(rs_rating)/5 as yesVotes", "count(*) as count" );
		$cond = array( "rs_page" => $pageId, "rs_isdeleted" => 0 );
		$row = $dbr->selectRow( $ratingStarTable, $var, $cond, __METHOD__ );
		$starYesVotes = $row ? $row->yesVotes : 0;
		$starRatingCount = $row ? $row->count : 0;
		$ratingCount = $total + $starRatingCount;
		$yesVotes = $accurate + $starYesVotes;
		$rating = 0;
		if ( $ratingCount > 0 ) {
			$rating = round( 100 * $yesVotes / $ratingCount );
		}
		$stats["ti_display_stars"] = $rating;

		$cond[] = "rs_timestamp > '$displayResetDate'";
		$row = $dbr->selectRow( $ratingStarTable, $var, $cond, __METHOD__ );
		$starDisplayCountTotal = $row ? $row->count : 0;
		$starDisplayCountYesTotal = $row ? $row->yesVotes : 0;

		$displayCountTotal = $ratingDisplayCountTotal + $starDisplayCountTotal;
		$displayCountYesTotal = $ratingDisplayCountYesTotal + $starDisplayCountYesTotal;
		$displayTotalPercent = $this->percent( $displayCountYesTotal, $displayCountTotal );

		$stats['ti_helpful_total_display_all_time'] = $displayCountTotal;
		$stats['ti_helpful_percentage_display_all_time'] = $displayTotalPercent;
		$stats['ti_helpful_percentage_display_soft_reset'] = $this->getPercentDisplaySoftReset( $displayTotalPercent, $rating, $ratingCount );

		return $stats;
	}

	function getRatingsPerPV( $dbr, $r, $t, $pageRow ) {
		$pageId = $pageRow->page_id;
		$pageViewsStat = new TSPageViews;
		$stats = $pageViewsStat->calc($dbr, $r, $t, $pageRow);
		$pv = $stats['ti_30day_views'];

		if ($pv < 1) {
			return 0;
		}

		$where = array("rat_page" => "$pageId", "rat_timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()");
		$ratings = $dbr->selectField('rating', 'count(*)', $where);
		$ratings = (float)$ratings;
		$pv = (float) $pv;

		if ($ratings <= 0.0) {
			return 0;
		}

		$result = $ratings * 1000 / $pv;
		return round($result, 3);
	}

	function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0);
	}

	function getPercentDisplaySoftReset( $displayTotalPercent, $currentPercent, $currentTotal ) {
		if ( $currentTotal >= 8 ) {
			return $currentPercent;
		} else {
			return $displayTotalPercent;
		}
	}
}

/*
 * alter table titus_intl add column `ti_summary_video_helpful_total` int(10) unsigned DEFAULT NULL after `ti_summary_video_ctr`;
 * alter table titus_intl add column `ti_summary_video_helpful_percentage` tinyint(3) unsigned DEFAULT NULL after `ti_summary_video_helpful_total`;
 * alter table titus_intl add column `ti_summary_text_helpful_total` int(10) unsigned DEFAULT NULL after `ti_summary_video_helpful_percentage`;
 * alter table titus_intl add column `ti_summary_text_helpful_percentage` tinyint(3) unsigned DEFAULT NULL after `ti_summary_text_helpful_total`;
 */
class TSItemRatings extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		// get the table name we will use to get ids from
		$table = Misc::getLangDB( $wgLanguageCode ) . '.item_rating';

		// make sure this table exists in this language
		if ( !$dbr->tableExists( $table ) ) {
			return array();
		}
		$options = array( 'DISTINCT' );
		$res = $dbr->select( $table, 'ir_page_id', '', __METHOD__, $options );
		$pageIds = array();
		foreach ( $res as $row ) {
			$pageIds[] = $row->ir_page_id;
		}

		return $pageIds;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;
		$result = array();
		$result['ti_summary_video_helpful_total'] = 0;
		$result['ti_summary_video_helpful_percentage'] = 0;
		$result['ti_summary_text_helpful_total'] = 0;
		$result['ti_summary_text_helpful_percentage'] = 0;

		if ( $wgLanguageCode != "en" ) {
			return $result;
		}

		$pageId = $pageRow->page_id;
		$table = 'item_rating';
		$vars = 'count(*)';

		// for now we do not care about the domain
		$domain = '';

		// summary video first
		$conds = array( 'ir_page_id' => $pageId );
		$type = 'summaryvideohelp';
		$date = PageHelpfulness::getClearEventDate( $pageId, $domain, $type, $dbr );
		$conds[] = "ir_timestamp > '$date'";
		$conds['ir_type'] = $type;
		$total = $dbr->selectField( $table, $vars, $conds, __METHOD__ );
		$yes = 0;
		$percent = 0;
		if ( $total > 0 ) {
			$conds[] = 'ir_rating > 0';
			$yes = $dbr->selectField( $table, $vars, $conds, __METHOD__ );
			$percent = $this->percent( $yes, $total );
		}
		$result['ti_summary_video_helpful_total'] = $total;
		$result['ti_summary_video_helpful_percentage'] = $percent;

		// now do summary text
		$conds = array( 'ir_page_id' => $pageId );
		$type = 'summarytexthelp';
		$date = PageHelpfulness::getClearEventDate( $pageId, $domain, $type, $dbr );
		$conds[] = "ir_timestamp > '$date'";
		$conds['ir_type'] = $type;
		$total = $dbr->selectField( $table, $vars, $conds, __METHOD__ );
		$yes = 0;
		$percent = 0;
		if ( $total > 0 ) {
			$conds[] = 'ir_rating > 0';
			$yes = $dbr->selectField( $table, $vars, $conds, __METHOD__ );
			$percent = $this->percent( $yes, $total );
		}
		$result['ti_summary_text_helpful_total'] = $total;
		$result['ti_summary_text_helpful_percentage'] = $percent;

		return $result;
	}


	private function percent($numerator, $denominator) {
		$percent = $denominator != 0 ? ($numerator / $denominator) * 100 : 0;
		return number_format($percent, 0);
	}
}

/*
 * Date of last update to Titus record
 */
class TSTimestamp extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return array("ti_timestamp" => wfTimestamp(TS_MW));
	}
}

/*
 * Whether the article is a rising star
 */
class TSRisingStar extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return array( "ti_risingstar" => RisingStar::isRisingStar( $pageRow->page_id, $dbr ) );
	}
}

/*
 * Number of wikiphotos, community photos and if the article has enlarged (> 499 px photos)
 */
class TSPhotos extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode, $wgContLang;

		$text = Wikitext::getSection(ContentHandler::getContentText( $r->getContent() ), wfMessage('steps'), true);
		$text = $text[0];
		$numPhotos = preg_match_all('/(?:\[\[ *Image|\{\{largeimage|\[\[' . $wgContLang->getNSText(NS_IMAGE) . ')/im', $text, $matches);

		$stats=array();
		$stats['ti_num_photos'] = $numPhotos;
		if ($wgLanguageCode == "en") {
			$numWikiPhotos = intVal($dbr->selectField(array('imagelinks','image'),'count(*)', array('il_from' => $pageRow->page_id, 'img_name = il_to', 'img_user_text' => array('Wikiphoto','Wikivisual'))));
			$stats = array_merge($stats, $this->getIntroPhotoStats($r));
			$stats['ti_num_wikiphotos'] = $numWikiPhotos;
			$stats['ti_enlarged_wikiphoto'] = intVal($this->hasEnlargedWikiPhotos($r));
			$stats['ti_num_community_photos'] = $numPhotos - $numWikiPhotos;
		}
		else {
			$stats['ti_enlarged_intro_photo'] = 0;
			$stats['ti_intro_photo'] = 0;
			$stats['ti_num_wikiphotos'] = 0;
			$stats['ti_enlarged_wikiphoto'] = 0;
			$stats['ti_num_community_photos'] = 0;
		}
		return $stats;
	}

	private function hasEnlargedWikiPhotos($r) {
		$enlargedWikiPhoto = 0;
		$text = Wikitext::getStepsSection(ContentHandler::getContentText( $r->getContent() ), true);
		$text = $text[0];
		if ($text) {
			// Photo is enlarged if it is great than 500px (and less than 9999px)
			$enlargedWikiPhoto = preg_match('/\|[5-9][\d]{2,3}px\]\]/im', $text);
		}
		return $enlargedWikiPhoto;
	}

	private function getIntroPhotoStats($r) {
		$text = Wikitext::getIntro(ContentHandler::getContentText( $r->getContent() ));
		$stats['ti_intro_photo'] = intVal(preg_match('/\[\[Image:/im', $text));
		// Photo is enlarged if it is great than 500px (and less than 9999px)
		$stats['ti_enlarged_intro_photo'] = intVal(preg_match('/\|[5-9][\d]{2,3}px\]\]/im', $text));
		return $stats;
	}
}

/**
 * Count number of wikivideos on the site
 */
class TSWikiVideo extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$stats = array();
		$text = Wikitext::getSection(ContentHandler::getContentText( $r->getContent() ), wfMessage('steps'), true);
		$text = $text[0];
		$num = preg_match_all("@\{\{ *whvid\|[^\}]+ *\}\}@",$text, $matches);
		$stats['ti_num_wikivideos'] = $num;
		return $stats;
	}
}

/*
 * Stu data (www and mobile) for article
 * alter table titus_intl add column `ti_stu_1day_views` int(10) NOT NULL DEFAULT '0' after `ti_stu_10s_percentage_mobile`;
 * alter table titus_intl add column `ti_stu_7day_views` int(10) unsigned NOT NULL DEFAULT '0' after `ti_stu_1day_views`;
 */
class TSStu extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;
		$stats = array(
			'ti_stu_10s_percentage_mobile' => 0,
			'ti_stu_views_mobile' => 0,
			'ti_stu_10s_percentage_www' => 0,
			'ti_stu_3min_percentage_www' => 0,
			'ti_stu_views_www' => 0,
			'ti_stu_1day_views' => 0,
			'ti_stu_7day_views' => 0
		);

		$domains = array('bt' => 'www', 'mb' => 'mobile');
		foreach ($domains as $domain => $label) {
			$query = "select * from stu.stu_dump where domain=" . $dbr->addQuotes($domain) . " AND page=" . $dbr->addQuotes($t->getDBKey());
			$res = $dbr->query($query);
			$rets = array();
			foreach ($res as $row) {
				$rets[$row->page][$row->k] = $row->v;
			}
			AdminBounceTests::cleanBounceData($rets);
			$stats = array_merge($stats, $this->extractStats($rets, $label));
		}
		// only calculate for english for now
		if ( $wgLanguageCode == "en" ) {
			// calculate 1 and 7 day stu numbers
			$stats = array_merge( $stats, $this->getDailyStu( $dbr, $t->getArticleID(), $stats['ti_stu_views_www'] ) );
		}
		return $stats;
	}

	private function getDailyStu( $dbr, $pageId, $totalViews ) {
		// get the last 7 days of stu data from historical
		$query = "select ti_stu_views_www as views from titusdb2.titus_historical_intl where ti_language_code = 'en' and ti_page_id=" . $pageId . " order by ti_datestamp DESC limit 7";
		$res = $dbr->query( $query, __METHOD__ );
		$views = array();
		foreach ( $res as $row ) {
			$views[] = $row->views;
		}

		if ( count( $views ) == 0 ) {
			$result = array( 'ti_stu_1day_views' => $totalViews );
			return $result;
		}

		$diffs = array();
		$diffs[] = $totalViews - $views[0];
		for ( $i = 0; $i < count( $views ) - 1; $i++ ) {
			// day minus prev days count
			$diffs[] = $views[$i] - $views[$i + 1];
		}

		// set the 1 day views (ok to be negative)
		$result = array( 'ti_stu_1day_views' => $diffs[0] );

		// now set the average views for the 7 days (ignoring negative numbers)
		$countedDays = 0;
		$total = 0;
		foreach ( $diffs as $diff ) {
			if ( $diff >= 0 ) {
				$countedDays++;
				$total += $diff;
			}
		}

		if ( $countedDays > 0 ) {
			$avg = intval( $total / $countedDays );
			$result['ti_stu_7day_views'] = $avg;
		}
		return $result;
	}

	protected function makeQuery($t, $domain = 'bt') {
		return array(
			'select' => '*',
			'from' => $domain,
			'pages' => array($t->getDBkey()),
		);
	}

	private function extractStats($data, $label) {
		$headers = array('0-10s', '3+m');
		$stats = array();
		foreach ($data as $page => $datum) {
			AdminBounceTests::computePercentagesForCSV($datum, '');
			if (isset($datum['0-10s'])) {
				$stats['ti_stu_10s_percentage_' . $label] = $datum['0-10s'];
			}

			if ($label != 'mobile' && isset($datum['3+m'])) {
				$stats['ti_stu_3min_percentage_' . $label] = $datum['3+m'];
			}

			if (isset($datum['__'])) {
				$stats['ti_stu_views_' . $label] = $datum['__'];
			}
			break; // should only be one record
		}
		return $stats;
	}
}

/*
 * Stu2 data (www and mobile) for article

   alter table titus_intl
     add column ti_stu2_3m_active_mobile  decimal(6,2) NOT NULL DEFAULT '0.0',
     add column ti_stu2_3m_active_desktop decimal(6,2) NOT NULL DEFAULT '0.0',
     add column ti_stu2_10s_active_mobile  decimal(6,2) NOT NULL DEFAULT '0.0',
     add column ti_stu2_10s_active_desktop decimal(6,2) NOT NULL DEFAULT '0.0',
     add column ti_stu2_activity_avg_mobile  decimal(6,2) NOT NULL DEFAULT '0.0',
     add column ti_stu2_activity_avg_desktop decimal(6,2) NOT NULL DEFAULT '0.0',
     add column ti_stu2_all_mobile  int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_all_desktop int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_search_mobile  int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_search_desktop int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_activity_count_mobile  int unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_activity_count_desktop int unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_quickbounce_mobile  int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_quickbounce_desktop int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_amp int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_last_reset varchar(8) NOT NULL DEFAULT '',
     add column ti_stu2_1day_views_mobile  int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_1day_views_desktop int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_7day_views_mobile  int(10) unsigned NOT NULL DEFAULT '0',
     add column ti_stu2_7day_views_desktop int(10) unsigned NOT NULL DEFAULT '0';
 */
class TSStu2 extends TitusStat {
	public function __construct() {
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		// only calculate for english for now
		if ( $wgLanguageCode != "en" ) {
			return $stats;
		}

		$res = $dbr->select( 'stu.stu_agg',
			'*',
			[ 'sa_pageid' => $t->getArticleID(),
			  'sa_lang' => $wgLanguageCode ],
			__METHOD__ );
		$rows = [];
		foreach ($res as $row) {
			$rows[] = (array)$row;
		}
		$statsStu2 = $this->extractStatsStu2($rows);

		$res = $dbr->select( 'stu.activity_agg',
			'*',
			[ 'aa_pageid' => $t->getArticleID(),
			  'aa_lang' => $wgLanguageCode ],
			__METHOD__ );
		$rows = [];
		foreach ($res as $row) {
			$rows[] = (array)$row;
		}
		$statsActivity = $this->extractStatsActivity($rows);

		$result = array_merge( $statsStu2, $statsActivity );

		// only calculate for English right now
		if ( $wgLanguageCode == "en" ) {
			// calculate 1 and 7 day difference of stu2 daily views
			$dailyViewAvgs = $this->getDailyViewDiffs( $dbr, $t->getArticleID(), $result );
			$result = array_merge( $result, $dailyViewAvgs );
		}

		return $result;
	}

	private function extractStatsStu2($rows) {
		$stats = [
			'ti_stu2_3m_active_mobile' => 0.0,
			'ti_stu2_3m_active_desktop' => 0.0,
			'ti_stu2_10s_active_mobile' => 0.0,
			'ti_stu2_10s_active_desktop' => 0.0,
			'ti_stu2_all_mobile' => 0,
			'ti_stu2_all_desktop' => 0,
			'ti_stu2_search_mobile' => 0,
			'ti_stu2_search_desktop' => 0,
			'ti_stu2_quickbounce_mobile' => 0,
			'ti_stu2_quickbounce_desktop' => 0,
			'ti_stu2_amp' => 0,
			'ti_stu2_last_reset' => '',
		];

		$mb = [];
		$dt = [];
		foreach ($rows as $row) {
			// there is a mobile and a desktop row for each article in stu_agg
			if ($row['sa_mobile'] == 't') {
				$mb = $row;
			} elseif ($row['sa_mobile'] == 'f') {
				$dt = $row;
			} else {
				$this->reportError( 'unexpected value received from stu_agg table! ' . print_r($row,true) );
			}

			if (!$stats['ti_stu2_last_reset']) {
				$stats['ti_stu2_last_reset'] = $row['sa_last_reset'];
			}
		}

		if ($mb) {
			if ($mb['sa_search']) {
				$active3m = 100 * ( $mb['sa_3m_active'] / $mb['sa_search'] );
				$active10s = 100 * ( $mb['sa_10s_active'] / $mb['sa_search'] );
			} else {
				$active3m = 0.0;
				$active10s = 0.0;
			}
			$stats['ti_stu2_3m_active_mobile'] = $active3m;
			$stats['ti_stu2_10s_active_mobile'] = $active10s;

			$stats['ti_stu2_all_mobile'] = $mb['sa_all'];
			$stats['ti_stu2_search_mobile'] = $mb['sa_search'];
			$stats['ti_stu2_quickbounce_mobile'] = $mb['sa_quick_bounce'];
			$stats['ti_stu2_amp'] = $mb['sa_amp'];
		}

		if ($dt) {
			if ($dt['sa_search']) {
				$active3m = 100 * ( $dt['sa_3m_active'] / $dt['sa_search'] );
				$active10s = 100 * ( $dt['sa_10s_active'] / $dt['sa_search'] );
			} else {
				$active3m = 0.0;
				$active10s = 0.0;
			}
			$stats['ti_stu2_3m_active_desktop'] = $active3m;
			$stats['ti_stu2_10s_active_desktop'] = $active10s;

			$stats['ti_stu2_all_desktop'] = $dt['sa_all'];
			$stats['ti_stu2_search_desktop'] = $dt['sa_search'];
			$stats['ti_stu2_quickbounce_desktop'] = $dt['sa_quick_bounce'];
		}

		return $stats;
	}

	private function extractStatsActivity($rows) {
		$stats = [
			'ti_stu2_activity_avg_mobile' => 0.0,
			'ti_stu2_activity_avg_desktop' => 0.0,
			'ti_stu2_activity_count_mobile' => 0,
			'ti_stu2_activity_count_desktop' => 0,
		];

		$mb = [];
		$dt = [];
		foreach ($rows as $row) {
			// there is a mobile and a desktop row for each article in activity_agg (like stu_agg)
			if ($row['aa_mobile'] == 't') {
				$mb = $row;
			} elseif ($row['aa_mobile'] == 'f') {
				$dt = $row;
			} else {
				$this->reportError( 'unexpected value received from activity_agg table! ' . print_r($row,true) );
			}
		}

		if ($mb) {
			$stats['ti_stu2_activity_avg_mobile'] = $mb['aa_a1_avg'];
			$stats['ti_stu2_activity_count_mobile'] = $mb['aa_total'];
		}

		if ($dt) {
			$stats['ti_stu2_activity_avg_desktop'] = $dt['aa_a1_avg'];
			$stats['ti_stu2_activity_count_desktop'] = $dt['aa_total'];
		}

		return $stats;
	}

	private function getDailyViewDiffs( $dbr, $pageId, $prevResults ) {
		$total_mb = $prevResults['ti_stu2_search_mobile'];
		$total_dt = $prevResults['ti_stu2_search_desktop'];

		// get the last 7 days of stu data from titus_historical_intl
		$query = "SELECT ti_stu2_search_mobile, ti_stu2_search_desktop
				  FROM titusdb2.titus_historical_intl
				  WHERE ti_language_code = 'en' AND ti_page_id={$pageId}
				  ORDER BY ti_datestamp DESC
				  LIMIT 7";
		$res = $dbr->query( $query, __METHOD__ );
		$views_mb = [];
		$views_dt = [];
		foreach ( $res as $row ) {
			$views_mb[] = $row->ti_stu2_search_mobile;
			$views_dt[] = $row->ti_stu2_search_desktop;
		}

		list($days_1_mb, $days_7_mb) = self::computeDiffs( $total_mb, $views_mb );
		list($days_1_dt, $days_7_dt) = self::computeDiffs( $total_dt, $views_dt );

		return [
			'ti_stu2_1day_views_mobile' => $days_1_mb,
			'ti_stu2_1day_views_desktop' => $days_1_dt,
			'ti_stu2_7day_views_mobile' => $days_7_mb,
			'ti_stu2_7day_views_desktop' => $days_7_dt,
		];
	}

	private static function computeDiffs($total, $views) {
		if ( count( $views ) == 0 ) {
			return [ $total, 0 ];
		}

		$diffs = [];
		$diffs[] = $total - $views[0];
		for ( $i = 0; $i < count( $views ) - 1; $i++ ) {
			// day minus prev days count
			$diffs[] = $views[$i] - $views[$i + 1];
		}

		// set the 1 day views (ok to be negative)
		$days_1 = $diffs[0];

		// now set the average views for the 7 days (ignoring negative numbers)
		$countedDays = 0;
		$sum = 0;
		foreach ( $diffs as $diff ) {
			if ( $diff >= 0 ) {
				$countedDays++;
				$sum += $diff;
			}
		}

		$days_7 = 0;
		if ( $countedDays > 0 ) {
			$avg = (int)round( $sum / $countedDays, 0 );
			$days_7 = $avg;
		}

		return [ $days_1, $days_7 ];
	}

}

/*
 * A stat used for testing that will throw a db exception on even numbered articles
 * by referencing a non existent table
 */
class TSFailEvenNumbers extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		// just return two random ids for testing
		return array( 2053, 88372 );
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$table = "page_failure_test";

		$pageId = $pageRow->page_id;

		if ( $pageId % 2 == 0 ) {
			$res = $dbr->select( $table, array( 'page_id' ), array(), __METHOD__ );
		}

		return array();
	}
}

/*
 * Article page views
 *
 * alter table titus_intl
 * add column `ti_daily_views_unique` int(10) unsigned NOT NULL DEFAULT '0' after ti_daily_views,
 * add column `ti_30day_views_unique` int(10) unsigned NOT NULL DEFAULT '0' after ti_30day_views;
 * alter table titus_intl add column `ti_daily_views_unique_mobile` int(10) unsigned NOT NULL DEFAULT '0' after ti_30day_views_unique;
 * alter table titus_intl add column `ti_30day_views_unique_mobile` int(10) unsigned NOT NULL DEFAULT '0' after ti_daily_views_unique_mobile;
 */
class TSPageViews extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		$stats = array(
			'ti_daily_views' => 0,
			'ti_30day_views' => 0,
			'ti_daily_views_unique' => 0,
			'ti_30day_views_unique' => 0,
			'ti_daily_views_unique_mobile' => 0,
			'ti_30day_views_unique_mobile' => 0,
		);
		$monthAgoString = date('Y-m-d', strtotime("-30 day", strtotime(date('Ymd', time()))));
		$yesterdayString = date('Y-m-d', strtotime("-1 day", strtotime(date('Ymd', time()))));

		$domain = wfCanonicalDomain($wgLanguageCode);
		$mobileDomain = wfCanonicalDomain($wgLanguageCode, true);
		if ($wgLanguageCode == "en") {
			$altDomain = AlternateDomain::getAlternateDomainForPage( $t->getArticleID() );
			if ($altDomain) {
				$domain = "www.".$altDomain;
				$mobileDomain = "m.".$altDomain;
			}
		}

		$stats['ti_domain'] = str_replace( 'www.', '', $domain );

		$quotedTitle = $dbr->addQuotes('/' . $pageRow->page_title);

		// Query parts

		$table = 'wiki_log.page_views';
		$fields = ['pv_t' => 'sum(pv_t)', 'pv_u' => 'sum(pv_u)'];

		$condPage = ['page' => '/' . $pageRow->page_title];
		$condDomainBoth = ['domain' => [$domain, $mobileDomain]];
		$condDomainMobile = ['domain' => $mobileDomain];
		$condMonthAgo = ['day >= ' . $dbr->addQuotes($monthAgoString)];
		$condYesterday = ['day' => $yesterdayString];

		// Desktop+mobile - monthly

		$conds = array_merge($condPage, $condDomainBoth, $condMonthAgo);
		$res = $dbr->select($table, $fields, $conds);
		if ($row = $res->fetchObject()) {
			if ($row->pv_t) $stats['ti_30day_views'] = $row->pv_t;
			if ($row->pv_u) $stats['ti_30day_views_unique'] = $row->pv_u;
		}

		// Desktop+mobile - daily

		$conds = array_merge($condPage, $condDomainBoth, $condYesterday);
		$res = $dbr->select($table, $fields, $conds);
		if ($row = $res->fetchObject()) {
			if ($row->pv_t) $stats['ti_daily_views'] = $row->pv_t;
			if ($row->pv_u) $stats['ti_daily_views_unique'] = $row->pv_u;
		}

		// Mobile - monthly

		$conds = array_merge($condPage, $condDomainMobile, $condMonthAgo);
		$res = $dbr->select($table, $fields, $conds);
		if ($row = $res->fetchObject()) {
			if ($row->pv_u) $stats['ti_30day_views_unique_mobile'] = $row->pv_u;
		}

		// Mobile - daily

		$conds = array_merge($condPage, $condDomainMobile, $condYesterday);
		$res = $dbr->select($table, $fields, $conds);
		if ($row = $res->fetchObject()) {
			if ($row->pv_u) $stats['ti_daily_views_unique_mobile'] = $row->pv_u;
		}

		return $stats;
	}
}

/*
 * Googlebot requests to wikiHow's service
 *
alter table titus_intl add column `ti_googlebot_7day_views_mobile` int(10) unsigned NOT NULL DEFAULT '0' after ti_30day_views_unique_mobile,
					   add column `ti_googlebot_7day_views_amp` int(10) unsigned NOT NULL DEFAULT '0' after ti_googlebot_7day_views_mobile,
					   add column `ti_googlebot_7day_views_desktop` int(10) unsigned NOT NULL DEFAULT '0' after ti_googlebot_7day_views_amp,
					   add column `ti_googlebot_7day_last_mobile` varchar(14) NOT NULL DEFAULT '' after ti_googlebot_7day_views_desktop,
					   add column `ti_googlebot_7day_last_amp` varchar(14) NOT NULL DEFAULT '' after ti_googlebot_7day_last_mobile,
					   add column `ti_googlebot_7day_last_desktop` varchar(14) NOT NULL DEFAULT '' after ti_googlebot_7day_last_amp
					   ;
 */
class TSGooglebotViews extends TitusStat {

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		$stats = array(
			'ti_googlebot_7day_views_mobile' => 0,
			'ti_googlebot_7day_views_amp' => 0,
			'ti_googlebot_7day_views_desktop' => 0,
			'ti_googlebot_7day_last_mobile' => '',
			'ti_googlebot_7day_last_amp' => '',
			'ti_googlebot_7day_last_desktop' => '',
		);

		$pageid = $t->getArticleID();

        $res = $dbr->select('botwatcher.botwatch_daily',
            ['MAX(bwd_last) AS last', 'SUM(bwd_count) AS hits', 'bwd_type'],
            ['bwd_lang' => $wgLanguageCode, 'bwd_pageid' => $pageid],
            __METHOD__,
            ['GROUP BY' => 'bwd_type']);

        foreach ($res as $row) {
			if ($row->bwd_type == 'mb') {
				$stats['ti_googlebot_7day_views_mobile'] = $row->hits;
				$stats['ti_googlebot_7day_last_mobile'] = $row->last;
			} elseif ($row->bwd_type == 'amp') {
				$stats['ti_googlebot_7day_views_amp'] = $row->hits;
				$stats['ti_googlebot_7day_last_amp'] = $row->last;
			} elseif ($row->bwd_type == 'dt') {
				$stats['ti_googlebot_7day_views_desktop'] = $row->hits;
				$stats['ti_googlebot_7day_last_desktop'] = $row->last;
			}
        }

		return $stats;
	}
}

/*
 * Meta robot policy for article
 */
class TSRobotPolicy extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$stats = array('ti_robot_policy' => '', 'ti_robot_policy_last_updated' => '');

		list($stats['ti_robot_policy'], $stats['ti_robot_policy_last_updated']) = RobotPolicy::getTitusPolicy($t);

		return $stats;
	}
}

/*
 * SEM Rush data for article
 */
class TSRushData extends TitusStat {
	/* schema:
CREATE TABLE `rush_data` (
  `rush_query` text,
  `rush_volume` int(11) DEFAULT NULL,
  `rush_cpc` decimal(5,2) DEFAULT NULL,
  `rush_competition` decimal(5,2) DEFAULT NULL,
  `rush_position` int(10) unsigned DEFAULT NULL,
  `rush_page_id` int(11) DEFAULT NULL,
  KEY `rush_page_id` (`rush_page_id`)
);
	 */

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$aid = $pageRow->page_id;
		$stats = array('ti_rush_topic_rank' => 0, 'ti_rush_cpc' => 0, 'ti_rush_query' => '');

		$sql = "select * from rush_data ra inner join
			(select rush_page_id, max(rush_volume) as max_vol from rush_data where rush_page_id = $aid group by rush_page_id) rb on
			ra.rush_page_id = rb.rush_page_id and ra.rush_volume = rb.max_vol and ra.rush_page_id = $aid LIMIT 1";
		$res = $dbr->query($sql);
		if ($row = $dbr->fetchObject($res)) {
			$stats['ti_rush_topic_rank'] = $row->rush_position;
			$stats['ti_rush_cpc'] = $row->rush_cpc;
			$stats['ti_rush_query'] = $row->rush_query;
		}
		return $stats;
	}
}

/*
 * Number of likes, plus ones and tweets
 */
class TSSocial extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$url = $this->getBaseUrl() . urldecode($t->getLocalUrl());
		$stats = array();
		$stats['ti_tweets'] = $this->getTweets($url);
		// Turn off Facebook because it is getting rate limited, and we don't really use this stat
		//$stats['ti_facebook'] = $this->getLikes($url);
		$stats['ti_plusones'] = $this->getPlusOnes($url);
		return $stats;
	}

	function getTweets($url) {
		$json_string = file_get_contents('http://urls.api.twitter.com/1/urls/count.json?url=' . $url);
		$json = json_decode($json_string, true);

		return intval($json['count']);
	}

	function getLikes($url) {
		// is there no comma in WH url?
		if (strpos($url, ',') === false) {
			$fburl = 'http://graph.facebook.com/?ids=' . $url;
		} else {
			// per: http://stackoverflow.com/questions/12163978/facebook-graph-api-returns-error-2500-when-there-are-commas-in-the-id-url
			$fburl = 'http://graph.facebook.com/' . $url;
		}
		$json_string = file_get_contents($fburl);
		$json = json_decode($json_string, true);

		return intval($json[$url]['shares']);
	}

	function getPlusOnes($url) {

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS,
			'[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url .
			'","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		$curl_results = curl_exec ($curl);
		curl_close ($curl);

		$json = json_decode($curl_results, true);

		return intval($json[0]['result']['metadata']['globalCounts']['count']);
	}
}

class TSTranslations extends TitusStat {

	public function getPageIdsToCalc( $dbr,  $date ) {
		global $wgLanguageCode;

		$ts = wfTimestamp(TS_UNIX, $date);
		$start = wfTimestamp(TS_MW, strtotime("-2 day", strtotime(date('Ymd',$ts))));

		$sql = "select distinct tl_from_aid from " . WH_DATABASE_NAME_EN . ".translation_link where tl_from_lang=" . $dbr->addQuotes($wgLanguageCode) . " AND tl_timestamp > " . $dbr->addQuotes($start);
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->tl_from_aid;
		}
		$sql = "select distinct tl_to_aid from " . WH_DATABASE_NAME_EN . ".translation_link where tl_to_lang=" . $dbr->addQuotes($wgLanguageCode) . " AND tl_timestamp > " . $dbr->addQuotes($start);
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$ids[] = $row->tl_to_aid;
		}
		$sql = "select distinct tll_from_aid from " . WH_DATABASE_NAME_EN . ".translation_link_log where tll_from_lang=" . $dbr->addQuotes($wgLanguageCode) . " AND tll_timestamp>" . $dbr->addQuotes($start) . " AND NOT (tll_from_aid is NULL)";
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$ids[] = $row->tll_from_aid;
		}

		$sql = "select distinct tll_to_aid from " . WH_DATABASE_NAME_EN . ".translation_link_log where tll_to_lang=" . $dbr->addQuotes($wgLanguageCode) . " AND tll_timestamp>" . $dbr->addQuotes($start) . " AND NOT (tll_to_aid is NULL)";
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$ids[] = $row->tll_to_aid;
		}

		$ids = array_unique($ids);

		return $ids;
	}

	private function fixURL($url) {
		if (preg_match("@(http://[a-z]+\.wikihow\.com/)(.+)@",$url,$matches)) {
			return $matches[1] . urlencode($matches[2]);
		}
		else {
			return $url;
		}
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;
		global $wgActiveLanguages;

		// Languages supported by Titus language_links
		$langs = $wgActiveLanguages;
		$langs[] = "en";

		// Added template fields to each language
		$ret = array();
		$links = array();
		foreach ($langs as $l) {

			$ret["ti_tl_" . $l] =  "";
			$ret["ti_tl_" . $l . "_id"] = "";
			$ret["ti_tl_" . $l . "_status"] = "";
		}
		$links = array_merge($links,TranslationLink::getLinksTo($wgLanguageCode,$pageRow->page_id));

		foreach ($links as $l) {
			if ($l->fromAID == $pageRow->page_id && $wgLanguageCode == $l->fromLang && in_array($l->toLang,$langs)) {
				if (isset($l->toURL)) {
					$ret["ti_tl_" . $l->toLang ] = $dbr->strencode($this->fixURL($l->toURL));
				}
				$ret["ti_tl_" . $l->toLang . "_id"] = intVal($l->toAID);
				$ret["ti_tl_" . $l->toLang . "_status"] = intVal($l->isTranslated) == 1 ? "active" : "inactive";
			}
			elseif($l->toAID == $pageRow->page_id && $wgLanguageCode == $l->toLang && in_array($l->fromLang, $langs)) {
				if (isset($l->fromURL)) {
					$ret["ti_tl_" . $l->fromLang] = $dbr->strencode($this->fixURL($l->fromURL));
				}
				$ret["ti_tl_" . $l->fromLang . "_id"] = intVal($l->fromAID);
				$ret["ti_tl_" . $l->fromLang . "_status"] = intVal($l->isTranslated) == 1 ? "active" : "inactive";
			}
		}
		return $ret;
	}
}

/**
 * Does the page have a sample in Titus
 */
class TSSample extends TitusStat {
  public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}
  public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$samples = 0;
		preg_match_all("/\[\[Doc:[^\]]*\]\]/", $txt, $matches);
		foreach ($matches[0] as $match) {
			$samples++;
			$samples += preg_match_all('/,/', $match, $dummyMatches);

		}
		$ret["ti_sample"] = $samples;
		return $ret;
	}
}
/**
 * Get info about most recent wikiphotos added to article
 */
class TSRecentWikiphoto extends TitusStat {
	/*
CREATE TABLE `wikiphoto_article_status` (
  `article_id` int(10) unsigned NOT NULL,
  `creator` varchar(32) NOT NULL DEFAULT '',
  `processed` varchar(14) NOT NULL DEFAULT '',
  `reviewed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `retry` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `needs_retry` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `error` text NOT NULL,
  `warning` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `images` int(10) unsigned NOT NULL DEFAULT '0',
  `replaced` int(10) unsigned NOT NULL DEFAULT '0',
  `steps` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`article_id`)
);
	 */

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$res = $dbr->select("wikivisual_article_status", array("creator","error","processed"), array("article_id" => $pageRow->page_id));
		$row = $dbr->fetchObject($res);

		$ret = array();
		if ($row->creator != NULL && $row->error == NULL) {
			$ret["ti_wikiphoto_creator"] = $row->creator;
			$ret["ti_wikiphoto_timestamp"] = $row->processed;
		} else {
			$res = $dbr->select("wikiphoto_article_status", array("creator","error","processed"),array("article_id" => $pageRow->page_id));
			$row = $dbr->fetchObject($res);

			if ($row->creator != NULL && $row->error == NULL) {
				$ret["ti_wikiphoto_creator"] = $row->creator;
				$ret["ti_wikiphoto_timestamp"] = $row->processed;
			} else {
				$ret["ti_wikiphoto_creator"] = "";
				$ret["ti_wikiphoto_timestamp"] = "";

			}
		}
		return $ret;
	}
}

/**
 * Update list of top 10k
 */
class TSTop10k extends TitusStat {
	private $_kwl = array();
	private $_ln = array();
	private $_ids = array();
	private $_gotSpreadsheet = false;
	private $_badSpreadsheet = false;

	/**
	 * Get the spreadsheet to feed the calculations
	 */
	private function getSpreadsheet($dbr) {
		global $wgLanguageCode;
		print "Getting Top10K spreadsheet\n";
		try {
			$gs = new GoogleSpreadsheet();
			$startColumn = 1;
			$endColumn = 4;
			$startRow = 2;
			$cols = $gs->getColumnData( WH_TITUS_TOP10K_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
			$urlList = array();
			$pageIds = array();
			$dups = "";
			foreach ($cols as $col) {
				if (is_numeric($col[1]) && $wgLanguageCode == $col[2]) {
					if (isset($this->_kwl[$col[1]])) {
						if ($dups == "") {
							$dups = "Duplicate article ids:\n\n";
						}
						else {
							$dups .= "\n\n";
						}
						$dups .= $col[1];
					}
					$this->_kwl[$col[1]] = $col[0];
					$this->_ln[$col[1]] = $col[3];
					$ids[] = $col[1];
				}
			}
			if ($dups != "") {
				$this->reportError($dups);
			}
			if (sizeof($this->_kwl) < 1000 && $wgLanguageCode == "en") {
				$this->_gotSpreadsheet = true;
				$this->_badSpreadsheet = true;
				$this->reportError("Top10k problem fetching spreadsheet. Fewer than 1000 ids found ");
				return;
			}
			if ($ids) {
				$this->checkForRedirects($dbr, $ids);
				$this->checkForMissing($dbr, $ids);
			}

			$query = "select ti_page_id, ti_top10k FROM " . TitusDB::getDBName() . "." . TitusDB::TITUS_INTL_TABLE_NAME  . " WHERE ti_language_code=" . $dbr->addQuotes($wgLanguageCode) ;
			$res = $dbr->query($query, __METHOD__);
			$pageIds = array();
			foreach ($res as $row) {
				if (isset($this->_kwl[$row->ti_page_id])) {
					if ($this->_kwl[$row->ti_page_id] != $row->ti_top10k) {
						$pageIds[] = $row->ti_page_id;
					}
				}
				else {
					if ($row->ti_top10k != NULL && $row->ti_top10k != "") {
						$pageIds[] = $row->ti_page_id;
					}
				}
			}
			$this->_ids = $pageIds;
			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = false;
		}
		catch(Exception $e) {
			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = true;
			$this->reportError("Top10k problem fetching spreadsheet :" . $e->getMessage());
		}
	}

	/**
	 * Get the page ids to calculate
	 */
	public function getPageIdsToCalc( $dbr, $date ) {
		if (! $this->_gotSpreadsheet) {
			$this->getSpreadsheet($dbr);
		}
		if ($this->_badSpreadsheet) {
			return array();
		}
		return $this->_ids;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;
		if ($this->_badSpreadsheet) {
			return array();
		}
		$ret =array('ti_top10k'=>'', 'ti_is_top10k'=>0, 'ti_top_list' => '');
		if (isset($this->_kwl[$pageRow->page_id])) {
			//1000 is the database limit for the size of the keywords
			if (sizeof($this->_kwl[$pageRow->page_id]) > 1000) {
				$this->reportError("Keyword for " . $pageRow->page_id . " over 1000 characters(truncating) :" . $this->_kwl[$pageRow->page_id]);
			}
			else {
				$ret['ti_top10k'] = $dbr->strencode($this->_kwl[$pageRow->page_id]);
				$ret['ti_is_top10k'] = 1;
				$ret['ti_top_list'] = $this->_ln[$pageRow->page_id];
			}
		}
		return $ret;
	}
}

class TSRatings extends TitusStat {
	private $_kwl = array();
	private $_ids = array();
	private $_gotSpreadsheet = false;
	private $_badSpreadsheet = false;
	private $_gotMMKRatings = false;

	private function getSpreadsheet($dbr) {
		global $wgLanguageCode;
		print "Getting ratings spreadsheet\n";
		try {
			$gs = new GoogleSpreadsheet();
			$startColumn = 1;
			$endColumn = 3;
			$startRow = 2;
			$cols = $gs->getColumnData( WH_TITUS_RATINGS_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
			$ids = array();
			$badDates = 0;
			foreach ($cols as $col) {
				if (is_numeric($col[0])) {
					$output = array($col[1],$this->fixDate($col[2]));
					if ($output[1] == NULL) {
						$badDates++;
					}
					if (isset($this->_kwl[$col[0]])) {
						$this->reportError("Duplicate ratings for article " . $col[0]);
					}
					$this->_kwl[$col[0]] = $output;
					$ids[] = $col[0];
				}
			}
			if ($badDates > 100) {
				$this->reportError("Unable to parse over 100 dates in spreadsheet");

				$this->_gotSpreadsheet=true;
				$this->_badSpreadsheet=true;
				return;

			}
			if (sizeof($ids) < 1000) {
				$this->reportError("Less than 1000 ratings in ratings spreadsheet found");
				$this->_gotSpreadsheet=true;
				$this->_badSpreadsheet=true;
				return;
			}
			$this->checkForRedirects($dbr, $ids);
			$this->checkForMissing($dbr, $ids);

			$query = "select ti_page_id, ti_rating, ti_rating_date FROM " . TitusDB::getDBName() . "." . TitusDB::TITUS_INTL_TABLE_NAME . " WHERE ti_language_code=" . $dbr->addquotes($wgLanguageCode) ;
			$res = $dbr->query($query, __METHOD__);
			$pageIds = array();
			foreach ($res as $row) {
				if (isset($this->_kwl[$row->ti_page_id])) {
					if ($this->_kwl[$row->ti_page_id][0] != $row->ti_rating || $this->_kwl[$row->ti_page_id][1]!=$row->ti_rating_date) {
						$pageIds[] = $row->ti_page_id;
					}
				}
				else {
					if (($row->ti_rating != NULL && $row->ti_rating != "") || ($row->ti_rating_date != NULL && $row->ti_rating_date != "") ) {
						$pageIds[] = $row->ti_page_id;
					}
				}
			}
			$this->_ids = $pageIds;

			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = false;

		}
		catch(Exception $e) {
			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = true;
			$this->reportError("Problem fetching spreadsheet :" . $e->getMessage());
		}
	}

	private function getRatingsFromMMK($dbr) {
		global $wgLanguageCode;
		print "Getting ratings from MMK Manager\n";
		try {
			$query = "select mmk_page_id, mmk_rating, mmk_rating_date FROM mmk.mmk_manager WHERE mmk_language_code=" . $dbr->addquotes($wgLanguageCode) ;
			$res = $dbr->query($query, __METHOD__);
			$ids = array();
			foreach ($res as $row) {
				$output = array($row->mmk_rating,$this->fixDate($row->mmk_rating_date));
				if ($output[1] == NULL) {
					$badDates++;
				}
				if (isset($this->_kwl[$row->mmk_page_id])) {
					$this->reportError("Duplicate ratings for article " . $row->mmk_page_id);
				}
				$this->_kwl[$row->mmk_page_id] = $output;
				$ids[] = $row->mmk_page_id;
			}

			$this->checkForRedirects($dbr, $ids);
			$this->checkForMissing($dbr, $ids);

			$query = "select ti_page_id, ti_rating, ti_rating_date FROM " . TitusDB::getDBName() . "." . TitusDB::TITUS_INTL_TABLE_NAME . " WHERE ti_language_code=" . $dbr->addquotes($wgLanguageCode) ;
			$res = $dbr->query($query, __METHOD__);
			$pageIds = array();
			foreach ($res as $row) {
				if (isset($this->_kwl[$row->ti_page_id])) {
					if ($this->_kwl[$row->ti_page_id][0] != $row->ti_rating || $this->_kwl[$row->ti_page_id][1]!=$row->ti_rating_date) {
						$pageIds[] = $row->ti_page_id;
					}
				}
				else {
					if (($row->ti_rating != NULL && $row->ti_rating != "") || ($row->ti_rating_date != NULL && $row->ti_rating_date != "") ) {
						$pageIds[] = $row->ti_page_id;
					}
				}
			}
			$this->_ids = $pageIds;

			$this->_gotMMKRatings = true;
		}
		catch(Exception $e) {
			$this->_gotMMKRatings = true;
			$this->reportError("Problem fetching data from MMK Manager :" . $e->getMessage());
		}
	}

	protected function fixDate($date) {
		$d=date_parse($date);
		if ($d) {
			return $d['year'] . $this->fixDatePart($d['month']) . $this->fixDatePart($d['day']) ;
		}
		else {
			return NULL;
		}
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		// if (!$this->_gotMMKRatings) {
			// $this->getRatingsFromMMK($dbr);
		// }
		if (isset($this->_kwl[$pageRow->page_id]) && $wgLanguageCode == "en") {
			$a = $this->_kwl[$pageRow->page_id];
			return array("ti_rating"=> $a[0],"ti_rating_date"=> $a[1]);
		}
		else {
			return array("ti_rating"=>"","ti_rating_date"=>"");
		}
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;
		if ($wgLanguageCode != "en") {
			return array();
		}
		if (!$this->_gotSpreadsheet) {
			$this->getSpreadsheet($dbr);
		}

		// if (!$this->_gotMMKRatings) {
			// $this->getRatingsFromMMK($dbr);
		// }

		return $this->_ids;
	}
}

/**
 *
 * a stat which reads its data from a google spreadsheet similarly to last edit and last fellow edit
 * alter table titus_intl add column `ti_visual_librarian` varchar(255) DEFAULT NULL after `ti_last_fellow_stub_edit_timestamp`,
 * add column `ti_visual_librarian_timestamp` varchar(8) DEFAULT NULL after `ti_visual_librarian`;
 *
 * alter table titus_historical_intl add column `ti_visual_librarian` varchar(255) DEFAULT NULL after `ti_last_fellow_stub_edit_timestamp`,
 * add column `ti_visual_librarian_timestamp` varchar(8) DEFAULT NULL after `ti_visual_librarian`;
 *
 */
class TSLibrarian extends TitusStat {
	private $_kwl = array();
	private $_ids = array();
	private $_gotSpreadsheet = false;
	private $_badSpreadsheet = false;

	private function getSpreadsheet( $dbr ) {
		global $wgLanguageCode;
		print "Getting librarian spreadsheet\n";
		try {
			$gs = new GoogleSpreadsheet();
			$startColumn = 1;
			$endColumn = 4;
			$startRow = 2;
			$cols = $gs->getColumnData( WH_TITUS_LIBRARIAN_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
			$ids = array();
			$badDates = 0;
			foreach ( $cols as $col ) {
				if ( is_numeric( $col[0] ) ) {
					$output = array( $this->fixDate( $col[1] ), $col[2] );
					if ( $output[1] == NULL ) {
						$badDates++;
					}
					$this->_kwl[$col[0]] = $output;
					$ids[] = $col[0];
				}
			}
			if ( $badDates > 100 ) {
				$this->reportError( "Unable to parse over 100 dates in spreadsheet" );
				$this->_gotSpreadsheet = true;
				$this->_badSpreadsheet = true;
				return;
			}

			$this->checkForRedirects( $dbr, $ids );
			$this->checkForMissing( $dbr, $ids );

			$query = "select ti_page_id, ti_visual_librarian, ti_visual_librarian_timestamp FROM " . TitusDB::getDBName() . "." . TitusDB::TITUS_INTL_TABLE_NAME . " WHERE ti_language_code=" . $dbr->addquotes($wgLanguageCode) ;
			$res = $dbr->query($query, __METHOD__);
			$pageIds = array();
			foreach ( $res as $row ) {
				if ( isset( $this->_kwl[$row->ti_page_id] ) ) {
					if ( $this->_kwl[$row->ti_page_id][0] != $row->ti_visual_librarian_timestamp
						|| $this->_kwl[$row->ti_page_id][1]!=$row->ti_visual_librarian ) {
							$pageIds[] = $row->ti_page_id;
					}
				} elseif ( ($row->ti_visual_librarian_timestamp != NULL && $row->ti_visual_librarian_timestamp != "" )
					|| ($row->ti_visual_librarian != NULL && $row->ti_visual_librarian != "") ) {
						$pageIds[] = $row->ti_page_id;
				}
			}
			$this->_ids = $pageIds;

			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = false;

		} catch( Exception $e ) {
			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = true;
			$this->reportError( "Problem fetching spreadsheet :" . $e->getMessage() );
		}
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		if ( isset($this->_kwl[$pageRow->page_id]) && $wgLanguageCode == "en" ) {
			$a = $this->_kwl[$pageRow->page_id];
			return array( "ti_visual_librarian_timestamp" => $a[0], "ti_visual_librarian" => $a[1] );
		} else {
			return array( "ti_visual_librarian_timestamp" => "", "ti_visual_librarian" => "" );
		}
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		if ( $wgLanguageCode != "en" ) {
			return array();
		}

		if ( !$this->_gotSpreadsheet ) {
			$this->getSpreadsheet( $dbr );
		}

		return $this->_ids;
	}
}

/**
 * Get last fellow stub edit from a spreadsheet
 * this is called "super titus" since it is the name of the project to get
 * info from a google spreadsheet.
 *
 * this particular feature is almost identical to lastFellowEdit.
 * the data lives on the same spreadsheet
 * it uses the following 2 columns
 * `ti_last_fellow_stub_edit` varchar(255) DEFAULT NULL,
 * `ti_last_fellow_stub_edit_timestamp` varchar(8) DEFAULT NULL,
 *
 * which can be added with these two statements:
 * alter table titus_intl add column `ti_last_fellow_stub_edit_timestamp` varchar(8) DEFAULT NULL after `ti_last_fellow_edit_timestamp`,
 * add column `ti_last_fellow_stub_edit` varchar(255) DEFAULT NULL after `ti_last_fellow_edit_timestamp`;
 * alter table titus_intl add column `ti_editing_status` varchar(255) DEFAULT NULL after `ti_last_fellow_edit_timestamp`;
 *
 * this also has a key which can be added as follows:
 * alter table titus_intl add KEY `last_fellow_stub_edit_timestamp` (`ti_last_fellow_stub_edit_timestamp`);
 *
 * alter table titus_historical_intl add column `ti_last_fellow_stub_edit_timestamp` varchar(8) DEFAULT NULL after `ti_last_fellow_edit_timestamp`,
 * add column `ti_last_fellow_stub_edit` varchar(255) DEFAULT NULL after `ti_last_fellow_edit_timestamp`;
 *
 */
class TSLastFellowStubEdit extends TitusStat {
	private $_kwl = array();
	private $_ids = array();
	private $_gotSpreadsheet = false;
	private $_badSpreadsheet = false;

	private function getSpreadsheet($dbr) {
		global $wgLanguageCode;
		print "Getting stub editor spreadsheet\n";
		try {
			$gs = new GoogleSpreadsheet();
			$startColumn = 1;
			$endColumn = 3;
			$startRow = 2;
			$cols = $gs->getColumnData( WH_TITUS_STUB_EDITOR_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
			$ids = array();
			$badDates = 0;
			foreach ($cols as $col) {
				if (is_numeric($col[0])) {
					$output = array($this->fixDate($col[1]),$col[2]);
					if ($output[1] == NULL) {
						$badDates++;
					}
					$this->_kwl[$col[0]] = $output;
					$ids[] = $col[0];
				}
			}
			if ($badDates > 100) {
				$this->reportError("Unable to parse over 100 dates in spreadsheet");

				$this->_gotSpreadsheet=true;
				$this->_badSpreadsheet=true;
				return;
			}

			// this is an error for the main fellow edit sheet, but not the stub edit sheet
			if ( sizeof( $ids ) < 1000 ) {
				decho( "Less than 1000 ratings in ratings spreadsheet found", '', false);
			}

			$this->checkForRedirects($dbr, $ids);
			$this->checkForMissing($dbr, $ids);

			$query = "select ti_page_id, ti_last_fellow_stub_edit, ti_last_fellow_stub_edit_timestamp FROM " . TitusDB::getDBName() . "." . TitusDB::TITUS_INTL_TABLE_NAME . " WHERE ti_language_code=" . $dbr->addquotes($wgLanguageCode) ;
			$res = $dbr->query($query, __METHOD__);
			$pageIds = array();
			foreach ($res as $row) {
				if (isset($this->_kwl[$row->ti_page_id])) {
					if ($this->_kwl[$row->ti_page_id][0] != $row->ti_last_fellow_stub_edit_timestamp || $this->_kwl[$row->ti_page_id][1]!=$row->ti_last_fellow_stub_edit) {
						$pageIds[] = $row->ti_page_id;
					}
				}
				else {
					if ( ($row->ti_last_fellow_stub_edit_timestamp != NULL && $row->ti_last_fellow_stub_edit_timestamp != "" ) ||
						($row->ti_last_fellow_stub_edit != NULL && $row->ti_last_fellow_stub_edit != "") ) {
						$pageIds[] = $row->ti_page_id;
					}
				}
			}
			$this->_ids = $pageIds;

			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = false;

		}
		catch(Exception $e) {
			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = true;
			$this->reportError("Problem fetching spreadsheet :" . $e->getMessage());
		}
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		if (isset($this->_kwl[$pageRow->page_id]) && $wgLanguageCode == "en") {
			$a = $this->_kwl[$pageRow->page_id];
			return array("ti_last_fellow_stub_edit_timestamp"=> $a[0],"ti_last_fellow_stub_edit"=> $a[1]);
		}
		else {
			return array("ti_last_fellow_stub_edit_timestamp"=>"","ti_last_fellow_stub_edit"=>"");
		}
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;
		if ($wgLanguageCode != "en") {
			return array();
		}
		if (!$this->_gotSpreadsheet) {
			$this->getSpreadsheet($dbr);
		}

		return $this->_ids;
	}
}

class TSLastFellowEdit extends TitusStat {
	private $_last_edits = [];
	private $_first_edits = array();
	private $_ids = [];
	private $_gotSpreadsheet = false;
	private $_badSpreadsheet = false;

	private function getSpreadsheet($dbr) {
		global $wgLanguageCode;
		print "Getting editor spreadsheet\n";
		try {
			$gs = new GoogleSpreadsheet();
			$startColumn = 1;
			$endColumn = 3;
			$startRow = 2;
			$cols = $gs->getColumnData( WH_TITUS_EDITOR_GOOGLE_DOC, $startColumn, $endColumn, $startRow );
			$ids = [];
			$badDates = 0;
			foreach ($cols as $col) {
				if (is_numeric($col[0])) {
					$output = [ $this->fixDate($col[1]), $col[2] ];
					$newDate = $output[0];
					if ($newDate == NULL) {
						$badDates++;
					} else {
						$aid = $col[0];
						$ids[$aid] = true;
						$lastEditSoFar = $this->_last_edits[$aid][0] ?? null;
						if ( !$lastEditSoFar || strcmp($lastEditSoFar, $newDate) < 0 ) {
							$this->_last_edits[$aid] = $output;
						}
						$firstEditSoFar = $this->_first_edits[$aid][0] ?? null;
						if ( !$firstEditSoFar || strcmp($firstEditSoFar, $newDate) > 0 ) {
							$this->_first_edits[$aid] = $output;
						}
					}
				}
			}
			if ($badDates > 100) {
				$this->reportError("Unable to parse over 100 dates in spreadsheet");

				$this->_gotSpreadsheet=true;
				$this->_badSpreadsheet=true;
				return;

			}
			if (sizeof($ids) < 1000) {
				$this->reportError("Less than 1000 ratings in ratings spreadsheet found");
				$this->_gotSpreadsheet=true;
				$this->_badSpreadsheet=true;
				return;
			}
			$this->checkForRedirects( $dbr, array_keys($ids) );
			$this->checkForMissing( $dbr, array_keys($ids) );

			$res = $dbr->select( TitusDB::getDBName() . '.' . TitusDB::TITUS_INTL_TABLE_NAME,
				['ti_page_id', 'ti_last_fellow_edit', 'ti_last_fellow_edit_timestamp','ti_first_fellow_edit_timestamp'],
				['ti_language_code' => $wgLanguageCode],
				__METHOD__ );
			$pageIds = [];
			foreach ($res as $row) {
				$aid = $row->ti_page_id;

				// We update the DB in 2 scenarios:

				// 1) If the article has fellow data in the DB but is missing from the sheet, we clear the DB data
				if ( !isset( $ids[$aid] ) ) {
					if ( ($row->ti_last_fellow_edit_timestamp != NULL && $row->ti_last_fellow_edit_timestamp != '') ||
						 ($row->ti_last_fellow_edit != NULL && $row->ti_last_fellow_edit != '') ||
						 ($row->ti_first_fellow_edit_timestamp != NULL && $row->ti_first_fellow_edit_timestamp != '')
					) {
						$pageIds[] = $aid;
					}
				}
				// 2) If the fellow data in the DB differs from the data in the spreadsheet, we update the DB
				elseif ( ($this->_last_edits[$aid][0] != $row->ti_last_fellow_edit_timestamp) ||
						 ($this->_last_edits[$aid][1] != $row->ti_last_fellow_edit) ||
						 ($this->_first_edits[$aid][0] != $row->ti_first_fellow_edit_timestamp)
				) {
					$pageIds[] = $aid;
				}
			}
			$this->_ids = $pageIds;

			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = false;

		}
		catch (Exception $e) {
			$this->_gotSpreadsheet = true;
			$this->_badSpreadsheet = true;
			$this->reportError("Problem fetching spreadsheet :" . $e->getMessage());
		}
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		$result = [
			'ti_last_fellow_edit_timestamp' => '',
			'ti_last_fellow_edit' => '',
			'ti_first_fellow_edit_timestamp' => '',
		];

		$pageId = $pageRow->page_id;

		$lastEdit = $this->_last_edits[$pageId] ?? null;
		if ( $lastEdit && $wgLanguageCode == 'en' ) {
			$result['ti_last_fellow_edit_timestamp'] = $dbr->strencode( $lastEdit[0] );
			$result['ti_last_fellow_edit'] = $dbr->strencode( $lastEdit[1] );
		}
		$firstEdit = $this->_first_edits[$pageId] ?? null;
		if ( $firstEdit && $wgLanguageCode == 'en' ) {
			$result['ti_first_fellow_edit_timestamp'] = $dbr->strencode( $firstEdit[0] );
		}

		return $result;
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;
		if ($wgLanguageCode != 'en') {
			return [];
		}
		if (!$this->_gotSpreadsheet) {
			$this->getSpreadsheet($dbr);
		}

		return $this->_ids;
	}
}

class TSEditingStatus extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		$result = array(
			"ti_editing_status" => ""
		);
		if ($wgLanguageCode != "en") {
			return $result;
		}

		$pageId = $pageRow->page_id;
		$table = TitusDB::TITUS_INTL_TABLE_NAME;
		$var = "ti_last_fellow_edit_timestamp";
		$cond = array(
			'ti_language_code' => 'en',
			'ti_page_id' => $pageId,
		);

		$dbName = $dbr->getDBname();
		$dbr->selectDB( TitusDB::getDBName() );
		$lastFellowEditTimestamp = $dbr->selectField( $table, $var, $cond, __METHOD__ );
		$dbr->selectDB( $dbName );

		$table = 'editfish_articles';
		if ( !$dbr->tableExists( $table ) ) {
			return $result;
		}
		$var = array(
			'ct_tag_list',
			'ct_completed',
		);

		$cond = array(
			'ct_page_id' => $pageId,
		);

		$tag = '';
		$row = $dbr->selectRow( $table, $var, $cond, __METHOD__ );
		if ( $row ) {
			if ( $row->ct_completed == 0 ) {
				if ( $row->ct_tag_list ) {
					$tag = $row->ct_tag_list;
				} else {
					$tag = "Missing Tag";
				}
			} else {
				if ( $lastFellowEditTimestamp ) {
					$tag = "Complete";
				} else {
					$tag = "Missing Date";
				}
			}
		} else {
			if ( !$lastFellowEditTimestamp ) {
				$tag = "No Plan to Edit";
			} else {
				$tag = "Complete";
			}
		}
		$result["ti_editing_status"] = $tag;
		return $result;
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

}

class TSLastPatrolledEditTimestamp extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return array('ti_last_patrolled_edit_timestamp' => $r->getTimestamp() );
	}
}

/**
 * Load Babelfish score and rank into Titus
 */
class TSBabelfishData extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		global $wgLanguageCode;

		$ret = array('ti_babelfish_rank' => NULL, 'ti_babelfish_score' => NULL);
		if ($wgLanguageCode != 'en') {
			$sql = "select ct_rank, ct_score FROM " . WH_DATABASE_NAME_EN . ".babelfish_articles "
		. " JOIN " . WH_DATABASE_NAME_EN . ".translation_link on tl_from_lang='en' AND tl_from_aid = ct_page_id AND tl_to_lang=ct_lang_code"
		. " WHERE tl_to_aid=" . $dbr->addQuotes($pageRow->page_id) . " AND ct_lang_code=" . $dbr->addQuotes($wgLanguageCode);
			$res = $dbr->query($sql, __METHOD__);
			if ($row = $dbr->fetchObject($res)) {
				$ret['ti_babelfish_rank'] = $row->ct_rank;
				$ret['ti_babelfish_score'] = $row->ct_score;
			}
		}
		else {
			// Grabbing Spanish rank for article from English because all articles have the same rank and score for all languages
			$sql = "select ct_rank, ct_score FROM " . WH_DATABASE_NAME_EN . ".babelfish_articles WHERE ct_page_id=" . $dbr->addQuotes($pageRow->page_id) . " AND ct_lang_code='es'";
			$res = $dbr->query($sql, __METHOD__);
			if ($row = $dbr->fetchObject($res)) {
				$ret['ti_babelfish_rank'] = $row->ct_rank;
				$ret['ti_babelfish_score'] = $row->ct_score;
			}
		}

		return $ret;
	}
}

/**
 * When Titus article was new article boosted
 */
class TSNABPromoted extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		$ts = wfTimestamp(TS_UNIX, $date);
		$d = wfTimestamp(TS_MW, strtotime("-2 day", strtotime(date('Ymd',$ts))));
		$langDB = Misc::getLangDB($wgLanguageCode);
		$sql = "select nap_page FROM " . $langDB .  ".newarticlepatrol JOIN " . $langDB . ".page on page_id = nap_page WHERE page_is_redirect = 0 AND nap_patrolled = 1 AND nap_timestamp_ci > " . $dbr->addQuotes($d);
		$res = $dbr->query($sql, __METHOD__);

		$pr = array();
		foreach ($res as $row) {
			$pr[$row->nap_page] = 1;
		}
		$sql = "select de_page_id FROM " . $langDB . ".daily_edits left join " . $langDB . ".newarticlepatrol on de_page_id = nap_page where nap_patrolled is NULL AND de_edit_type <> " . DailyEdits::DELETE_TYPE . " AND de_timestamp > " . $dbr->addQuotes($d) ;
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$pr[$row->de_page_id] = 1;
		}
		$ids = array_keys($pr);

		return $ids;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$nab = NewArticleBoost::getNABbedDate($dbr, $pageRow->page_id);
		$ret = array('ti_nab_promoted' => $nab);
		return $ret;
	}
}

/**
 * When Titus article was new article boosted
 */
class TSNABDemoted extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		$ts = wfTimestamp(TS_UNIX, $date);
		$d = wfTimestamp(TS_MW, strtotime("-2 day", strtotime(date('Ymd',$ts))));
		$langDB = Misc::getLangDB($wgLanguageCode);
		$sql = "select nap_page FROM " . $langDB .  ".newarticlepatrol JOIN " . $langDB . ".page on page_id = nap_page WHERE page_is_redirect = 0 AND nap_patrolled = 0 AND nap_demote = 1 AND nap_timestamp_ci > " . $dbr->addQuotes($d);
		$res = $dbr->query($sql, __METHOD__);

		$pr = array();
		foreach ($res as $row) {
			$pr[$row->nap_page] = 1;
		}
		$sql = "select de_page_id FROM " . $langDB . ".daily_edits left join " . $langDB . ".newarticlepatrol on de_page_id = nap_page where nap_patrolled is NULL AND de_edit_type <> " . DailyEdits::DELETE_TYPE . " AND de_timestamp > " . $dbr->addQuotes($d) ;
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$pr[$row->de_page_id] = 1;
		}
		$ids = array_keys($pr);

		return $ids;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$nad = NewArticleBoost::getDemotedDate($dbr, $pageRow->page_id);
		$ret = array('ti_nab_demoted' => $nad);
		return $ret;
	}
}

class TSNABScore extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		global $wgLanguageCode;

		$ts = wfTimestamp(TS_UNIX, $date);
		$d = wfTimestamp(TS_MW, strtotime("-2 day", strtotime(date('Ymd',$ts))));
		$langDB = Misc::getLangDB($wgLanguageCode);
		$sql = "select nap_page FROM " . $langDB .  ".newarticlepatrol JOIN " . $langDB . ".page on page_id = nap_page WHERE page_is_redirect = 0 AND nap_patrolled = 0 AND nap_demote = 1 AND nap_timestamp_ci > " . $dbr->addQuotes($d);
		$res = $dbr->query($sql, __METHOD__);

		$pr = array();
		foreach ($res as $row) {
			$pr[$row->nap_page] = 1;
		}
		$sql = "select de_page_id FROM " . $langDB . ".daily_edits left join " . $langDB . ".newarticlepatrol on de_page_id = nap_page where nap_patrolled is NULL AND de_edit_type <> " . DailyEdits::DELETE_TYPE . " AND de_timestamp > " . $dbr->addQuotes($d) ;
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$pr[$row->de_page_id] = 1;
		}
		$ids = array_keys($pr);

		return $ids;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$score = $dbr->selectField(NewArticleBoost::NAB_TABLE, 'nap_atlas_score', array('nap_page' => $pageRow->page_id), __METHOD__);
		$ret = array('ti_nab_score' => $score);
		return $ret;
	}
}

/**
 * Load data into Titus from Petametrics API
 */
class TSPetaMetrics extends TitusStat {
	private $_stats;
	private $_loaded;
	private $_errors;
	private $_hasError;

	public function __construct() {
		$this->_loaded = false;
		$this->_errors = 0;
	}

	// Load spreadsheet from Peta-Metrics API
	private function loadSpreadsheet() {
		$url = 'https://api.petametrics.com/v1/metrics/bydevice/export?$apiKey=' . WH_PETAMETRICS_API_KEY;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$str = curl_exec($ch);
		curl_close($ch);

		$lines = preg_split("@[\r\n]+@",$str);
		$first = true;
		foreach ($lines as $line) {
			if ($first) {
				$header = preg_split("@\t@",$line);
				$first = false;
			}
			else {
				$fs = preg_split("@\t@",$line);
				for ($n=0;$n < sizeof($header); $n++) {
					$this->_stats[$fs[1]][$header[$n]] = $fs[$n];
				}
			}
		}
	}

	// Some Petametrics data returns -1 on NULL. We want to convert this to NULL
	private function nullOrVal($val) {
		$nv = floatval($val);
		if (!is_numeric($val) || $nv < 0.0) {
			$this->_hasError = true;
			return NULL;
		}
		return $nv;
	}

	// We are loading for pages, everynight
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		if (!$this->_loaded) {
			$this->loadSpreadsheet();
			$this->_loaded = true;
		}
		$articleId = $t->getArticleId();
		if (isset($this->_stats[$articleId])) {
			$statsForPage = $this->_stats[$articleId];
			$this->_hasError = false;
			$ret = array(
				'ti_pm_desktop_30day_views' => $statsForPage['desktop:30days:views'],
				'ti_pm_mobile_30day_views' => $statsForPage['mobile:30days:views'],
				'ti_pm_tablet_30day_views' => $statsForPage['tablet:30days:views'],
				'ti_pm_desktop_10s' => $this->nullOrVal($statsForPage['desktop:bounce10s'])*100.0,
				'ti_pm_mobile_10s' => $this->nullOrVal($statsForPage['mobile:bounce10s']) * 100,
				'ti_pm_tablet_10s' => $this->nullOrVal($statsForPage['tablet:bounce10s']) * 100,
				'ti_pm_desktop_3m' => $this->nullOrVal($statsForPage['desktop:stay3mRate']) * 100,
				'ti_pm_mobile_3m' => $this->nullOrVal($statsForPage['mobile:stay3mRate']) * 100,
				'ti_pm_tablet_3m' => $this->nullOrVal($statsForPage['tablet:stay3mRate']) * 100,
				'ti_pm_desktop_scroll_px' => $this->nullOrVal($statsForPage['desktop:avgSpx']),
				'ti_pm_mobile_scroll_px' => $this->nullOrVal($statsForPage['mobile:avgSpx']),
				'ti_pm_tablet_scroll_px' => $this->nullOrVal($statsForPage['tablet:avgSpx']),
				'ti_pm_desktop_scroll_pct' => $this->nullOrVal($statsForPage['desktop:avgSpct']),
				'ti_pm_mobile_scroll_pct' => $this->nullOrVal($statsForPage['mobile:avgSpct']),
				'ti_pm_tablet_scroll_pct' => $this->nullOrVal($statsForPage['tablet:avgSpct'])
			);
			if ($this->_hasError) {
				if ($this->_errors < 50) {
					$this->reportError("Null or bad values for article " . $t->getArticleID());
				}
				elseif ($this->_errors == 50) {
					$this->reportError("Too many bad values in articles. Deprecating further PetaMetrics errors.");
				}
				$this->_errors++;
			}
		}
		else {
			$ret = array(
				'ti_pm_desktop_30day_views' => '',
				'ti_pm_tablet_30day_views' => '',
				'ti_pm_mobile_30day_views' => '',
				'ti_pm_desktop_10s' => '',
				'ti_pm_mobile_10s' => '',
				'ti_pm_tablet_10s' => '',
				'ti_pm_desktop_3m' => '',
				'ti_pm_mobile_3m' => '',
				'ti_pm_tablet_3m' => '',
				'ti_pm_desktop_scroll_px' => '',
				'ti_pm_mobile_scroll_px' => '',
				'ti_pm_tablet_scroll_px' => '',
				'ti_pm_desktop_scroll_pct' => '',
				'ti_pm_mobile_scroll_pct' => '',
				'ti_pm_tablet_scroll_pct' => ''
			);
		}
		return $ret;
	}
}

class TSCaps extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$caps = preg_match_all("@[A-Z]@", $txt, $matches);
		$lower = preg_match_all("@[a-z]@", $txt, $matches);
		if ($lower + $caps == 0) {
			$ratio = 1;
		}
		else {
			$ratio = $caps/($caps + $lower);
		}
		$stepsSection = Wikitext::getStepsSection($txt);
		$stepsCaps = 0;
		$stepsLower = 0;
		$stepsRatio = 0;
		if ($stepsSection) {
			$stepsCaps = preg_match_all("@[A-Z]@",$stepsSection[0], $matches);
			$stepsLower = preg_match_all("@[a-z]@",$stepsSection[0], $matches);
			if ($stepsCaps + $stepsLower == 0) {
				$stepsRatio = 1;
			}
			else {
				$stepsRatio = $stepsCaps / ($stepsCaps + $stepsLower);
			}
		}

		$intro = Wikitext::getIntro($txt);
		$introCaps = 0;
		$introLower = 0;
		$introRatio = 0;
		if ($intro) {
			$introCaps = preg_match_all("@[A-Z]@", $intro, $matches);
			$introLower = preg_match_all("@[a-z]@", $intro, $matches);
			if ($introLower + $introCaps == 0) {
				$introRatio =1;
			}
			else {
				$introRatio = $introCaps / ($introCaps + $introLower);
			}
		}
		return array(
			"ti_lower" => $lower,
			"ti_upper" => $caps,
			'ti_capsratio' => $ratio,
			'ti_steps_lower' => $stepsLower,
			'ti_steps_caps' => $stepsCaps,
			'ti_steps_ratio' => $stepsRatio,
			'ti_intro_lower' => $introLower,
			'ti_intro_caps' => $introCaps,
			'ti_intro_ratio' => $introRatio
		);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}
}

class TSStepLength extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$stepsSection = Wikitext::getStepsSection($txt);
		$steps = Wikitext::splitSteps($stepsSection[0]);
		$min = 0;
		$max = 0;
		$sum = 0;
		$n = 0;
		$first = true;
		foreach ($steps as $step) {
			$length = strlen($step);
			if ($first) {
				$min = $length;
				$max = $length;
			}
			if ($length < $min) {
				$min = $length;
			}
			if ($length > $max) {
				$max = $length;
			}
			$sum += $length;
			$n++;
		}
		if ($n > 0) {
			$avg = $sum / $n;
		}
		else {
			$avg = 0;
		}
		return array('ti_step_length_min' => $min, 'ti_step_length_max' => $max, 'ti_step_length_avg' => $avg);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}
}

/**
 * Number of links in the document
 */
class TSNumLinks extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$links = preg_match_all("@(http://[^\"\b'>]+)@",$txt, $matches);
		$baseLinks = preg_match_all("@(http://[^\"/\b'>]+/?)@", $txt, $matches);
		$maxRepeats = 0;
		foreach ($matches[0] as $match) {
			$repeats = preg_match_all('@' . preg_quote($match, '@') . '@',$txt, $matches2);
			if (is_numeric($repeats) && $repeats > $maxRepeats) {
				$maxRepeats = $repeats;
			}
		}
		$sites = preg_match_all("@www\.[a-zA-Z0-9-_]*\.[a-z]+@", $txt, $matches);

		return array('ti_num_links' => $links, 'ti_max_link_repeats' => $maxRepeats,'ti_num_base_links' => $baseLinks, 'ti_num_urls' => $sites);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}
}

/**
 * Check for template to see if the article is inuse
 */
class TSInUse extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		if (get_class($r) == "RevisionNoTemplateWrapper") {
			$txt = $r->getOrigText();
		} else {
			$txt = ContentHandler::getContentText( $r->getContent() );
		}

		return array('ti_inuse' => preg_match("@{{ *(inuse|construction)@i",$txt,$matches) ? '1' : '0');
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}
}

/**
 * Grab the Flesch Kincaid Reading Ease score
 * alter table titus_historical_intl add column `ti_fk_reading_ease` decimal(4,1) NOT NULL DEFAULT '0.0';
 */
class TSFKReadingEase extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$fkre = AdminReadabilityScore::getFKReadingEase(ContentHandler::getContentText( $r->getContent() ));
		$ret = array('ti_fk_reading_ease' => $fkre);
		return $ret;
	}
}

class TSWordLength extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$numWords = preg_match_all("@\b[a-zA-Z]+\b@",$txt,$matches);
		$wc = array();
		$numWords = 0;
		$wordTextSize = 0;
		foreach ($matches[0] as $match) {
			$match = strtolower($match);
			if (!isset($wc[$match])) {
				$wc[$match] = 0;
			}
			$wc[$match]++;
			$wordTextSize += strlen($match);
			$numWords++;
		}
		$numUniqWords = 0;
		$maxWordCt = 0;
		$totalUniqWordLen = 0;
		foreach ($wc as $match => $ct) {
			$numUniqWords++;
			$totalUniqWordLen += $ct;
			if ($ct > $maxWordCt) {
				$maxWordCt = $ct;
			}
		}

		$len = strlen($txt);
		if ($len == 0) {
			$len = 1;
		}
		// ti_num_words Number of words in document
		// ti_num_uniq_words Number of unique words in document
		// ti_max_word_count The maximum number of times a single word is repeated in document
		// ti_avg_word_len Average length of the word
		// ti_avg_uniq_word_len Average length of unique words
		// ti_words_pct_text Percent of text that is words

		return [
			"ti_num_words" => $numWords,
			"ti_num_uniq_words" => $numUniqWords,
			"ti_max_word_count" => $maxWordCt,
			"ti_avg_word_len" => ($wordTextSize / ($numWords?$numWords:1)),
			"ti_avg_uniq_word_len" => $totalUniqWordLen/($numUniqWords==0?1:$numUniqWords),
			"ti_words_pct_text" => $wordTextSize/$len
		];
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}
}

class TSCharTypes extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$op = preg_match_all("@\(@",$txt, $matches);
		$cp = preg_match_all("@\)@",$txt, $matches);
		$colon = preg_match_all("@:@",$txt, $matches);
		$ats = preg_match_all("@\@@", $txt, $matches);
		$semicolons = preg_match_all("@;@",$txt, $matches);
		$dashes = preg_match_all("@-@",$txt,$matches);
		$spaces = preg_match_all("@ @",$txt,$matches);
		$pluses = preg_match_all("@\+@",$txt,$matches);
		$vowels = preg_match_all("@[aeiouAEIOU]@",$txt,$matches);
		$letters = preg_match_all("@[a-zA-Z]@",$txt,$matches);
		$numbers = preg_match_all("@[0-9]@",$txt,$matches);
		$tabs = preg_match_all("@\t@",$txt, $matches);
		$numEquals = preg_match_all("@=@",$txt, $matches);
		$ob = preg_match_all("@\[@",$txt,$matches);
		$cb = preg_match_all("@\]@",$txt,$matches);
		$numPeriods = preg_match_all("@\.@",$txt,$matches);

		$len = strlen($txt);
		if ($len == 0) {
			$len = 1;
		}

		return [
			"ti_num_oparen" => $op,
			"ti_pct_oparen" => $op/$len,
			"ti_pct_cparen" => $cp/$len,
			"ti_pct_colon" => $colon/$len,
			"ti_num_semicolon" => $semicolons,
			"ti_pct_semicolon" => $semicolons/$len,
			'ti_num_dashes' => $dashes,
			'ti_pct_dashes' => $dashes/$len,
			'ti_num_pluses' => $pluses,
			'ti_pct_pluses' => $pluses/$len,
			'ti_num_vowels' => $vowels,
			'ti_pct_vowels' => $vowels / ($letters ? $letters : 1),
			'ti_num_numbers' => $numbers,
			'ti_pct_numbers' => $numbers/$len,
			'ti_pc_ob' => $ob/$len,
			'ti_num_ob' => $ob,
			'ti_num_cb' => $cb,
			'ti_pct_cb' => $cb/$len,
			'ti_num_periods' => $numPeriods,
			'ti_pct_periods' => $numPeriods/$len,
			'ti_num_ats' => $ats,
			'ti_num_tabs' => $tabs,
			'ti_num_spaces' => $spaces,
			'ti_pct_spaces' => $spaces/$len ];
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::DAILY_EDIT_IDS;
	}

}

class TSWikiText extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		return array('wikitext' => ContentHandler::getContentText( $r->getContent() ), 'article_id' => $t->getArticleId());
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

/**
 * See how many html list elements we have of various sorts
 */
class TSHtmlList extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$ulo = preg_match_all("@< *ul *>@i",$txt,$matches);
		$ulc = preg_match_all("@</ *ul *>@i",$txt, $matches);
		$lio = preg_match_all("@< *li *>@i",$txt, $matches);
		$lic = preg_match_all("@</ *li *>@i",$txt, $matches);

		return array('ti_html_ulo' => $ulo, 'ti_html_ulc' => $ulc, 'ti_html_lio' => $lio, 'ti_html_lic' => $lic);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSSubSteps extends TitusStat {
	private $_verbList;

	public function __construct() {
		$f = fopen(__DIR__ . "/verbs.txt","r");
		$this->_verbList = array();
		while (!feof($f)) {
			$l = fgets($f);
			$l = chop($l);
			$l = strtolower($l);
			$this->_verbList[$l] = 1;
		}
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = ContentHandler::getContentText( $r->getContent() );

		$fwc = array();
		$numSteps = 0;
		$numSubSteps = 0;
		$numSections = 0;
		$virtualSteps = 0;
		$numNumericSteps = 0;
		$numBullets = 0;
		$verbFirstWord = 0;
		$firstWords = 0;
		$stepVerbs = 0;
		$stepsWithVerbs = 0;
		$numSectionSteps = 0;

		if ($text) {
			$numSteps = preg_match_all('/(?:^|\n)\#[^*]/im', $text, $matches);
			$numSubSteps = preg_match_all('/(?:^|\n)\#\*/im', $text, $matches);
			$virtualSteps = preg_match_all('/\#\*/im', $text, $matches);
			$numNumericSteps = preg_match_all("/[0-9](\.|\))/",$text, $matches);
			$numBullets = preg_match_all("/(?:^|\n)\*/im", $text, $matches);
			$numSectionSteps = preg_match_all("@== *step@i", $text, $mathes);
			$numSections = preg_match_all("@==[^=\n\r]+==@", $text, $matches);
			$verbFirstWord = 0;
			$firstWords = 0;

			// Match first word in steps
			preg_match_all("@(?:^|\n)\# *([a-zA-Z]+) *@", $text, $matches);
			if ($matches[1]) {
				foreach ($matches[1] as $match) {
					$match = strtolower($match);
					if (!isset($fwc[$match])) {
						$fwc[$match] = 0;
					}
					if (isset($this->_verbList[$match])) {
						$verbFirstWord ++;
					}
					$firstWords++;
				}
			}
			// Check how many verbs are in the steps
			preg_match_all("@(\b[a-zA-Z]+\b)@", $text, $matches);
			if ($matches[1]) {
				foreach ($matches[1] as $match) {
					$match = strtolower($match);
					if (isset($this->_verbList[$match])) {
						$stepVerbs++;
					}
				}
			}
			$steps = preg_split("@\#@", $text);

			// Check how many steps have at least one verb
			$stepsWithVerbs = 0;
			foreach ($steps as $step) {
				if (preg_match_all("@(\b[a-zA-Z]+\b)@", $text, $matches)) {
					foreach ($matches[1] as $match) {
						if (isset($this->_verbList[$match])) {
							$stepsWithVerbs++;
							break;
						}
					}
				}
			}
		}

		if ($firstWords) {
			$fwVerbRatio = $verbFirstWord/$firstWords;
		}
		else {
			$fwVerbRatio = 0;
		}

		return array(
			'ti_num_virtual_steps' => $numSteps,
			'ti_num_virtual_substeps' => $numSubSteps,
			'ti_num_numeric_steps' => $numNumericSteps,
			'ti_virtual_steps' => $virtualSteps,
			'ti_distinct_step_fws' => sizeof(array_keys($fwc)),
			'ti_num_fws' => $firstWords,
			'ti_num_verb_fws' => $verbFirstWord,
			'ti_fw_verbratio' => $fwVerbRatio,
			'ti_step_verbs' => $stepVerbs,
			'ti_steps_with_verbs' => $stepsWithVerbs,
			'ti_num_section_steps' => $numSectionSteps,
			'ti_num_sections' => $numSections
		);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSCopyVio extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		if (get_class($r) == "RevisionNoTemplateWrapper") {
			$text = $r->getOrigText();
		}
		else {
			$text = ContentHandler::getContentText( $r->getContent() );
		}
		if (preg_match("@{{[^}]*copyvio@i", $text, $matches)) {
			return array("ti_copyvio" => 1);
		}
		else {
			return array("ti_copyvio" => 0);
		}
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSNFD extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		if (get_class($r) == "RevisionNoTemplateWrapper") {
			$text = $r->getOrigText();
		}
		else {
			$text = ContentHandler::getContentText( $r->getContent() );
		}
		if (preg_match("@{{[^}]*nfd\|([a-zA-Z]+)@", $text, $matches)) {
			return array("ti_nfd" => 1, "ti_nfd_type" => $matches[1]);
		}
		else {
			return array("ti_nfd" => 0, "ti_nfd_type" => "");
		}
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSRecipeStuff extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = ContentHandler::getContentText( $r->getContent() );
		$foodMeasurements = preg_match_all("@(?:[0-9]|a|one|two|three|four|five|six|seven|eight|nine|ten) (?:cup|tsp|tbsp|pinch|g.|teaspoon|tablespoon|gram|ounce|oz|liter|ml)@i", $text, $matches);
		$recipeWords = preg_match_all("@cook|bake|measure|stir|mix|spicy|whisk|microwave|broil|boil|grill|fry|simmer|heat|fried@", $text, $matches);
		$foodWords = preg_match_all("@seasoning|oil|eggs|juice|lemon|salt|pepper|sauce|mustard|wine|beer|teriyaki|scallion|sauce|sugar|fish|sesame|seed@", $text, $matches);
		$ingredientsSection = preg_match("@== *ingredients *==@", $text, $matches);
		return array('ti_food_measurements' => $foodMeasurements, 'ti_recipe_words' => $recipeWords, 'ti_food_words' => $foodWords);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSBadWords extends TitusStat {
	private $regex;

	public function __construct() {
		global $IP;

		$f = fopen("$IP/maintenance/wikihow/bad_words.txt","r");
		if ($f) {
			$badWords = array();
			while (!feof($f)) {
				$l = fgets($f);
				$l = chop($l);
				if ($l && strlen($l) > 1) {
					$badWords[] = $l;
				}
			}
			fclose($f);
			$this->regex = '@' . implode('|',$badWords) . '@i';
		} else {
			$this->regex = '';
		}
	}
	public function calc( $dbr, $r, $t, $pageRow ) {
		if (!$this->regex) throw new Exception('Could not load bad words list');
		$text = ContentHandler::getContentText( $r->getContent() );
		$numBadWords = preg_match_all($this->regex, $text, $matches);
		return array('ti_bad_words' => $numBadWords);
	}
	public function getPageIdsToCalc( $dbr, $date) {
		return array();
	}
}

class TSTitleAttrs extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = $t->getText();
		$syms = preg_match_all("@[[:punct:]]@",$text,$matches);
		$caps = preg_match_all("@[A-Z]@",$text,$matches);
		$lcase = preg_match_all("@[a-z]@",$text,$matches);
		$nums = preg_match_all("@[0-9]@",$text,$matches);
		$say = preg_match_all("@say@i", $text, $matches);
		$make = preg_match_all("@make@i", $text, $matches);
		$create = preg_match_all("@create@i", $text, $matches);
		$do = preg_match_all("@do@i", $text, $matches);
		$be = preg_match_all("@be@i", $text, $matches);
		$become = preg_match_all("@become@i", $text, $matches);
		$pronounce = preg_match_all("@pronounce@i", $text, $matches);

		return array(
			'ti_title_syms' => $syms,
			'ti_title_caps' => $caps,
			'ti_title_lcase' => $lcase,
			'ti_title_nums' => $nums,
			'ti_title_say' => $say,
			'ti_title_make' => $make,
			'ti_title_create' => $create,
			'ti_title_do' => $do,
			'ti_title_be' => $be,
			'ti_title_become' => $become,
			'ti_title_pronounce' => $pronounce
		);
	}
	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

/**
 * Look at the counts of certain keywords that signal certain types of articles
 */
class TSWords extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = $t->getText();
		$copy = preg_match_all("@\bcopy\b@i", $text, $matches);
		$paste = preg_match_all("@\bpaste\b@i", $text, $matches);
		$copyAndPaste = preg_match_all("@copy and paste@i", $text, $matches);
		$wordWithDot = preg_match_all("@[a-zA-Z]+\.[a-z]+@i",$text, $matches);
		$code = preg_match_all("@\bcode\b@i", $text, $matches);
		$website = preg_match_all("@\bwebsite\b@i", $text, $matches);
		$say = preg_match_all("@\bsay\b@i", $text, $matches);
		$pronounce = preg_match_all("@\bpronounce\b@i", $text, $matches);
		$free = preg_match_all("@\bfree\b@i", $text, $matches);
		$call = preg_match_all("@\bcall\b@i", $text, $matches);
		$dial = preg_match_all("@\bdial\b@i", $text, $matches);
		$wordFile = preg_match_all("@file@i", $text, $matches);
		$wordSave = preg_match_all("@save@i", $text, $matches);
		$wordType = preg_match_all("@\btype\b@i", $text, $matches);
		$wordOpen = preg_match_all("@\bopen\b@i", $text, $matches);
		$wordClick = preg_match_all("@\bclick\b@i", $text, $matches);
		$wordOn = preg_match_all("@\bon\b@i", $text, $matches);
		$wordWindows = preg_match_all("@\bWindows\b@i", $text, $matches);
		$wordTouch = preg_match_all("@\btouch\b@i", $text, $matches);
		$wordProgram = preg_match_all("@\bprogram\b@i", $text, $matches);
		$wordButton = preg_match_all("@\bbutton\b@i", $text, $matches);
		$wordInternet = preg_match_all("@\binternet\b@i", $text, $matches);
		$wordVirus = preg_match_all("@\bvirus\b@i", $text, $matches);
		$wordUpdate = preg_match_all("@\bupdate\b@i", $text, $matches);
		$wordBlock = preg_match_all("@\bblock\b@i", $text, $matches);
		$wordIphone = preg_match_all("@\b(?:iphone|i-phone)\b@i",$text, $matches);
		$wordAndroid = preg_match_all("@\bandroid\b@i", $text, $matches);
		$wordCMD = preg_match_all("@\bCMD\b@",$text, $matches);
		$wordCommand = preg_match_all("@\bcommand\b@", $text, $matches);
		$wordJava = preg_match_all("@\bjava\b@i",$text, $matches);
		$wordEnter = preg_match_all("@\benter\b@i",$text, $matches);
		$wordI = preg_match_all("@\bI\b@", $text, $matches);

		return array(
			"ti_word_copy" => $copy,
			"ti_word_paste" => $paste,
			"ti_word_copyandpaste" => $copyAndPaste,
			"ti_word_with_dot" => $wordWithDot,
			"ti_word_code" => $code,
			"ti_word_website" => $website,
			"ti_word_say" => $say,
			'ti_word_pronounce' => $pronounce,
			"ti_word_free" => $free,
			"ti_word_call" => $call,
			"ti_word_dial" => $dial,
			"ti_word_type" => $wordType,
			"ti_word_file" => $wordFile,
			"ti_word_save" => $wordSave,
			"ti_word_open" => $wordOpen,
			"ti_word_click" => $wordClick,
			"ti_word_on" => $wordOn,
			"ti_word_windows" => $wordWindows,
			"ti_word_touch" => $wordTouch,
			"ti_word_program" => $wordProgram,
			"ti_word_button" => $wordButton,
			"ti_word_internet" => $wordInternet,
			"ti_word_virus" => $wordVirus,
			"ti_word_update" => $wordUpdate,
			"ti_word_block" => $wordBlock,
			"ti_word_iphone" => $wordIphone,
			"ti_word_android" => $wordAndroid,
			"ti_word_cmd" => $wordCMD,
			"ti_word_command" => $wordCommand,
			"ti_word_java" => $wordJava,
			"ti_word_enter" => $wordEnter,
			"ti_word_i" => $wordI
		);
	}
	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

/**
 * There is a common formatting error where the steps are done in the form
 * #1 or #2
 */
class TSDoubleSteps extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = ContentHandler::getContentText( $r->getContent() );
		$doubleSteps = preg_match_all("@\# *[0-9]@", $text, $matches);
		return array("ti_double_steps" => $doubleSteps);
	}
	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSSymbolism extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$text = ContentHandler::getContentText( $r->getContent() );
		$smilies = preg_match_all("@:-*[()]@",$text, $matches);
		$explanations = preg_match_all("@!@", $text, $matches);
		return array("ti_smilies" => $smilies, "ti_explanations" => $explanations);
	}
	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSTransitions extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$numericTransitions = preg_match_all("@first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|eleventh@", $txt, $matches);
	}
	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

class TSSpamKeywords extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$keywords = preg_match_all("@moving|packing|seo|backlinks|casino|poker|pharmacy|nutritional suplement|escort|visa|cleaning service|love spell|love potion|abney associates|forex|limousine|watch online for free|nintendo 3ds flash card|purse|watch|penis enlargement|black magic|payday loan|pest control|dubai@", $txt, $matches);
		$authorIs = preg_match("@The author is a@", $txt, $matches);
		return array('ti_spam_keywords' => $keywords, 'ti_spam_author_is' => $authorIs);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

/**
 * Get how many spelling errors there are in an articles
 */
class TSSpellCheck extends TitusStat {
	public $pspell;
	public $wl;

	public function __construct() {
		$this->pspell = wikiHowDictionary::getLibrary();
		$this->wl = wikihowDictionary::getWhiteListArray();
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$txt = ContentHandler::getContentText( $r->getContent() );
		$t = $this;
		$n = 0;
		$badWords = array();
		$newText = WikihowArticleEditor::textify($txt, array('remove_ext_links' => 1));
		preg_replace_callback('/\b(\w|\')+\b/',
			function($word) use(&$badWords, &$n, &$t) {
				$word = $word[0];
				if (!isset($t->wl[$word])
					&& !isset($t->wl[$word])
					&& !preg_match("@[0-9]@",$word, $matches)
					&& !pspell_check($t->pspell, $word)
				) {
					$badWords[$word] = 1;
					$n++;
				}
			},
			$newText);
		return  array('ti_mispelled' => $n, 'ti_mispelled_words' => count(array_keys($badWords)));
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

/**
 * Grammar check articles
 */
class TSGrammar extends TitusStat {

	public function __construct() {
	}

	public function checkGrammar($txt) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_URL, "http://localhost:8081/" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, 'language=' . urlencode( 'en-US') . '&text=' . urlencode($txt) );
		$str =curl_exec($ch);
		curl_close($ch);
		$xml = simplexml_load_string($str);
		$ret = array();
		foreach ($xml->error as $e) {
			$attr = $e->attributes();
			$cat = 'ti_lt_' . str_replace(' ','_',$attr['category']);
			if (!isset($ret[$cat])) {
				$ret[$cat] = 0;
			}
			$ret[$cat] ++;
		}
		return $ret;
	}

	public function cleanText($txt) {
		// Remove wikilinks
		$txt = preg_replace( "@\[\[[^|\]]+\|([^\]]+)\]\]@", "$1", $txt);
		$txt = preg_replace( "@\[[^\]]+\]+@", "", $txt);
		$txt = preg_replace( "@<[^>]+>@", "", $txt);
		$txt = htmlspecialchars_decode($txt);
		return $txt;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$rawTxt = ContentHandler::getContentText( $r->getContent() );
		$text = Wikitext::getStepsSection($rawTxt, true);
		$text = $text[0];
		$steps = preg_split("@#\*?@", $text);
		$ret = array(
			"ti_lt_Bad_style" => 0,
			"ti_lt_Capitalization" => 0,
			"ti_lt_Collocations" => 0,
			"ti_lt_Commonly_Confused_Words" => 0,
			"ti_lt_Grammar" => 0,
			"ti_lt_Miscellaneous" => 0,
			"ti_lt_Nonstandard_Phrases" => 0,
			"ti_lt_Possible_Typo" => 0,
			"ti_lt_Punctuation_Errors" => 0,
			"ti_lt_Redundant_Phrases" => 0,
			"ti_lt_Slang" => 0
		);
		foreach ($steps as $step) {
			$txt = $this->cleanText($step);
			$subRet = $this->checkGrammar($txt);
			foreach ( $subRet as $k => $v ) {
				$ret[$k] += $v;
			}
		}
		$intro = Wikitext::getIntro($rawTxt);
		$txt = $this->cleanText($intro);
		$subRet = $this->checkGrammar($txt);
		foreach ( $subRet as $k => $v ) {
			$ret[$k] += $v;
		}

		return $ret;
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

/**
 * Get info about expert verified articles
 * it uses the following 3 (soon to be 4) additional columns
 * `ti_expert_verified_name` varchar(255) NOT NULL default '';
 * `ti_expert_verified_date` varchar(255) NOT NULL default '';
 * `ti_expert_verified_revision` varchar(255) NOT NULL default '';
 * `ti_expert_verified_community` varchar(255) NOT NULL default '';
 *
 * which can be added with this statement:
 * alter table titus_intl add column `ti_expert_verified_name` varchar(255) NOT NULL default '',
 * add column `ti_expert_verified_date` varchar(255) NOT NULL default '',
 * add column `ti_expert_verified_revision` varchar(255) NOT NULL default '';
 *
 * additional row for verified source (like community or expert)
 * alter table titus_intl add column `ti_expert_verified_source` varchar(255) NOT NULL default '' after `ti_expert_verified_revision`;
 * alter table titus_historical_intl add column `ti_expert_verified_source` varchar(255) NOT NULL default '' after `ti_expert_verified_revision`;
 */
class TSExpertVerified extends TitusStat {

	public function getPageIdsToCalc( $dbr, $date ) {
		return  TitusDB::ALL_IDS ;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$stats = array();

		$pageId = $pageRow->page_id;
		$verifiers = VerifyData::getByPageIdFromDB( $pageId );

		$verifierNames = array();
		$verifierDates = array();
		$verifierRevisions = array();
		$verifierSources = array();

		if ( $verifiers && !empty($verifiers) ) {
			foreach ( $verifiers as $v ) {
				$verifierNames[] = $v->name ?: 'unknown';
				$verifierDates[] = $v->date ?: 'unknown';
				$verifierRevisions[] = $v->revisionId ?: 'unknown';
				$verifierSources[] = $v->worksheetName ?: 'unknown';
			}
		}

		// get name, date and revision
		$stats['ti_expert_verified_name'] = $dbr->strencode( implode( ",", $verifierNames ) );
		$stats['ti_expert_verified_date'] = $dbr->strencode( implode( ",", $verifierDates ) );
		$stats['ti_expert_verified_revision'] = $dbr->strencode( implode( ",", $verifierRevisions ) );
		$stats['ti_expert_verified_source'] = $dbr->strencode( implode( ",", $verifierSources) );

		// Add Tech Article Widget stats if no verifier data was found
		$techStats = $this->getTechReviewed($dbr, $pageRow, empty($verifierNames));
		$stats = array_merge($stats, $techStats);

		return $stats;
	}

	/**
	 * Tech Article Widget (prod/extensions/wikihow/TechArticle/)
	 *
	 * It sets the dedicated TechArticle fields (ti_tech_X). If the article has tested
	 * platforms, it may also set shared ExpertVerified fields (ti_expert_verified_X),
	 *
		alter table titus_intl
		add column ti_tech_product varchar(500) DEFAULT '' after ti_userreview_stamp,
		add column ti_tech_platforms varchar(1000) DEFAULT '' after ti_tech_product,
		add column ti_tech_tested varchar(100) DEFAULT '' after ti_tech_platforms;
	 */
	private function getTechReviewed($dbr, $pageRow, $overwriteExpert) {
		global $wgLanguageCode;

		if ($wgLanguageCode != 'en') {
			return [ 'ti_tech_product' => null, 'ti_tech_platforms' => null, 'ti_tech_tested' => null ];
		}

		$data = [
			'ti_tech_product' => null,
			'ti_tech_platforms' => [],
			'ti_tech_tested' => []
		];

		// Fetch the product and platforms for the article
		$tables = ['tech_article', 'tech_product', 'tech_platform'];
		$fields = ['tpr_name', 'tpl_name', 'tar_tested', 'tar_user_id', 'tar_rev_id', 'tar_date'];
		$conds = [
			'tar_page_id' => $pageRow->page_id,
			'tar_product_id = tpr_id',
			'tar_platform_id = tpl_id',
		];
		$options = [ 'ORDER BY' => 'tar_tested, tpl_name' ];
		$res = $dbr->select($tables, $fields, $conds, __METHOD__, $options);
		$fullyTested = true; // To simulate TechArticle->isFullyTested()

		foreach ($res as $row) {

			if (!$row->tar_tested) { // ORDER BY is relevant for this to work
				$fullyTested = false;
			}

			// A TechArticle can only be about 1 product, so we save it once as a string
			if (!$data['ti_tech_product']) {
				$data['ti_tech_product'] = $row->tpr_name;
			}

			// A TechArticle can apply to multiple platforms, so we save them as an array
			$data['ti_tech_platforms'][] = $row->tpl_name;
			if ($row->tar_tested) {
				$data['ti_tech_tested'][] = $row->tpl_name;
			}

			// Write to the shared fields only when there is no other ExpertVerified data,
			// and all platforms have been tested
			if ($overwriteExpert && $fullyTested) {
				$unixTS = wfTimestamp(TS_UNIX, $row->tar_date);
				$dateStr = DateTime::createFromFormat('U', $unixTS)->format('n/j/y');
				$data['ti_expert_verified_date'] = $dateStr;
				$data['ti_expert_verified_name'] = $dbr->strencode( User::newFromId($row->tar_user_id)->getName() );
				$data['ti_expert_verified_revision'] = $row->tar_rev_id;
				$data['ti_expert_verified_source'] = 'tech';
				$overwriteExpert = false; // We only need to do this once, for the newest row
			}
		}

		$plats = $data['ti_tech_platforms'];
		$data['ti_tech_platforms'] = $plats ? $dbr->strencode(implode(', ', $plats)) : null;

		$tested = $data['ti_tech_tested'];
		$data['ti_tech_tested'] = $tested ? $dbr->strencode(implode(', ', $tested)) : null;

		return $data;
	}
}

/**
 * Get info about staff reviewed articles
 * `ti_staff_byline_eligible` tinyint(1) NOT NULL default 0;
 *
 * additional row for staff_reviewed
 * alter table titus_intl add column `ti_staff_byline_eligible` tinyint(1) NOT NULL default 0 after `ti_expert_verified_source`;
 */
class TSStaffReviewed extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return  TitusDB::ALL_IDS ;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$staff_reviewed = StaffReviewed::staffReviewedCheck($pageRow->page_id, $checkMemc = false) ? 1 : 0;
		return ['ti_staff_byline_eligible' => $staff_reviewed];
	}
}

/**
 * Get the number of pictures patrolled
 */
class TSUCIImages extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		$ts = wfTimestamp(TS_UNIX, $date);
		$start = wfTimestamp(TS_MW, strtotime("-2 day", strtotime(date('Ymd',$ts))));
		$sql = "SELECT DISTINCT page_id " .
			   "FROM " . WH_DATABASE_NAME_EN . ".user_completed_images " .
			   "JOIN " . WH_DATABASE_NAME_EN . ".page ON page_title = uci_article_name " .
			   "  AND page_namespace = " . NS_MAIN . " " .
			   "WHERE uci_timestamp  > " . $dbr->addQuotes($start);
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach ( $res as $row ) {
			$ids[] = $row->page_id;
		}
		return $ids;
	}
	public function calc( $dbr, $r, $t, $pageRow ) {
		$ret = array('ti_pp_images' => UCIPatrol::getNumUCIForPage($t->getText()));

		return $ret;
	}
}

/*
 * Info on ratings for an article through rating tool
 *
 * ALTER TABLE titus_intl ADD COLUMN ti_ratetool_total int(4) unsigned AFTER ti_helpful_last_reset_timestamp;
 * ALTER TABLE titus_intl ADD COLUMN ti_ratetool_percentage decimal(4, 2) unsigned AFTER ti_ratetool_total;
 */
class TSRateTool extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		// Nullify these RateTool stats since this tool is gone
		return array("ti_ratetool_total" => 0, "ti_ratetool_percentage" => 0);
	}
}

/*
 * alter table titus_intl add column `ti_qa_questions_unanswered_approved` int(10) NOT NULL default 0 after `ti_qa_last_answer_date`;
 * alter table titus_intl add column `ti_qa_show_unanswered` tinyint(1) NOT NULL default 0 after `ti_qa_questions_unanswered_approved`;
 * alter table titus_historical_intl add column `ti_qa_questions_unanswered_approved` int(10) NOT NULL default 0 after `ti_qa_last_answer_date`;
 * alter table titus_historical_intl add column `ti_qa_show_unanswered` tinyint(1) NOT NULL default 0 after `ti_qa_questions_unanswered_approved`;
 * Stats relating to the Q&A (Questions and Answers) feature
 */
class TSQA extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$aid = $t->getArticleId();

		// In some languages this table  does not exist and this will cause
		// a database error, check to see if it exists before using it
		if (!class_exists('QADB') || !$dbr->tableExists( QADB::TABLE_SUBMITTED_QUESTIONS )) {
			return array();
		}

		if (!empty($aid)) {
			$data = [
				"ti_qa_questions_unanswered" => $this->getQuestionsUnanswered($dbr, $aid),
				"ti_qa_questions_answered" => $this->getQuestionsAnswered($dbr, $aid),
				"ti_qa_last_answer_date" => $this->getLastAnswerDate($dbr, $aid),
				"ti_qa_questions_unanswered_approved" => $this->getQuestionsUnansweredApproved($aid),
				"ti_qa_show_unanswered" => $this->showUnanswered($t),
				"ti_qa_expert_questions_answered" => $this->getExpertQuestionsAnswered($dbr, $aid)
			];
		} else {
			$data = [
				"ti_qa_questions_unanswered" => 0,
				"ti_qa_questions_answered" => 0,
				"ti_qa_last_answer_date" => "",
				"ti_qa_questions_unanswered_approved" => 0,
				"ti_qa_show_unanswered" => 0,
				"ti_qa_expert_questions_answered" => 0
			];
		}

		return $data;
	}

	protected function getQuestionsUnansweredApproved($aid) {
		$qadb = QADB::newInstance();
		$lastSubmittedId = 0;
		$curated = false;
		$proposed = false;
		$approved = true;
		$count = $qadb->getSubmittedQuestionsCount($aid, $lastSubmittedId, $curated, $proposed, $approved);
		return $count;
	}

	protected function showUnanswered($t) {
		return (int)QAWidget::isUnansweredQuestionsTarget($t);
	}

	protected function getLastAnswerDate($dbr, $aid) {
		$ts = $dbr->selectField(
				QADB::TABLE_ARTICLES_QUESTIONS,
				"qa_updated_timestamp",
				["qa_article_id" => $aid],
				__METHOD__,
				[
					"ORDER BY" => "qa_updated_timestamp DESC",
					"LIMIT" => 1
				]
		);

		$date = '';
		if (!empty($ts) && strlen($ts) == 14) {
			$date = substr($ts, 0, 8);
		}

		return $date;
	}

	protected function getExpertQuestionsAnswered($dbr, $aid) {
		return $dbr->selectField(
			QADB::TABLE_ARTICLES_QUESTIONS,
			"count(*)",
			[
				"qa_article_id" => $aid,
				"qa_inactive" => 0,
				"qa_verifier_id != 0"
			],
			__METHOD__
		);
	}

	protected function getQuestionsAnswered($dbr, $aid) {
		return $dbr->selectField(
				QADB::TABLE_ARTICLES_QUESTIONS,
				"count(*)",
				[
					"qa_article_id" => $aid,
					"qa_inactive" => 0
				],
				__METHOD__
		);
	}

	protected function getQuestionsUnanswered($dbr, $aid) {
		return $dbr->selectField(
			QADB::TABLE_SUBMITTED_QUESTIONS,
			"count(*)",
			[
				"qs_article_id" => $aid,
				"qs_ignore" => 0,
				"qs_curated" => 0
			],
			__METHOD__
		);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return TitusDB::ALL_IDS;
	}
}

class TSKeywordRank extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date)  {
		return TitusDB::DAILY_EDIT_IDS;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$res = $dbr->query("select min(position) as position from dedup.keywords where title=" . $dbr->addQuotes("how to " . str_replace('-',' ',$t->getText())));
		$pos = 0;
		foreach ( $res as $row ) {
			$pos = $row->position;
		}
		return ( array('ti_keyword_rank' => $pos));
	}
}
/**
 * Mongo function for getting all the steps
 */
class TSStepsText extends TitusStat {
	public function calc( $dbr, $r, $t, $pageRow ) {
		$rawTxt = ContentHandler::getContentText( $r->getContent() );
		$text = Wikitext::getStepsSection($rawTxt, true);
		$text = $text[0];
		$steps = preg_split("@#@", $text);
		$realSteps = array();
		$subSteps = array();
		foreach ( $steps as $step ) {
			if ( $step[0] == '*' ) {
				$substep = $this->cleanText(substr($step, 1));
				if (!preg_match("@^==@", $substep, $matches)) {
					$subSteps[] = $substep;
				}
			} else {
				$step = $this->cleanText($step);
				if (!preg_match("@^==@", $step, $matches)) {
					$realSteps[] = $step;
				}
			}
		}
		return array('steps' => $realSteps, 'substeps' => $subSteps);
	}

	public function getPageIdsToCalc( $dbr, $date ) {
		return array();
	}
}

/**
 * Get info about user reviews
 *
 * How many curated reviews are on the article
 * Whether or not an article has the user reviewed stamp
 *
 * alter table titus_intl add column `ti_userreview_count` int(10) NOT NULL default 0, add column `ti_userreview_stamp` tinyint(1) NOT NULL default 0 after `ti_userreview_count`;
 * alter table titus_historical_intl add column `ti_userreview_count` int(10) NOT NULL default 0, add column `ti_userreview_stamp` tinyint(1) NOT NULL default 0 after `ti_userreview_count`;
 *
 */
class TSUserReview extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		return  TitusDB::ALL_IDS ;
	}
	public function calc( $dbr, $r, $t, $pageRow ) {
		$stats = array();

		$pageId = $pageRow->page_id;
		$numReviews = UserReview::getEligibleNumCuratedReviews( $pageId );
		$hasIntro = UserReview::eligibleForByline( WikiPage::newFromId($pageId) );
		$totalReviews = UserReview::getTotalCuratedReviews( $pageId );
		$isEligible = UserReview::getArticleEligibilityString( $pageId );

		$stats['ti_userreview_count'] = $numReviews;
		$stats['ti_userreview_stamp'] = $hasIntro;
		$stats['ti_userreview_eligible'] = $isEligible;
		$stats['ti_userreview_count_all'] = $totalReviews;

		return $stats;
	}
}

/**
 * Get info about quizzes
 *
 * How many quiz questions on each article
 *
 * alter table titus_intl add column `ti_quiz_valid_count` int(10) NOT NULL default 0 after `ti_userreview_count`;
 *
 */
class TSQuizzes extends TitusStat {

	public function getPageIdsToCalc($dbr, $date) {
		return (TitusDB::ALL_IDS);
	}

	public function calc($dbr, $r, $t, $pageRow) {
		$pageId = $pageRow->page_id;
		$countInfo = QuizImporter::getMethodCountForId($pageId);

		// get name, date and revision
		$stats['ti_quiz_valid_count'] = $countInfo['good'];

		return $stats;

	}
}

/**
 * Sensitive Article (prod/extensions/wikihow/SensitiveArticle/)
 *
   alter table titus_intl
   add column ti_sensitive_reason varchar(1000) DEFAULT '' after ti_tech_tested;
 */
class TSSensitiveArticle extends TitusStat {

	public function getPageIdsToCalc($dbr, $date) {
		return (TitusDB::ALL_IDS);
	}

	public function calc($dbr, $r, $t, $pageRow) {
		$tables = ['sensitive_reason', 'sensitive_article'];
		$fields = 'sr_name';
		$conds = [
			'sa_page_id' => $pageRow->page_id,
			'sr_id = sa_reason_id'
		];
		$options = [ 'ORDER BY' => 'sr_name' ];
		$res = $dbr->select($tables, $fields, $conds, __METHOD__, $options);

		$reasons = [];
		foreach ($res as $row) {
			$reasons[] = $row->sr_name;
		}

		$value = $reasons ? $dbr->strencode(implode(', ', $reasons)) : null;
		return ['ti_sensitive_reason' => $value];
	}
}

/*
 * ti_search_volume
 * alter table titus_intl add column ti_search_volume int(10) NOT NULL default 0;
 * alter table titus_intl add column ti_search_volume_label varchar(255) NOT NULL default '' after ti_search_volume;
 */
class TSSearchVolume extends TitusStat {
	public function getPageIdsToCalc( $dbr, $date ) {
		$ids = SearchVolume::getNewPageIds();

		return $ids;
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		$volume = SearchVolume::getVolume($pageRow->page_id);
		$label = SearchVolume::getVolumeLabel($volume);

		return ['ti_search_volume' => $volume, 'ti_search_volume_label' => $label ];
	}
}

/*
 * ti_inbound_links
 * alter table titus_intl add column ti_inbound_links int(10) unsigned DEFAULT NULL;
 */
class TSInboundLinks extends TitusStat
{
	public function getPageIdsToCalc( $dbr, $date ) {
		return (TitusDB::ALL_IDS);
	}

	public function calc( $dbr, $r, $t, $pageRow ) {
		return ['ti_inbound_links' => ArticleStats::getInboundLinkCount($t)];
	}
}

/**
 * Fetch original verification dates for English articles from a Google Sheet.
 *
 * ALTER TABLE titus_intl ADD COLUMN ti_expert_verified_date_original varchar(14) DEFAULT NULL after ti_expert_verified_source;
 */
class TSExpertVerifiedSince extends TitusStat {
	private $dates = []; // [ EN_AID => VERIFICATION_DATE]
	private $isSheetProcessed = false;

	public function getPageIdsToCalc($dbr, $date) {
		if (Misc::isIntl()) {
			return [];
		}
		if (!$this->isSheetProcessed) {
			$this->processSpreadsheet($dbr);
		}
		return array_keys($this->dates);
	}

	public function calc($dbr, $r, $t, $pageRow) {
		$aid = $pageRow->page_id;
		if (!Misc::isIntl() && isset($this->dates[$aid])) {
			return [ 'ti_expert_verified_date_original' => $this->dates[$aid] ];
		} else {
			return [ 'ti_expert_verified_date_original' => null ];
		}
	}

	private function processSpreadsheet($dbr) {
		print __CLASS__ . ": fetching 'Original Verification Dates' spreadsheet\n";
		$this->isSheetProcessed = true;

		try {
			$sheet =@ new GoogleSpreadsheet();
			$cols = $sheet->getSheetData(WH_TITUS_ORIGINAL_VERIFICATIONS_GOOGLE_DOC, 'production!A2:D');
		} catch (Exception $e) {
			$this->reportError("Problem fetching spreadsheet: " . $e->getMessage());
			return;
		}

		$errors = 0;
		foreach ($cols as $col) {
			$aid = ( is_numeric($col[0]) && (int) $col[0] > 0 ) ? (int) $col[0] : null;
			$newDate = $col[2] ? $this->fixDate($col[2]) : null;
			if ($aid && $newDate) {
				$oldDate = $this->dates[$aid] ?? null;
				if ( $oldDate && strcmp($oldDate, $newDate) <= 0 ) {
					continue; // we are only interested in the oldest date
				}
				$this->dates[$aid] = $newDate;
			} else {
				$errors++;
			}
		}

		if ($errors * 100 > count($this->dates)) { // 1% of 25k rows = 250 errors
			$this->reportError("Encountered {$errors} parsing errors in WH_TITUS_ORIGINAL_VERIFICATIONS_GOOGLE_DOC");
		}

		$ids = array_keys($this->dates);
		$this->checkForRedirects($dbr, $ids);
		$this->checkForMissing($dbr, $ids);
	}

}

/**
 *  Quick Summary Created dates and authors for English articles from a Google Sheet.
 *
 * ALTER TABLE titus_intl ADD COLUMN ti_summary_created_date varchar(14) DEFAULT NULL after ti_expert_verified_date_original;
 * ALTER TABLE titus_intl ADD COLUMN ti__summary_author_name varchar(255) DEFAULT NULL after ti_summary_created_date;
 */
class TSQuickSummaryCreated extends TitusStat {
	private $dates = [];
	private $names = [];
	private $isSheetProcessed = false;

	public function getPageIdsToCalc($dbr, $date) {
		if (Misc::isIntl()) {
			return [];
		}
		if (!$this->isSheetProcessed) {
			$this->processSpreadsheet($dbr);
		}
		return array_keys($this->dates);
	}

	public function calc($dbr, $r, $t, $pageRow) {
		$aid = $pageRow->page_id;
		$date = null;
		$name = null;

		if ( !Misc::isIntl() ) {
			if ( isset( $this->dates[$aid] ) ) {
				$date = $this->dates[$aid];
			}
			if ( isset( $this->names[$aid] ) ) {
				$name = $this->names[$aid];
			}
		}

		$result = [
			'ti_summary_created_date' => $date,
			'ti_summary_author_name' => $name
		];

		return $result;
	}

	private function processSpreadsheet($dbr) {
		print __CLASS__ . ": fetching 'Quick Summary Created Dates and Authors' spreadsheet\n";
		$this->isSheetProcessed = true;

		try {
			$sheet =@ new GoogleSpreadsheet();
			$cols = $sheet->getSheetData(WH_TITUS_QUICK_SUMMARY_CREATED_DATE_GOOGLE_DOC, 'production!A2:D');
		} catch (Exception $e) {
			$this->reportError("Problem fetching spreadsheet: " . $e->getMessage());
			return;
		}

		$errors = 0;
		foreach ($cols as $col) {
			if ( !isset( $col[2] ) ) {
				continue;
			}
			$aid = ( is_numeric($col[0]) && (int) $col[0] > 0 ) ? (int) $col[0] : null;

			$name = null;
			if ( isset( $col[3] ) ) {
				$name = $col[3];
			}
			if ( $aid && $name ) {
				$this->names[$aid] = $name;
			}

			$date = $col[2] ? $this->fixDate($col[2]) : null;
			if ($aid && $date) {
				$this->dates[$aid] = $date;
			} else {
				$errors++;
			}
		}

		if ($errors * 100 > count($this->dates)) { // 1% of 25k rows = 250 errors
			$this->reportError("Encountered {$errors} parsing errors in WH_TITUS_ORIGINAL_VERIFICATIONS_GOOGLE_DOC");
		}

		$ids = array_keys($this->dates);
		$this->checkForRedirects($dbr, $ids);
		$this->checkForMissing($dbr, $ids);
	}
}

/**
 * Get info about greenboxes
 *
 * How many regular and how many expert greenboxes on each article
 *
 * alter table titus_intl add column `ti_num_greenbox_not_expert` int(10) NOT NULL default 0 after `ti_inbound_links`;
 * alter table titus_intl add column `ti_num_greenbox_expert` int(10) NOT NULL default 0 after `ti_num_greenbox_not_expert`;
 *
 * TODO: Reuben notes that after this code was put live (Apr 29, 2019), the nightly
 * Titus run slowed down by 1 hour. It now consistently takes 8h to run Titus in all
 * languages, and it took 7 hours before adding this TSGreenBox stat.
 */
class TSGreenBox extends TitusStat {

	public function getPageIdsToCalc($dbr, $date) {
		return (TitusDB::ALL_IDS);
	}

	public function calc($dbr, $r, $t, $pageRow) {
		$text = Wikitext::getSection(ContentHandler::getContentText( $r->getContent() ), wfMessage('steps'), true);
		$text = $text[0];

		$regex_gb = '/{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX.'.*?}}/is';
		$regex_gbe = '/{{'.GreenBox::GREENBOX_EXPERT_TEMPLATE_PREFIX.'.*?}}/is';

		$stats['ti_num_greenbox_not_expert'] = preg_match_all($regex_gb, $text, $matches);
		$stats['ti_num_greenbox_expert'] =preg_match_all($regex_gbe, $text, $matches);

		return $stats;
	}
}

/**
 * ti_active_coauthor: indicates what kind of coauthor is currently being displayed on an article
 *
 * ALTER TABLE titus_intl ADD COLUMN ti_active_coauthor varchar(32) NOT NULL default '' AFTER ti_expert_verified_date_original;
 */
class TSActiveCoauthor extends TitusStat {

	public function getPageIdsToCalc($dbr, $date) {
		return TitusDB::ALL_IDS;
	}

	public function calc($dbr, $r, $t, $pageRow)
	{
		$aid = $t->getArticleID();
		$verifiers = [];
		Hooks::run( 'BylineStamp', [ &$verifiers, $aid ] );
		$group = SocialStamp::getCoauthorGroup($verifiers);

		return [ 'ti_active_coauthor' => $group ];
	}
}
