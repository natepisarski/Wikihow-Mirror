<?php
global $IP;
require_once("$IP/extensions/wikihow/dedup/OAuth.php");

/**
 * This class uses an algorithm for Deduping queries.
 *
 * The algorithm works as follows:
 * 1) The first 50 URLs are fetched for each query
 * 2) The number of URLs shared between the two queries is the matching score
 *
 * The following tables are used:
 * query_lookup Stores the the URLS and when they were fetched
 * query_lookup_log Logs the fetch of queries
 */
class DedupQuery {
	// After this interval, we will refresh search results to
	// keep the data driving the algorithm current
	const SEARCH_REFRESH_INTERVAL = 7776000; // 60*60*24*90 i.e. 90 days

	/**
	 * Fetch a query from Bing, and save in the database for Dedup query matching
	 */
	public static function fetchQuery($query, $ts) {
		if (!$query) {
			return;
		}
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select min(ql_time_fetched) as ts from dedup.query_lookup where ql_query=" . $dbr->addQuotes($query);
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$oldTs = $row->ts;
		}
		if ($oldTs > $ts) {
			$dbw = wfGetDB(DB_MASTER);
			wfDebugLog('dedup', "Fetched result from db: $query");
			$sql = "insert into dedup.query_lookup_log(qll_query, qll_result, qll_timestamp) values(" . $dbw->addQuotes($query) . "," . $dbw->addQuotes("exists") . "," . $dbw->addQuotes(wfTimestampNow()) . ")";
			$dbw->query($sql, __METHOD__);
			return;
		}
		try {
			$cc_key  = WH_YAHOO_BOSS_API_KEY;
			$cc_secret = WH_YAHOO_BOSS_API_SECRET;
			$url = "http://yboss.yahooapis.com/ysearch/web";
			$args = array();
			$args["q"] = $query;
			$args["format"] = "json";

			wfDebugLog('dedup', "Curling results from $url: $query");
			$start = microtime(true);
			$consumer = new OAuthConsumer($cc_key, $cc_secret);
			$request = OAuthRequest::from_consumer_and_token($consumer, NULL,"GET", $url, $args);
			$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
			$url = sprintf("%s?%s", $url, OAuthUtil::build_http_query($args));
			$ch = curl_init();
			$headers = array($request->to_header());
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60); // timeout connection after 60s
			$rsp = curl_exec($ch);
			$duration = microtime(true) - $start;
			wfDebugLog('dedup', "Received results after " . sprintf("%.4f", $duration) . "s. Response: $rsp");
			$results = json_decode($rsp);
			//wfDebugLog('dedup', "Decoded: " . print_r($results,true));
			if ($results
				&& $results->bossresponse
				&& $results->bossresponse->responsecode == 200
				&& $results->bossresponse->web->results
			) {
				$n = 0;
				$dbw = wfGetDB(DB_MASTER);
				foreach ($results->bossresponse->web->results as $result) {
					$n++;
					$sql = "insert into dedup.query_lookup(ql_query,ql_url,ql_pos,ql_time_fetched) values(" . $dbw->addQuotes($query) . "," . $dbw->addQuotes($result->url) . "," . $dbw->addQuotes($n) . "," . $dbw->addQuotes(wfTimestampNow()) . ") on duplicate key update ql_pos=" . $dbw->addQuotes($n) . ", ql_time_fetched=" . $dbw->addQuotes(wfTimestampNow());
					$dbw->query($sql, __METHOD__);
				}
				$sql = "insert into dedup.query_lookup_log(qll_query, qll_result, qll_timestamp) values(". $dbw->addQuotes($query) . "," . $dbw->addQuotes('success') . "," . $dbw->addQuotes(wfTimestampNow()) . ")";
				$dbw->query($sql, __METHOD__);
			} else {
				wfDebugLog('dedup', "Received malformed Yahoo Boss result for $query. Curl error (if any): " . curl_error($ch) . " Response: " . $rsp);
				$dbw = wfGetDB(DB_MASTER);
				$sql ="insert into dedup.query_lookup_log(qll_query, qll_result, qll_timestamp, qll_timestamp, qll_comment) values(" . $dbw->addQuotes($query) . "," . $dbw->addQuotes('badresponse') . "," . $dbw->addQuotes(wfTimestampNow()) . "," . $dbw->addQuotes("Response : " . ($results ? print_r($results,true) : ''));
				$dbw->query($sql, __METHOD__);
			}
		}
		catch(Exception $ex) {
			wfDebugLog('dedup', "Got fetch exception for $query: " . $ex);
			$dbw = wfGetDB(DB_MASTER);
			$sql = "insert into dedup.query_lookup_log(qll_query, qll_result, qll_timestamp, qll_comment) values(" . $dbw->addQuotes($query) . "," . $dbw->addQuotes("exception") . "," . $dbw->addQuotes(wfTimestampNow()) . "," . $dbw->addQuotes($ex->getMessage()) .  ")";
			$dbw->query($sql, __METHOD__);
		}
	}

	/**
	 * Produce a how to query from the title text of an article. This query
	 * will be used for Dedup matching
	 */
	public static function getQueryFromTitleText($titleText) {
		$query = str_replace("\"","",$titleText);
		$query = strtolower($query);
		$query = "how to " . $query;
		return($query);
	}

	/**
	 * Add an article title to the Dedup so dedup queries can match against it.
	 * @param title Title object to add
	 * @param lang Language of the title to add
	 */
	public static function addTitle($title, $lang) {
		if (!$title) {
			return("");
		}
		$titleText = $title->getText();
		if (!$titleText) {
			return("");
		}
		$pageId = $title->getArticleID();
		$query = self::getQueryFromTitleText($titleText);
		if (!$query) {
			return("");
		}
		self::fetchQuery($query, wfTimestamp(TS_MW, time() - self::SEARCH_REFRESH_INTERVAL));
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select count(*) as ct from dedup.title_query where tq_title=" . $dbr->addQuotes($titleText);
		$res = $dbr->query($sql, __METHOD__);
		$ct = 0;
		foreach ($res as $row) {
			$ct = $row->ct;
		}
		$dbw = wfGetDB(DB_MASTER);

		if ($ct == 0) {
			$sql = "insert into dedup.title_query(tq_page_id,tq_lang,tq_title,tq_query) values(" . $dbw->addQuotes($title->getArticleID()) . "," . $dbw->addQuotes($lang) . "," . $dbw->addQuotes($titleText) . "," . $dbw->addQuotes($query) . ")";
			$dbw->query($sql, __METHOD__);
			$sql = "insert into dedup.title_update_log(tul_title, tul_lang, tul_page_id, tul_page_action, tul_timestamp) values(" . $dbw->addQuotes($titleText) . "," . $dbw->addQuotes($lang) . "," . $dbw->addQuotes($pageId) . "," . $dbw->addQuotes('a') . "," . $dbw->addQuotes(wfTimestampNow()) . ")";
			$dbw->query($sql, __METHOD__);
		}
		else {
			$sql = "insert into dedup.titus_update_log(tul_title, tul_lang, tul_page_id, tul_page_action,  tul_timestamp) values(" . $dbw->addQuotes($titleText) . "," . $dbw->addQuotes($lang) . "," . $dbw->addQuotes($pageId) . "," . $dbw->addquotes('fa') . "," . $dbw->addQuotes(wfTimestampNow()) .  ")";
		}
		return($query);
	}

	/**
	 * Remove old title from the Dedup system
	 * @param titleText Text of the title
	 * @param lang Langauge of the title to remove
	 */
	public static function removeTitle($titleText, $lang) {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select * from dedup.title_query where tq_title=" . $dbr->addQuotes($titleText);
		$pageId = 0;
		$pageLang = "en";
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$pageLang = $row->tq_lang;
			$pageId = $row->tq_page_id;
		}
		$dbw = wfGetDB(DB_MASTER);
		$sql = "delete from dedup.title_query where tq_lang=" . $dbw->addQuotes($lang) . " AND tq_title=" . $dbw->addquotes($titleText);
		$dbw->query($sql, __METHOD__);
		$sql = "insert into dedup.title_update_log(tul_title, tul_lang, tul_page_id,tul_page_action, tul_timestamp) values(" . $dbw->addQuotes($titleText) . "," . $dbw->addQuotes($lang) . "," . $dbw->addQuotes($pageId) . ",'d'," . $dbw->addQuotes(wfTimestampNow()) . ")";
		$dbw->query($sql, __METHOD__);
	}

	public static function addQuery($query) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query($sql, __METHOD__);
		self::fetchQuery($query, wfTimestamp(TS_MW, self::SEARCH_REFRESH_INTERVAL));
		$dbw = wfGetDB(DB_MASTER);
		$sql = "insert ignore into dedup.special_query(sq_query,sq_import_date) values(" . $dbw->addQuotes($query) . ",now())";
		$dbw->query($sql, __METHOD__);
	}

	/**
	 * Find matches for the designated queries
	 */
	public static function matchQueries($queries) {
		$dbr = wfGetDB(DB_REPLICA);
		$queriesN = array();
		foreach ($queries as $query) {
			$queriesN[] = $dbr->addQuotes($query);
		}
		$dbw = wfGetDB(DB_MASTER);
		$sql = "insert ignore into dedup.query_match select ql.ql_query as query1, ql2.ql_query as query2, count(*) as ct from dedup.query_lookup ql join dedup.query_lookup ql2 on ql2.ql_url=ql.ql_url where ql.ql_query in (" . implode(',',$queriesN) . ") group by ql.ql_query, ql2.ql_query";
		$dbw->query($sql, __METHOD__);

	}

	/**
	 * Find categories
	 */
	public static function getCategories($query) {
			$dbr = wfGetDB(DB_REPLICA);
			$sql = "select cl_to, sum(ct) as score from categorylinks join dedup.title_query on tq_page_id=cl_from and tq_lang='en' join dedup.query_match on tq_query=query2 and tq_query<>query1 where query1=" . $dbr->addQuotes($query) . " group by cl_to order by score desc";
			$res = $dbr->query($sql, __METHOD__);
			$cats = array();
			foreach ($res as $row) {
				$cats[] = array('cat' => $row->cl_to, 'score' => $row->score);
			}
			return($cats);
	}

	/*
	 * Find articles related to the following
	 */
	public static function getRelated($title, $minScore = 1) {
		global $wgLanguageCode;

		$dbr = wfGetDB(DB_REPLICA);
		$query = DedupQuery::addTitle($title, $wgLanguageCode);
		if (!$query) {
			return(array());
		}
		$sql = "select query1, query2, ct, tq_page_id from dedup.query_match join dedup.title_query on tq_query=query2 where query1 =" . $dbr->addQuotes($query) . " and query1<> query2 order by query1, ct desc";
		$res = $dbr->query($sql, __METHOD__);
		$titles = array();
		foreach ($res as $row) {
			if ($row->ct >= $minScore) {
				$t = Title::newFromId($row->tq_page_id);
				if ($t) {
					$titles[] = array('title' => $t, 'ct' =>$row->ct);
				}
			}
		}
		return($titles);
	}
}
