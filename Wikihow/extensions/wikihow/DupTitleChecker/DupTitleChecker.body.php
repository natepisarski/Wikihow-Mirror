<?php
/* This page allows you to check if a list of titles already exists as a page on wikihow
 * the titles are queried using search api ( LSearch.php) which uses bing to get results ( paid service, be judicious with its use)
 * the results are manually resolved using dedup tool
 * the results can be obtained using the special page admindeduptool
 *
 * all queries in lower case
 * The search results are cached in dedup.bing_search_title table
 * This script also checks deduptool resolved results to see if a particular query had been solved before
*/

/*
 * Class to store output results of the dedup process
 */
class DupNode {

	const DUP_DUP = "Duplicate";
	const DUP_LIKELY = "Likely Duplicate";
	const DUP_POSSIBLE ="Possible Duplicate";

	function __construct( $n ) {
		$name = $n;
		$duplicates = [];
		$likely = [];
		$possible = [];
	}

	public function getCount() {
		return count( $this->duplicates ) + count( $this->likely ) + count( $this->possible );
	}

	public function addItem( $n, $label ) {
		if ( $label == self::DUP_DUP ) {
			$this->duplicates[] = $n;
		}
		elseif ( $label == self::DUP_LIKELY ) {
			$this->likely[] = $n;
		}
		elseif ( $label == self::DUP_POSSIBLE ) {
			$this->possible[] = $n;
		}
	}

	public function inTheNode( $n ) {
		if ( ( $this->duplicate ) && in_array( $n, $this->duplicates ) ) {
			return self::DUP_DUP;
		}
		elseif ( ( $this->likely ) && in_array( $n, $this->likely ) ) {
			return self::DUP_LIKELY;
		}
		elseif ( ( $this->possible ) && in_array( $n, $this->possible ) ) {
			return self::DUP_POSSIBLE;
		}
		else {
			return null;
		}
	}

	public function getDuplicatesString() {
		if ( $this->duplicates ) {
			return join( ";", $this->duplicates );
		}
		return "";
	}

	public function getLikelyString() {
		if ( $this->likely ) {
			return join( ";", $this->likely );
		}
		return "";
	}

	public function getPossibleString() {
		if ( $this->possible ) {
			return join( ";" , $this->possible );
		}
		return "";
	}

}
class DupTitleChecker extends UnlistedSpecialPage {

	private $errors = [];
	private $queries = [];
	private $orgQueries = [];
	private $queryResults = [];
	private $scoredResults = [];
	private $workDone = false;

	const SEARCH_REFRESH_INTERVAL = 7776000; // 60*60*24*90 i.e. 90 days

	const DUP_DUP_LIMIT = 0.7;
	const DUP_LIKELY_LIMIT = 0.45;
	const DUP_POSSIBLE_LIMIT = 0.2;

	function __construct() {
		$this->action = $GLOBALS[ 'wgTitle' ]->getPartialUrl();
		parent::__construct( $this->action );
		$GLOBALS[ 'wgHooks' ][ 'ShowSideBar' ][] = [ 'DupTitleChecker::removeSideBarCallback' ];
	}

	static function removeSideBarCallback( &$showSideBar ) {
		$showSideBar = false;
		return true;
	}

	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	private function getQueries() {
		$queries = $this->getRequest()->getVal( 'queries' );

		if ( !$queries ) {
			return false;
		}

		$qs = preg_split( "@[\r\n]+@", $queries );
		$oq = [];

		foreach ( $qs as $q ) {
			if ( !preg_match( "@^how to@i", $q, $matches ) ) {
				$oq[] = "how to " . strtolower( $q );
			}
			else {
				$oq[] = strtolower( $q );
			}
		}
		$this->queries = $oq;
		$this->orgQueries = $oq;
	}

	private function processDbResults( $rows , $timeStamp ) {
		foreach ( $rows as $row ) {
			if ( in_array( $row->query, $this->queries ) ) {

				// if not already resolved, add to dedup tool
				$this->addToDedupTool( $timeStamp, $row->query, $row->matches, $row->finalM );

				// remove from to solve list
				$key = array_search( $row->query, $this->queries );
				unset( $this->queries[$key] );
			}
		}
	}

	private function addToDedupTool( $importTimestamp, $query, $enc_ids, $exactMatch ) {
		if ( count ( $enc_ids ) == 0 ) {
			// nothing found
			DedupTool::addToTool( $importTimestamp, "", $query, -1 );
		}
		elseif ( $exactMatch ) {
			// already found a good match
			DedupTool::addToTool( $importTimestamp, $enc_ids, $query, $exactMatch );
		}
		else {
			DedupTool::addToTool( $importTimestamp, $enc_ids, $query );
		}

		$this->workDone = true;
	}

