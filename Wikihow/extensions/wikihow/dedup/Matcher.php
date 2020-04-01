<?php

class DedupMatcher {
	public static function updateMatches() {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);
		$sql = "select ql.ql_query as q1, ql2.ql_query as q2 from dedup.query_lookup ql join dedup.query_lookup ql2 on ql2.ql_query=ql.ql_query group by ql.ql_query, ql2.ql_query order by q1";
		$res = $dbr->query($sql, __METHOD__);
		$query = false;
		$matches = array();
		foreach($res as $row) {
			$matches[$row->q1][] = $row->q2;
		}

	}
}
