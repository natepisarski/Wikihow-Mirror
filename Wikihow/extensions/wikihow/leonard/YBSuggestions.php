<?php
// require_once("../../../maintenance/commandLine.inc");
global $IP;

require_once("$IP/extensions/wikihow/dedup/OAuth.php");
require_once("LDao.php");
require_once("KeywordIdeasCSV.php");

class Yboss {
	const HOW_TO = "how to";
	const CSV_DELIMITER = "\t";

	const KEY_POS_IN_RESULTS = "position_in_results";
	const KEY_ORIG_TITLE = "original_title";
	const KEY_SHORT_TITLE = "short_title";
	const KEY_SITE = "site";
	const KEY_URL = "url";

	const DEDUP_QUERY_DELIMITER = '|';

	const DEDUP_CLUSTER_SCORE = 35;

	public static function printMsg($msg) {
		echo "$msg"."\n";
	}

	public static function getDomain($url) {
		$urlParts = parse_url($url);
		return $urlParts['host'];
	}

	private static function queryCleanup($query) {
		$query = trim($query);
		$query = preg_replace('/&amp;/', ' and ', $query);
		$query = preg_replace('/\s\s+/', ' ', $query);
		return $query;
	}

	private static function makeHowToQuery($query) {
		$query = self::queryCleanup($query);
		if (preg_match("/^how to/i", $query) == 0) {
			return "how to ". $query;
		}
	}

	private static function isHowToResult($query) {
		$query = self::queryCleanup($query);
		return (preg_match("/^how to/i", $query));
	}

	public static function fetchQueryFromYBoss($row) {
		$query = self::makeHowToQuery($row[KeywordIdeasCSV::KEY_KEYWORD]);
		if (empty($query)) return;
		$cc_key  = WH_YAHOO_BOSS_API_KEY;
		$cc_secret = WH_YAHOO_BOSS_API_SECRET;
		$url = "http://yboss.yahooapis.com/ysearch/web";
		$args = array();
		$args["q"] = $query;
		$args["format"] = "json";

		$consumer = new OAuthConsumer($cc_key, $cc_secret);
		$request = OAuthRequest::from_consumer_and_token($consumer, NULL,"GET", $url, $args);
		$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
		$url = sprintf("%s?%s", $url, OAuthUtil::build_http_query($args));
		$ch = curl_init();
		$headers = array($request->to_header());
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$rsp = curl_exec($ch);
		$results = json_decode($rsp);

		$totalResults = 0;
		if ($results->bossresponse->responsecode == 200) {
			$totalResults = $results->bossresponse->web->totalresults;
			$titles = array();
			foreach ($results->bossresponse->web->results as $idx=>$result) {
				$title = self::getShortTitle($result->title);
				if (self::isHowToResult($title) == 1) {
					$csvRow = array();
					$csvRow[self::KEY_POS_IN_RESULTS] = $idx;
					$csvRow[self::KEY_ORIG_TITLE] = $result->title;
					$csvRow[self::KEY_SHORT_TITLE] = $result->title;
					$csvRow[self::KEY_SHORT_TITLE] = $title;
					$csvRow[self::KEY_SITE] = self::getDomain($result->url);
					$csvRow[self::KEY_URL] = $result->url;
					$titles[] = $csvRow;
				}
			}
		} else {
			$err = "Error in fetching reults from boss for kw=[$query]. Resp code = ".$results->bossresponse->responsecode. "<br>\n";
		}
		return array($err, $totalResults, $titles);
	}

	private static function dedupAndJoinShortTitles($seedKeyword) {
		if (empty($seedKeyword)) return null;

		$res = LDao::getUniqueShortTitlesForASeed($seedKeyword);
		if (!res || empty($res)) return null;
		$shortTitles = array();
		foreach ($res as $dbRow) {
			$shortTitle = $dbRow->short_title;
			if (empty($shortTitle)) continue;
			$shortTitles[] = $shortTitle;
		}
		return implode(self::DEDUP_QUERY_DELIMITER, array_unique($shortTitles));
	}

	public static function groupUsingDedup($seedKeyword) {
		if (empty($seedKeyword)) return null;

		$queries = self::dedupAndJoinShortTitles($seedKeyword);
		if (empty($queries)) return null;

		$url = "https://titus.wikiknowhow.com/api.php";
		$args = array();
		$args["action"] = "dedup";
		$args["subcmd"] = "queries";
		$args["internal"] = "true";
		$args["clusterScore"] = self::DEDUP_CLUSTER_SCORE;
		$args["format"] = "json";
		$args["queries"] = $queries;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);

		$rsp = curl_exec($ch);
		$results = json_decode($rsp);