	private function getJobStatus() {
		$html = "";

		foreach ( $this->errors as $error ) {
				$html .= '<p>' .$error .'</p>';
		}

		return $html;
	}

	private function getSimilarTitles( $query , $web = false ) {
		$l = new LSearch();

		if ( $web ) {
			$hits = $l->getBingSearchResults( $query, 0, 50, LSearch::SEARCH_WEB );
		}
		else {
			$hits = $l->externalSearchResultTitles( $query, 0, 10, 0, LSearch::SEARCH_INTERNAL );
		}

		return $hits;
	}

	/* Save results to a db, avoid multiple searches for the same term
	 * the results expire after 3 months
	 * this script would delete anything older than 3 months to keep table reasonable
	 */
	private function saveWebQueryResults( $timeStamp, $query, $url, $rank ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert( 'dedup.bing_web_search',
						[ 'bws_url' => $url,
						'bws_rank' => $rank,
						'bws_timestamp' => $timeStamp,
						'bws_query' => $query ], __METHOD__, []
					);
	}

	// save results to a db, avoid multiple searches for the same term
	private function saveQueryResults( $timeStamp, $query, $enc_ids, $exactMatch ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert( 'dedup.bing_title_search',
						[ 'bts_match' => $enc_ids,
						'bts_final' => $exactMatch,
						'bts_timestamp' => $timeStamp,
						'bts_query' => $query ], __METHOD__, []
					);
	}

	private function scoreRBO( $arr1, $arr2 ) {
		$rbo = new RankBiasedOverlap();
		$score = $rbo->	rankedOverlap( $arr1, $arr2, 0.9 );
		return $score;
	}

	private function scorePair( $arr1, $arr2 ) {
		 return count( array_intersect( $arr1, $arr2 ) );
	}

	// this searches previously saved results from bing search
	private function findInWebSearches() {
		$dbr = wfGetDB( DB_REPLICA );
		$queryDB = [];

		$queryChunks = array_chunk( $this->queries, 400 );

		$colms = [ 'bws_timestamp as timestamp',
					'bws_query as query',
					'bws_url as url',
					'bws_rank as rank' ];
		// look in db to see if it exists ( in chunks, the intput size > 2000 )
		foreach ( $queryChunks as $chunk ) {
			$conds = [ "bws_query" => $chunk ];
			$rows = $dbr->select( 'dedup.bing_web_search',
						$colms,
						$conds,
						__METHOD__,
						[] );

			if ( $rows ) {
				foreach ( $rows as $row ) {
					// Add to the result set
					$this->queryResults[$row->query][] = $row->url;
					// Remove it from the query list
					$key = array_search( $row->query, $this->queries );
					if ( $key ) {
						unset( $this->queries[$key] );
					}
				}
			}
		}
		$this->queries = array_values( $this->queries );
	}

	/* At the start of a run delete anything that is
	 * older than 3 months. We don't use these results
	 */
	private function deleteOldData() {
		$expirytTimestamp = wfTimestamp( TS_MW, time() - self::SEARCH_REFRESH_INTERVAL );

		$dbw = wfGetDB( DB_MASTER );
		$conds[] = "bws_timestamp < ". $expirytTimestamp;
		$dbw->delete( 'dedup.bing_web_search', $conds );
	}

	/* Function to get combinations
	*/
	private function combination( $m, $a ) {
		if ( !$m ) {
			yield [];
			return;
		}
		if ( !$a ) {
			return;
		}

		$h = $a[0];
		$t = array_slice( $a, 1 );

		foreach ( $this->combination( $m - 1, $t ) as $c ) {
			yield array_merge( [ $h ], $c );
		}

		foreach ( $this->combination( $m, $t ) as $c ) {
			yield $c;
		}
	}

	private function getScoreLabel( $score ) {
		if ( $score >= self::DUP_DUP_LIMIT ) {
			return DupNode::DUP_DUP;
		}
		elseif ( $score < self::DUP_DUP_LIMIT && $score >= self::DUP_LIKELY_LIMIT ) {
			return DupNode::DUP_LIKELY;
		}
		elseif ($score < self::DUP_LIKELY_LIMIT && $score > self::DUP_POSSIBLE_LIMIT ) {
			return DupNode::DUP_POSSIBLE;
		}
		else {
			return "None";
		}
	}

	private function addToScoredResults ( $pair, $label ) {
		if ( array_key_exists( $pair[0] , $this->scoredResults ) ) {
			// add to the list
			$this->scoredResults[$pair[0]]->addItem( $pair[1], $label );
		}
		else {
			//create a new element
			$newNode = new DupNode( $pair[0] );
			$newNode->addItem( $pair[1], $label );
			$this->scoredResults[$pair[0]] = $newNode;
		}
	}

