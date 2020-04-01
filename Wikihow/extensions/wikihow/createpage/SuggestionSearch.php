<?php

class SuggestionSearch extends UnlistedSpecialPage {

	public function __construct() {
	   parent::__construct( 'SuggestionSearch' );
	}

	public static function matchKeyTitles($text, int $limit = 10) {
		global $wgMemc;

		$gotit = array();
		$text = trim($text);
		$limit = intval($limit);

		$cacheKey = wfMemcKey('matchsuggtitles', $limit, $text);
		$result = $wgMemc->get($cacheKey);
		if (is_array($result)) {
			return $result;
		}

		$db = wfGetDB( DB_MASTER );
		$textForSQL = $db->addQuotes($text . '%');

		$sql = "SELECT suggested_titles.st_title, st_id
				FROM suggested_titles
				WHERE convert(st_title using latin1) LIKE $textForSQL
				 AND st_used = 0
				LIMIT $limit;";
		$result = array();
		$res = $db->query( $sql, __METHOD__ . '-1' );
		foreach ($res as $row) {
			$con = array();
			$con[0] = $row->st_title;
			$con[1] = $row->st_id;
			$result[] = $con;
			$gotit[$row->st_title] = 1;
		}

		if (count($result) >= $limit) {
			$wgMemc->set($cacheKey, $result, 3600);
			return $result;
		}

		$key = TitleSearch::generateSearchKey($text);
		$keyForSQL = $db->addQuotes('%' . str_replace(' ', '%', $key) . '%');
		$base = "SELECT suggested_titles.st_title, suggested_titles.st_id FROM suggested_titles WHERE ";
		$sql = $base . " st_key LIKE $keyForSQL AND st_used = 0 ";
		$sql .= " LIMIT $limit;";
		$res = $db->query( $sql, __METHOD__ . '-2' );
		foreach ($res as $row) {
			if (count($result) >= $limit) {
				break;
			}
			if (!isset($gotit[$row->st_title])) {
				$con = array();
				$con[0] = $row->st_title;
				$con[1] = $row->st_id;
				$result[] = $con;
				$gotit[$row->st_title] = 1;
			}
		}

		if (count($result) >= $limit) {
			$wgMemc->set($cacheKey, $result, 3600);
			return $result;
		}

		$ksplit = explode(" ", $key);
		if (count($ksplit) > 1) {
			$sql = $base . " ( ";
			foreach ($ksplit as $idx => $chunk) {
				$chunkForSQL = $db->addQuotes('%' . $chunk . '%');
				$sql .= ($idx > 0 ? " OR" : "") . " st_key LIKE $chunkForSQL"  ;
			}
			$sql .= " ) AND st_used = 0 ";
			$sql .= " LIMIT $limit;";
			$res = $db->query( $sql, __METHOD__ . '-3' );
			foreach ($res as $row) {
				if (count($result) >= $limit) {
					break;
				}
				if (!isset($gotit[$row->st_title]))  {
					$con = array();
					$con[0] = $row->st_title;
					$con[1] = $row->st_id;
					$result[] = $con;
				}
			}
		}

		$wgMemc->set($cacheKey, $result, 3600);
	    return $result;
	}

	public function execute($par) {
		$this->getOutput()->setArticleBodyOnly(true);

		$t1 = time();
		$search = $this->getRequest()->getVal("qu");

		if ($search == "") exit;

		$search = strtolower($search);
		$howto = strtolower(wfMessage('howto', ''));
		if (strpos($search, $howto) === 0) {
			$search = substr($search, 6);
			$search = trim($search);
		}
		$t = Title::newFromText($search, 0);
		$dbkey = $t->getDBKey();

		$array = "";
		$titles = self::matchKeyTitles($search);
		foreach ($titles as $con) {
			$t = Title::newFromDBkey($con[0]);
			$title = $t ? $t->getFullText() : '';
			$array .= '"' . str_replace("\"", "\\\"", $title) . '", ' ;
		}
		if (strlen($array) > 2) $array = substr($array, 0, strlen($array) - 2); // trim the last comma
		$array1 = $array;

		$array = "";
		foreach ($titles as $con) {
			$array .=  "\" \", ";
		}
		if (strlen($array) > 2) $array = substr($array, 0, strlen($array) - 2); // trim the last comma
		$array2 = $array;

		print 'WH.AC.sendRPCDone(frameElement, "' . $search . '", new Array(' . $array1 . '), new Array(' . $array2 . '), new Array(""));';
	}

}
