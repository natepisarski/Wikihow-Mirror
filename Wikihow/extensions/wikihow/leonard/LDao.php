<?php
require_once 'dbutils.php';
class LDao {
	const SEC_OF_THE_DAY = 86400;
	
	/**
	 * Returns diff in days from now
	 * @param unknown $ts
	 * @return number
	 */
	static function dayDiffWithNow($ts) {
		$now = wfTimestampNow(TS_MW);
		$date = strtotime ( $ts );
		$dateNow = strtotime ( $now );
		return abs ( $dateNow - $date ) / self::SEC_OF_THE_DAY;
	}
	
	public static function getDbTopicOrSeedKeyword($seedKeyword) {
		if (empty($seedKeyword)) return null;
		$dbr = DbUtils::getDbr();
		$query = "SELECT * FROM leonard.leo_topics WHERE seed = ". $dbr->addQuotes($seedKeyword);
		return DbUtils::exDbR($query);
	}

	public static function isTitleGrpsUpdated($seedKeyword) {
		$res = self::getDbTopicOrSeedKeyword($seedKeyword);
		if ($res == null) return null;
		foreach ($res as $dbRow) {
			$titleGrpsUpdated = $dbRow->title_grps_updated;
			break;
		}
		return $titleGrpsUpdated === 'Y' ? true : false;
	}
	
	public static function markTitleGrpsUpdated($seedKeyword) {
		if (empty($seedKeyword)) return null;
		$dbw = DbUtils::getDbw();
		
		$query = "UPDATE leonard.leo_topics SET title_grps_updated = 'Y' WHERE ".
				"seed = ". $dbw->addQuotes($seedKeyword) ."; ";
				
		return DbUtils::exDbW($query);
	}
	
	public static function addDbSeedKeyword($seedKeyword) {
		if (empty($seedKeyword)) return;
		$fetchedTs = wfTimestampNow(TS_MW);
		$dbw = DbUtils::getDbw();
		$query = "REPLACE INTO leonard.leo_topics ".
				"(seed, fetched_ts) VALUES (".
				$dbw->addQuotes($seedKeyword). ",".
				$dbw->addQuotes($fetchedTs).
				")";
		return DbUtils::exDbW($query);
	}

	public static function getKeyword($seedKeyword, $keyword) {
		if (empty($seedKeyword) || empty($keyword)) return null;
		$dbr = DbUtils::getDbr();
	
		$query = "SELECT * FROM leonard.leo_keywords k ".
				"WHERE ".
				" status = 'A' ".
				" AND k.keyword = ". $dbr->addQuotes($keyword).
				" AND k.seed = ". $dbr->addQuotes($seedKeyword);
		return DbUtils::exDbR($query);
	}

	public static function addDbKeyword($seedKeyword, 
			$keyword, 
			$avgMonthSearches,
			$numSearchResults,
			$ipRank = 0
			) {
		if (empty($seedKeyword) || empty($keyword)) return;
		$fetchedTs = wfTimestampNow(TS_MW);
		$dbw = DbUtils::getDbw();
		
		$query = "REPLACE INTO leonard.leo_keywords ".
				"(seed, keyword, avg_month_searches, ip_rank, num_search_results, status, fetched_ts) VALUES (".
				$dbw->addQuotes($seedKeyword). ",".
				$dbw->addQuotes($keyword). ",".
				$dbw->addQuotes($avgMonthSearches). ",".
				$dbw->addQuotes($ipRank). ",".
				$dbw->addQuotes($numSearchResults). ",".
				"'A', ".
				$dbw->addQuotes($fetchedTs).
				")";
		return DbUtils::exDbW($query);
	}
	
	public static function markKeywordsInactive($seedKeyword, $activeKeywords) {
		if (empty($activeKeywords) || empty($seedKeyword)) return null;
		$dbw = DbUtils::getDbw();
		
		$inSet = '';
		//prepare not in set
		foreach($activeKeywords as $kw) {
			$inSet = $inSet. $dbw->addQuotes($kw) .',';
		}
		$inSet = rtrim($inSet, ',');
		
		$query = "UPDATE leonard.leo_keywords SET status = 'I' WHERE ".
				"seed = ". $dbw->addQuotes($seedKeyword) ." ".
				"AND status = 'A' ".
				"AND keyword NOT IN (". $inSet .")";
		return DbUtils::exDbW($query);
	}
	
	public static function markTitlesInactive($seedKeyword) {
		if (empty($seedKeyword)) return null;
		$dbw = DbUtils::getDbw();
		
		$query = 
			"UPDATE leonard.leo_titles t SET status = 'I'  ". 
			"WHERE  ". 
			"    t.seed = ". $dbw->addQuotes($seedKeyword) ." ".
			"    AND t.status='A'  ". 
			"    AND EXISTS (SELECT k.keyword  ". 
			"                FROM leonard.leo_keywords k  ". 
			"                WHERE  ". 
			"                    k.seed = t.seed  ". 
			"                    and k.keyword = t.keyword  ". 
			"                    and t.status = 'I' ". 
			"                ); ";		
		return DbUtils::exDbW($query);
	}