	private function findDuplicates() {
		// make combinations of the array - query results
		// check how many match
		foreach ( $this->combination( 2, array_keys( $this->queryResults ) ) as $pair ) {
			$score = $this->scorePair( $this->queryResults[$pair[0]], $this->queryResults[$pair[1]] );

			if ( $score <> 0 ) {
				$rbo = $this->scoreRBO( $this->queryResults[$pair[0]], $this->queryResults[$pair[1]] );

				$label = $this->getScoreLabel( $rbo );
				$this->addToScoredResults( $pair, $label );
			}
			else {
				$this->addToScoredResults( $pair, "");
			}
		}
	}

	// this searches previously resolved results from DedupTool
	private function findInDedupResolved( $timeStamp ) {
		$dbr = wfGetDB( DB_REPLICA );
		$queryDB = [];

		$queryChunks = array_chunk( $this->queries, 400 );
		$colms = [ 'ddt_import_timestamp as timestamp',
						'ddt_query as query',
						'ddt_final as finalM',
						'ddt_to as matches' ];

		// look in db to see if it exists ( in chunks, the intput size can be > 2000 )
		foreach ( $queryChunks as $chunk ) {
			$conds = [ "ddt_query" => $chunk ];
			$rows = $dbr->select( 'dedup.deduptool',
							$colms,
							$conds,
							__METHOD__,
							[] );

			if ( $rows ) {
				$this->processDbResults( $rows, $timeStamp );
			}
		}
		$this->queries = array_values( $this->queries );
	}

	// this searches previously caches results from bing search
	private function findInCache( $timeStamp ) {
		$dbr = wfGetDB( DB_REPLICA );
		$queryDB = [];

		$queryChunks = array_chunk( $this->queries, 400 );

		$colms = [ 'bts_timestamp as timestamp',
					'bts_query as query',
					'bts_final as finalM',
					'bts_match as matches' ];

		// look in db to see if it exists ( in chunks, the intput size > 2000 )
		foreach ( $queryChunks as $chunk ) {
			$conds = [ "bts_query" => $chunk ];
			$rows = $dbr->select( 'dedup.bing_title_search',
						$colms,
						$conds,
						__METHOD__,
						[] );

			if ( $rows ) {
				$this->processDbResults( $rows, $timeStamp );
			}
		}
		$this->queries = array_values( $this->queries );
	}

	private function getOrderedResults () {
		$nrr = [];

		foreach( $this->scoredResults as $key=>$node ) {
			$cnt = $node->getCount();
			$nrr[$key] = $cnt;
		}
		arsort( $nrr );
		return $nrr;
	}

	private function alreadyPrintedResult( $key, $seenList ) {

		foreach( $seenList as $seen ) {
			if ( array_key_exists( $seen, $this->scoredResults ) ) {
				$node = $this->scoredResults[$seen];
				$res = $node->inTheNode( $key );
				if ( isset( $res ) ) {
					return array ( $res, $seen ) ;
				}
			}
		}
		return array ( null, null );
	}

	private function dedupResults() {
		$this->getOutput()->setArticleBodyOnly( true );

		$status_primary = "Primary";
		$status_write = "WRITE";
		$seen = [];

		// file header
		header( "Content-Type: text/csv" );
		header( 'Content-Disposition: attachment; filename= DupList'.date( "_Y-m-d_H_i_s" ).'.csv' );

		$header = [ 'Query', 'Status', 'Duplicate Queries', 'Likely Queries', 'Possible Queries' ];

		$fileHandle = fopen( 'php://output', 'w' );
		fputcsv( $fileHandle, $header, ',', '"' );

		$order = $this->getOrderedResults();

		foreach ( $order as $key=>$ct ) {
			$nrow = [];
			//safety check, it should always be there
			if ( array_key_exists( $key, $this->scoredResults ) ) {
				$node = $this->scoredResults[$key];
				// check node was already printed, but only in what is seen
				list( $nl, $link ) = $this->alreadyPrintedResult( $key, $seen );

				if ( $nl ) {
					if ( $nl == DupNode::DUP_DUP ) {
						$nrow = [ $key, $nl, $link, '' , '' ];
					}
					elseif ( $nl == DupNode::DUP_LIKELY ) {
						$nrow = [ $key, $nl, '' , $link, '' ];
					}
					elseif ( $nl == DupNode::DUP_POSSIBLE ) {
						$nrow = [ $key, $nl, '' , '', $link ];
					}
				}
				else {
					// if it wasn't printed as result, print new.
						if ( $node->getCount() == 0 ) {
							// to write node
							$nrow = [$key, $status_write ];
						}
						else {
							$nrow = [$key, $status_primary, $node->getDuplicatesString(), $node->getLikelyString(), $node->getPossibleString() ];
						}
				}
				$seen[] = $key;
				//write the line
				fputcsv( $fileHandle, $nrow, ',', '"' );
			}
		}
		// sometimes in rare cases if the last pair does not have any matches
		// it skips a line
		// this ensures the output is same as input
		foreach( $this->orgQueries as $query ) {
			if ( !in_array( $query, $seen ) ) {
				$nrow = [ $query, $status_write ];
				fputcsv( $fileHandle, $nrow, ',', '"' );
			}
		}
		exit( 0 );
	}

