<?php

global $IP;
require_once("$IP/extensions/wikihow/dedup/dedupQuery.php");

class ApiDedup extends ApiBase {

	public function execute() {
		$params = $this->extractRequestParams();
		$command = $params['subcmd'];
		$result = $this->getResult();
		$module = $this->getModuleName();

		switch ($command) {
			case 'queries':
				$internal = $params['internal'];
				$clusterScore = $params['clusterScore'];
				$row = $this->getQueries($params['queries'], $internal, $clusterScore);
				$result->addValue(null, $module, $row);
				break;
		}
	}

	/**
	 * Determines teh queries that match
	 * @param internal Only match against queries specified instead of the entire database
	 * @param clusterScore Cluster all queries above a certain score
	 */
	function getQueries($queries, $internal, $clusterScore) {
		$queries = preg_split("@\|@",$queries);
		$dbw = wfGetDB(DB_MASTER);
		$queryE = array();
		foreach ($queries as $query) {
			if ($query) {
				DedupQuery::addQuery($query);
				$queryE[] = $dbw->addQuotes($query);
			}
		}
		DedupQuery::matchQueries($queries, $internal);
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select query1, query2, ct, tq_title, tq_page_id from dedup.query_match left join dedup.title_query on tq_query=query2 where query1 in (" . implode($queryE,",") . ")";
		if ($internal) {
			$sql .= " and query2 in (" . implode($queryE,",") . ")";
		}
		$sql .= " order by query1, ct desc";
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();

		if (!$clusterScore) {
			foreach ($res as $row) {
				$ret[$row->query1][] = array('query' => $row->query2, 'score' => $row->ct, 'title' => $row->tq_title, 'aid' => $row->tq_page_id);
			}
		}
		else {
			$clusters = array();
			$clusterLookup = array();
			foreach ($queries as $query) {
				$clusters[$query] = $query;
				$clusterLookup[$query] = array($query);
			}
			foreach ($res as $row) {
				$ret[$row->query2] = array('query' => $row->query2, 'title' => $row->tq_title, 'aid' => $row->tq_page_id);
				if ($row->ct >= $clusterScore) {
					if (!isset($clusters[$row->query2])) {
						$clusters[$row->query2] = $row->query2;
						$clusterLookup[$row->query2] = array($row->query2);
					}

					$cl1 = $clusters[$row->query1];
					$cl2 = $clusters[$row->query2];
					if ($cl2 && $cl1 != $cl2) {
						$clusterLookup[$cl1] = array_merge($clusterLookup[$cl1],$clusterLookup[$cl2]);
						foreach ($clusterLookup[$cl2] as $q) {
							$clusters[$q] = $cl1;
						}
						unset($clusterLookup[$cl2]);
					}
				}
			}
			$ret2 = array();
			foreach ($clusterLookup as $name => $cl) {
				$fullCl = array();
				foreach ($cl as $q) {
					$fullCl[] = $ret[$q];
				}
				$ret2[] = $fullCl;
			}
			$ret = $ret2;
		}

		return($ret);
	}

	function getVersion() {
		return("1.0");
	}

	function getAllowedParams() {
		return(array('subcmd' => '', 'queries' => '', 'internal' => '', 'clusterScore' => ''));
	}
}
