<?php

/*
 * GUI tool for determining if queries having matching
 * queries on the site.
 */
class Dedup extends UnlistedSpecialPage {
	private $queriesR;
	private $queryMatches;
	private $urlMatches;

	public function __construct() {
		parent::__construct("Dedup");

		require_once(__DIR__ . "/dedupQuery.php");
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	/**
	 * Print a line of output for the Dedup algorithm
	 */
	private function printLine() {
		$urlMatches = array();
		$first = true;
		foreach ( $this->closestUrls as $closestUrl ) {
			if ($first) {
				$first = false;
				$closeURL = $closestUrl['url'];
				$closeURLScore = $closestUrl['score'];
			}
			else {
				$urlMatches[] = $closestUrl['url'] . " (" . $closestUrl['score'] . ")";
			}
		}

		if (!$closeURLScore || $closeURLScore <= 7) {
			$todo = "write";
		}
		elseif ($closeURLScore >= 35) {
			$todo = "dup URL";
		}
		else {
			$todo = "not sure";
		}
		if (sizeof($urlMatches) > 5) {
			$urlMatches = array_slice($urlMatches, 0,5);
		}
		print $this->query . "\t" . $todo . "\t" . $closeURL . "\t" . $closeURLScore . "\t" . implode("| ",$this->queryMatches) . "\t" . implode("| ",$urlMatches) . "\n";

	}

	private function processLineForTool($importTimestamp, $query) {
		$idMatches = [];
		$first = true;
		foreach ( $this->closestUrls as $closestUrl ) {
			if ($first) {
				$first = false;
				$closeURLScore = $closestUrl['score'];
				$closeId = $closestUrl['id'];
				$idMatches[] = $closestUrl['id'];
			}
			else {
				$idMatches[] = $closestUrl['id'];
			}
		}
		if (sizeof($idMatches) > 5) {
			$idMatches = array_slice($idMatches, 0, 5);
		}
		if (!$closeURLScore) {
			//needs to be written
			DedupTool::addToTool($importTimestamp, "", $query, -1);
		}
		elseif ($closeURLScore >= 40) {
			//already found a good match
			DedupTool::addToTool($importTimestamp, json_encode($idMatches), $query, $closeId);
		}
		else {
			//lets use the tool
			DedupTool::addToTool($importTimestamp, json_encode($idMatches), $query);
		}
	}

	/**
	 * Match dedup queries against themselves instead of against other things
	 */
	public function getTopMatchBatch() {
		set_time_limit(0);
		print "\"Query\",\"Status\",\"Dup queries >= 35\",\"Dup queries 19-34\"\n";
		$dbw = wfGetDB(DB_MASTER);
		$queryE = array();
		wfDebugLog('dedup', "getTopMatchBatch: adding queries");
		foreach ($this->queriesR as $query) {
			dedupQuery::addQuery($query);
			$queryE[] = $dbw->addQuotes($query);
		}

		wfDebugLog('dedup', "getTopMatchBatch: matching queries");
		dedupQuery::matchQueries($this->queriesR);

		wfDebugLog('dedup', "getTopMatchBatch: fetching results from mysql");
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT query1, query2, ct " .
			   "FROM dedup.query_match " .
			   "WHERE query1 <> query2 " .
			   "  AND query1 IN (" . implode($queryE,",") . ") " .
			   "  AND query2 IN (" . implode($queryE,",") . ") " .
			   "GROUP BY query1, query2 " .
			   "ORDER BY field(query1," . implode($queryE,",") . ")";
		$res = $dbr->query($sql, __METHOD__);
		$last = false;

		header("Content-Type: text/csv");
		header('Content-Disposition: attachment; filename="Dedup.csv"');
		$clusters35 = array();
		$clusters19 = array();
		$nondup = array();
		$dup = array();
		$posDup = array();
		foreach ( $res as $row ) {
			if ($row->ct >= 35) {
				$clusters35[$row->query1][] = $row->query2;
				if (!in_array($row->query1,$dup) && !in_array($row->query1, $posDup)) {
					$nondup[] = $row->query1;
				}
				if (!in_array($row->query2, $nondup)) {
					$dup[] = $row->query2;
				}
			}
			elseif ($row->ct >= 19) {
				$clusters19[$row->query1][] = $row->query2;
				if (!in_array($row->query1, $dup) && !in_array($row->query1, $posDup)) {
					$nondup[] = $row->query1;
				}
				if (!in_array($row->query2, $nondup)) {
					$posDup[] = $row->query2;
				}
			}
		}
		foreach ( $this->queriesR as $query ) {
			print "\"" . addslashes($query) . "\",\"";
			if (in_array($query, $dup)) {
				print "duplicate";
			}
			elseif (in_array($query, $posDup)) {
				print "possible duplicate";
			}
			elseif (isset($clusters35[$query])) {
				print "dup check";
			}
			else {
				print "write";
			}
			print "\",\"";
			if (isset($clusters35[$query])) {
				print addslashes(implode("\r",$clusters35[$query]));
			}
			print "\",\"";
			if (isset($clusters19[$query])) {
				print addslashes(implode("\r",$clusters19[$query]));
			}

			print "\"\n";
		}
		print "\n";
		exit;
	}


	/**
	 * Test for Alissa - Match dedup queries against themselves instead of against other things and output scores
	 */
	public function getTopMatchBatchScores() {
		set_time_limit(0);
		print "\"Query1\",\"Query2\",\"Score\"\n";
		$dbw = wfGetDB(DB_MASTER);
		$queryE = array();
		wfDebugLog('dedup', "getTopMatchBatchScores: adding queries");
		foreach ($this->queriesR as $query) {
			dedupQuery::addQuery($query);
			$queryE[] = $dbw->addQuotes($query);
		}

		wfDebugLog('dedup', "getTopMatchBatchScores: matching queries");
		dedupQuery::matchQueries($this->queriesR);

		wfDebugLog('dedup', "getTopMatchBatchScores: fetching results from mysql");
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT query1, query2, ct " .
			   "FROM dedup.query_match " .
			   "WHERE ct >= 20 " .
			   "  AND query1 <> query2 " .
			   "  AND query1 IN (" . implode($queryE,",") . ") " .
			   "  AND query2 IN (" . implode($queryE,",") . ") " .
			   "GROUP BY query1, query2 " .
			   "ORDER BY ct DESC";
		$res = $dbr->query($sql, __METHOD__);

		header("Content-Type: text/csv");
		header('Content-Disposition: attachment; filename="Dedup.csv"');
		$duplicatePairs = [];
		$matches = false;
		foreach ( $res as $row ) {
			$matches = true;
			// Check the flipped pairs to make sure the pair hasn't already been added, but in reverse
			if ($duplicatePairs[$row->query1] != $row->query2) {
				// Add the pair, but in reverse so it's easier to look up duplicate pairs
				$duplicatePairs[$row->query2] = $row->query1;
				echo "\""
					. addslashes($this->formatQuery($row->query1)) . "\",\""
					. addslashes($this->formatQuery($row->query2)) . "\",\""
					. $row->ct . "\"\n";
			}

		}

		if (!$matches) {
			echo "No matches found with scores above 20";
		}

		print "\n";
		exit;
	}

	public function getBatch() {
		set_time_limit(0);
		$dbw = wfGetDB(DB_MASTER);
		$queryE = array();
		wfDebugLog('dedup', "getBatch: adding queries");
		foreach ( $this->queriesR as $query ) {
			dedupQuery::addQuery($query);
			$queryE[] = $dbw->addQuotes($query);
		}
		wfDebugLog('dedup', "getBatch: matching queries");
		dedupQuery::matchQueries($this->queriesR);

		wfDebugLog('dedup', "getBatch: fetching results from mysql");
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT query1, query2, ct, tq_title " .
			   "FROM dedup.query_match " .
			   "LEFT JOIN dedup.title_query ON tq_query=query2 " .
			   "WHERE query1 IN (" . implode($queryE,",") . ") " .
			   "ORDER BY field(query1," . implode($queryE, ",") . "), ct DESC";
		$res = $dbr->query($sql, __METHOD__);
		$query = false;
		header("Content-Type: text/tsv");
		header('Content-Disposition: attachment; filename="Dedup.xls"');

		print "Query\tWhat to do\tclosest URL match\tclosest URL score\tquery matches >= 35\tnext closest URL matches (max 5)\n";
		$this->closestUrls = array();
		$this->query = false;
		$this->queryMatches = array();
		foreach ( $res as $row ) {
			if ($this->query != $row->query1) {
				if ($this->query) {
					$this->printLine();
				}
				$this->queryMatches = array();
				$this->closestUrls = array();
				$this->query = $row->query1;
			}
			if ($row->ct >= 35 && $row->query1 != $row->query2) {
				$this->queryMatches[] = $row->query2;
			}
			if ($row->tq_title) {
				$this->closestUrls[] = array('url' => ("http://www.wikihow.com/" . str_replace(" ","-",$row->tq_title)), 'score' => $row->ct);
			}
		}

		if ($this->query) {
			$this->printLine();
		}

		exit;
	}

	protected function formatQuery($query) {
		return preg_replace('@^how to @i', '', $query);
	}

	/**
	 * Match a batch of queries together to see which ones mean the same thing
	 */
	public function getBatchForTool() {
		set_time_limit(0);
		$dbw = wfGetDB(DB_MASTER);
		$queryE = array();
		foreach ( $this->queriesR as $query ) {
			dedupQuery::addQuery($query);
			$queryE[] = $dbw->addQuotes($query);
		}
		dedupQuery::matchQueries($this->queriesR);

		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT query1, query2, ct, tq_title, tq_page_id " .
			   "FROM dedup.query_match " .
			   "LEFT JOIN dedup.title_query ON tq_query=query2 " .
			   "WHERE query1 IN (" . implode($queryE,",") . ") " .
			   "ORDER BY field(query1," . implode($queryE, ",") . "), ct DESC";
		$res = $dbr->query($sql, __METHOD__);

		$this->closestUrls = [];
		$this->query = false;
		$this->queryMatches = [];
		foreach ( $res as $row ) {
			if ($this->query != $row->query1) {
				if ($this->query) {
					$this->processLineForTool(wfTimestampNow(), $this->query);
				}
				$this->queryMatches = [];
				$this->closestUrls = [];
				$this->query = $row->query1;
			}
			if ($row->ct >= 35 && $row->query1 != $row->query2) {
				$this->queryMatches[] = $row->query2;
			}
			if ($row->tq_title) {
				$this->closestUrls[] = array(
					'url' => ("https://www.wikihow.com/" . str_replace(" ","-",$row->tq_title)),
					'score' => $row->ct,
					'id' => $row->tq_page_id );
			}
		}

		if ($this->query) {
			$this->processLineForTool(wfTimestampNow(), $this->query);
		}

		exit;
	}

	/*
	 * Parse out the queries from the input post request,
	 * and 'how to' to the beginning of queries
	 * @return true if there are queries and false otherwise
	 */
	private function getQueries() {
		$queries = $this->getRequest()->getVal('queries');
		if (!$queries) {
			return false;
		}
		$qs = preg_split("@[\r\n]+@", $queries);
		$oq = array();
		foreach ( $qs as $q ) {
			if ( !preg_match("@^how to@i", $q, $matches) ) {
				$oq[] = "how to " . $q;
			}
			else {
				$oq[] = $q;
			}
		}
		$this->queriesR = $oq;
		wfDebugLog('dedup', "Parsed " . count($oq) . " queries");
		return true;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		$userGroups = $this->getUser()->getGroups();
		if (!in_array('staff',$userGroups)) {
			$wgOut->setRobotPolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		// Make it so that dedup can run 2h before returning results
		set_time_limit(2 * 60 * 60);
		ini_set('memory_limit', '512M');

		$action = $wgRequest->getVal('act');
		if (!$action) {
			EasyTemplate::set_path(__DIR__);
			$wgOut->addHTML(EasyTemplate::html('Dedup.tmpl.php'));
			$wgOut->addModules("ext.wikihow.Dedup");
		} elseif ($action == 'getBatch' && $this->getQueries()) {
			$internalDedup = $wgRequest->getVal('internalDedup');
			if ($internalDedup) {
				$this->getTopMatchBatch();
			} elseif ($wgRequest->getVal('internalDupTool', false)) {
				$this->getBatchForTool();
			}
			else {
				$this->getBatch();
			}
		}
	}
}