	// Find exact duplicates, no need to search web and do the work on these
	// we know they are duplicates
	private function findExactMatches() {
		foreach ( $this->combination( 2, $this->queries ) as $pair ) {
			if ( $pair[0] == $pair[1] ) {
				// save results
				$this->addToScoredResults( $pair, DupNode::DUP_DUP );
				// Remove one of the matches from the query list
				// still need to compare this with others just not with itself
				$key = array_search( $pair[0], $this->queries );
				unset( $this->queries[$key] );
			}
		}
		$this->queries = array_values( $this->queries );
	}

	private function processTitles() {
		$timeStamp = wfTimestampNow();
		$workDone = false;

		// check existing cache and previously resolved titles
		$this->findInDedupResolved( $timeStamp );
		$this->findInCache( $timeStamp );

		foreach ( $this->queries as $query ) {
			// get similar titles
			// do search if not found.
			$hits = $this->getSimilarTitles( $query );
			if ( count( $hits ) > 0 ) {
				$ids = [];
				$exactMatch = 0;

				foreach ( $hits as $hit ) {
					$thisId = $hit->getArticleID();
					$ids[] = $thisId;
					if ( trim( $query ) == trim( strtolower( "how to " .$hit ) ) ) {
						$exactMatch = $thisId;
					}
				}
				// top 10 only
				if ( count( $ids ) > 10 ) {
					$ids = array_slice( $ids, 0, 10 );
				}
				$this->addToDedupTool( $timeStamp, $query, json_encode( $ids ), $exactMatch );

				// add to DB
				$this->saveQueryResults( $timeStamp, $query, json_encode( $ids ), $exactMatch );
				$workDone = true;
			}
			else {
				// none found
				$this->addToDedupTool( $timeStamp, $query, null, null );
			}
		}
		if ( $this->workDone ) {
			$this->errors[] = "Queries loaded to Deduptool, results can be downloaded from AdminDedup page";
		}
	}

	/* This function dedups a list within itself
	 */
	private function dedupList() {
		$timeStamp = wfTimestampNow();
		// Delete old data from db. Only use 3 month old results
		$this->deleteOldData();
		// Find exact duplicates, optimization to remove the work for exact dups
		$this->findExactMatches();
		// Find old usable results
		$this->findInWebSearches();

		foreach ( $this->queries as $query ) {
			$hits = $this->getSimilarTitles( $query, true );
			if ( count( $hits ) > 0 ) {
				$rank = 0;

				foreach ( $hits as $hit ) {
					// Add to the result set
					$this->queryResults[$query][] = $hit['url'];
					// Save to the db
					$this->saveWebQueryResults( $timeStamp, $query, $hit['url'], $rank );
					$rank += 1;
				}
			}
			else {
				$this->errors[] = " Nothing found for the query " . $query;
			}
		}
		// Collected data, now process it
		$this->findDuplicates();
		// out csv
		$this->dedupResults();
	}
	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute( $par ) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userName = $user->getName();

		// Check permissions
		$userGroups = $user->getGroups();
		if ( ( $userName != 'Rjsbhatia' ) && ( $user->isBlocked() || !( in_array( 'staff', $userGroups ) ) ) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		set_time_limit( 2 * 60 * 60 );
		ini_set( 'memory_limit', '512M' );

		$val = $req->getVal( 'listdedup' );
		$this->getQueries();

		if ( isset( $val ) ) {
			$this->dedupList();
		}
		else {
			$this->processTitles();
		}

		$out->setHTMLTitle( 'Check for duplicates - wikiHow' );
		$out->setPageTitle( 'Check for duplicates' );

		$must_vars = [
						'action' => $this->action,
						'jobStatus' => $this->getJobStatus()
						];

		$options = [ 'loader' => new Mustache_Loader_FilesystemLoader( __DIR__ ) , ];
		$m = new Mustache_Engine( $options );
		$tmpl = $m->render( 'DupTitleChecker.mustache', $must_vars );

		if ( $html ) {
			$tmpl .= $html;
		}

		$out->addHTML( $tmpl );
		$out->addModules( 'ext.wikihow.DupTitleChecker' );
	}
}