	public static function addTitle($seedKeyword,
			$keyword,
			$posInResults,
			$origTitle,
			$shortTitle,
			$site,
			$url
		) {
		if (empty($seedKeyword) || empty($keyword) || empty($origTitle)) return;
		$fetchedTs = wfTimestampNow(TS_MW);
		$dbw = DbUtils::getDbw();
	
		$query = "REPLACE INTO leonard.leo_titles ".
				"(seed, keyword, position_in_results, original_title, short_title, site, url, fetched_ts) VALUES (".
				$dbw->addQuotes($seedKeyword). ",".
				$dbw->addQuotes($keyword). ",".
				$dbw->addQuotes($posInResults). ",".
				$dbw->addQuotes($origTitle). ",".
				$dbw->addQuotes($shortTitle). ",".
				$dbw->addQuotes($site). ",".
				$dbw->addQuotes($url). ",".
				$dbw->addQuotes($fetchedTs).
				")";
		return DbUtils::exDbW($query);
	}
	
	public static function getUniqueShortTitlesForASeed($seedKeyword) {
		if (empty($seedKeyword)) return;
		$dbr = DbUtils::getDbr();
		
		$query = "
		SELECT 
		    short_title 
		FROM leonard.leo_titles t 
		WHERE 
		    status = 'A' 
		    AND seed = ". $dbr->addQuotes($seedKeyword) ." \n". 
		"GROUP BY 
		    short_title;";

		$dbr = DbUtils::getDbr();
		return DbUtils::exDbR($query);
	}

	public static function updateTitleGrpInfo($seedKeyword, $shortTitle, $grpId, $aid, $whTitle = '') {
		if (empty($seedKeyword) || empty($shortTitle)) return null;
		$dbw = DbUtils::getDbw();
	
		$query =
		"UPDATE leonard.leo_titles t 
		 SET 
			dup_grp_id = ". $dbw->addQuotes($grpId) ."  \n";
		if (!empty($aid) && $aid > 0) {
			$query .= ", wh_title = ".$dbw->addQuotes(str_replace(' ', '-', $whTitle)) ." \n".
					", wh_aid = ".$dbw->addQuotes($aid) ." \n";
		}
			
		$query .= " WHERE  ".
		"    t.seed = ". $dbw->addQuotes($seedKeyword) ." \n".
		"    AND t.short_title = ". $dbw->addQuotes($shortTitle) ." 
		     AND t.status = 'A' ";

		return DbUtils::exDbW($query);
	}
	
	public static function getTitles($seedKeyword, $groupTitles) {
		if (empty($seedKeyword)) return;
		$dbr = DbUtils::getDbr();
		$query = 
		"SELECT
			group_concat(DISTINCT t.short_title SEPARATOR 0x0B) as short_titles,
			group_concat(DISTINCT IF (t.wh_aid <> 0, concat('http://www.wikihow.com/',t.wh_title), '') SEPARATOR 0x0B) as wh_article,
			sum(k.avg_month_searches) as sum_of_ams,
			count(t.short_title) as num_sites
		FROM
			leonard.leo_keywords k INNER join leonard.leo_titles t ON k.keyword = t.keyword
		WHERE
			k.status = 'A'
			AND t.status = 'A'
			AND k.seed = ". $dbr->addQuotes($seedKeyword) ." \n".
		"GROUP BY ";
		
//		Also had following columns in the select query however elizabeth needs only few as seen in the actual query	
// 		group_concat(DISTINCT t.site order by t.position_in_results SEPARATOR 0x0B) as site_list,
// 		group_concat(DISTINCT concat(t.keyword,':',k.avg_month_searches) ORDER BY k.avg_month_searches DESC) as kwd_n_avg_m_searches,
// 		group_concat(t.original_title order by t.position_in_results SEPARATOR 0x0B) as original_titles,
// 		group_concat(DISTINCT t.url SEPARATOR 0x0B) as urls,
// 		min(k.ip_rank) as min_kw_ip_rank,
// 		max(k.ip_rank) as max_kw_ip_rank
		
		if ($groupTitles === true) {
			$query .= " dup_grp_id ";
		} else {
			$query .= " short_title ";
		}
		$query .= " ORDER BY
			sum(k.avg_month_searches) DESC,
			t.keyword,
			count(t.short_title) DESC";
		
		$dbr = DbUtils::getDbr();
		return DbUtils::exDbR($query);
	}
}