		$titlesGroups = array();
		foreach ($results as $groups) {
			foreach ($groups as $group) {
				$titles = array();
				if (!empty($group) && count($group) > 0) {
					foreach ($group as $title) {
						if ($title && !empty($title->query)) $titles[] = $title;
					}
				}
				if (!empty($titles) && count($titles) > 0) $titlesGroups[] = $titles;
			}
		}

		return($titlesGroups);
	}

	private static function updateDbTitleGroupsInfo($seedKeyword, $titlesGroups) {
		if (empty($titlesGroups) || count($titlesGroups) <= 0 || empty($seedKeyword)) return;

		foreach ($titlesGroups as $idx => $titlesGroup) {
			foreach ($titlesGroup as $title) {
				LDao::updateTitleGrpInfo($seedKeyword, $title->query, $idx, $title->aid, $title->title);
			}
		}
		LDao::markTitleGrpsUpdated($seedKeyword); //so that we will not do dedup based title grouping
												  //over and over for same csv upload
	}

	public static function getShortTitle($str) {
		if (!empty($str)) {
			$str = trim(strip_tags($str));
			$str = preg_replace('/(&amp;|&)/', ' and ', $str);
			$str = preg_replace('/(\s+|[\-\|\?:«])/', ' ', $str);
		}
		$arr = explode('  ', $str);
		if (count($arr) > 1 && strlen($arr[0]) < strlen($arr[1])) {
			//taking care of example "<b>How To</b> Use | <b>Pregnancy</b> <b>Calculator</b>";
			return trim($arr[0]).' '.trim($arr[1]);
		} else {
			return trim($arr[0]);
		}
	}

	protected static function updateDb($seed, $keywordRow, $titles, $numSearchResults = 0) {
		if (empty($seed) || empty($keywordRow) || empty($titles)) return;

		//store keyword
		$seedKeyword = KeywordIdeasCSV::getSeedKeyword($seed);
		$keyword = $keywordRow[KeywordIdeasCSV::KEY_KEYWORD];
		$avgMonthlySearches = $keywordRow[KeywordIdeasCSV::KEY_AVG_SEARCHES];
		$ipRank = $keywordRow[KeywordIdeasCSV::KEY_IP_RANK];
		$res = LDao::addDbKeyword($seedKeyword, $keyword, $avgMonthlySearches, $numSearchResults, $ipRank);

		//store titles
		foreach ($titles as $title) {
			$posInResults = $title[self::KEY_POS_IN_RESULTS];
			$origTitle = $title[self::KEY_ORIG_TITLE];
			$shortTitle = $title[self::KEY_SHORT_TITLE];
			$site = $title[self::KEY_SITE];
			$url = $title[self::KEY_URL];
			$res = LDao::addTitle($seedKeyword, $keyword, $posInResults, $origTitle, $shortTitle, $site, $url);
		}
	}

	public static function getTitlesAsCsv($seedKeyword, $groupTitles) {
		$res = LDao::getTitles($seedKeyword, $groupTitles);
		if ($res !== false) {
			$titleCsv = array();
			foreach ($res as $dbrow) {
				if (count($titleCsv) == 0) {
					foreach ($dbrow as $header => $vals) {
						$str .= $header. self::CSV_DELIMITER;
					}
					$titleCsv[] = $str;
				}
				$str = '';
				foreach ($dbrow as $vals) {
					$str .= $vals . self::CSV_DELIMITER;
				}
				$titleCsv[] = $str;
			}
		}
		return $titleCsv;
	}

	public static function fetchQueries($csvFile, $avgSearchThresh, $groupTitles) {
		list($err, $seed, $csvRows) = KeywordIdeasCSV::getKeywordIdeas($csvFile, $avgSearchThresh);
		if (!$err) {
			foreach ($csvRows as $row) {
				list($errt, $totalResults, $titles) = self::fetchQueryFromYBoss($row);
				if ($errt) $err1 .= $errt;
				if (!$errt) self::updateDb($seed, $row, $titles, $totalResults);

			}
			if ($err1) echo($err1);
		}
		// 		return array($err, $seed, $rows);
		if (!$err) {
			$seedKeyword = KeywordIdeasCSV::getSeedKeyword($seed);

			//get title groups using dedup mechanism
			$isTitleGrpsUpdated = LDao::isTitleGrpsUpdated($seedKeyword);
			if ($isTitleGrpsUpdated === false) {
				$titlesGroups = self::groupUsingDedup($seedKeyword);
				if (!empty($titlesGroups)) self::updateDbTitleGroupsInfo($seedKeyword, $titlesGroups);
			}

			//create csv
			$titleCsv = self::getTitlesAsCsv($seedKeyword, $groupTitles);
		}
		return array($err, $seed, $titleCsv);
	}

}

//Yboss::getTitlesAsCsv('heart health');
