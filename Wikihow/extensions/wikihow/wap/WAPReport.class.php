<?php
class WAPReport {
	const MIME_TYPE = 'application/vnd.ms-excel';
	const FILE_EXT = '.xls';
	const MSG_RPT_TOO_LARGE = 'Report too large to fetch over the web. Contact Jordan for a copy of report';
	const MAX_RPT_SIZE = 14000;
	const NO_MATCHING_ARTICLES = "No matching articles";

	protected $dbType = null;
	protected $config = null;

	public function __construct($dbType) {
		$this->dbType = $dbType;
		$this->config = WAPDB::getInstance($dbType)->getWAPConfig();
	}

	private function getDBR() {
		global $wgDBname;
		$db = DatabaseBase::factory('mysql');
		$db->open(WH_DATABASE_MASTER, WH_DATABASE_USER, WH_DATABASE_PASSWORD, $wgDBname);
		return $db;
	}

	public function getExcludedArticles() {
		global $IP;
		$excludedKey = $this->config->getExcludedArticlesKeyName();
		$excludeList = ConfigStorage::dbGetConfig($excludedKey);

		$report = "";
		if ($excludeList) {
			$excludeList = explode("\n", ConfigStorage::dbGetConfig($excludedKey));
			require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
			$dbr = self::getDBR();
			$aids = "(" . implode(",", $excludeList) . ")";
			$articleTable = $this->config->getArticleTableName();
			$rows = DatabaseHelper::batchSelect($articleTable, array('*'), array("ct_page_id IN $aids"),
				__METHOD__, array(), DatabaseHelper::DEFAULT_BATCH_SIZE, $dbr);
			$data = WAPUtil::generateTSVOutput($rows);
		}
		$report = self::getReportArray($data);
		return $report;
	}

	public function getUntaggedUnassignedArticles() {
		global $IP;
		$dbr = self::getDBR();
		require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
		$articleTable = $this->config->getArticleTableName();
		$rows = DatabaseHelper::batchSelect($articleTable, array('*'), array('ct_tag_list' => '', 'ct_user_id' => 0),
			__METHOD__, array(), DatabaseHelper::DEFAULT_BATCH_SIZE, $dbr);
		return self::getReportArray(WAPUtil::generateTSVOutput($rows));
	}

	public function getUserArticles($uid) {
		global $IP;

		$dbr = self::getDBR();
			$articleTable = $this->config->getArticleTableName();
			$count = $dbr->selectField($articleTable, 'count(*)', array('ct_user_id' => $uid), __METHOD__);
			if ($count > self::MAX_RPT_SIZE) {
				$data = self::MSG_RPT_TOO_LARGE;
			} else {
				require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
				$rows = DatabaseHelper::batchSelect($articleTable, array('*'), array('ct_user_id' => $uid),
					__METHOD__, array(), DatabaseHelper::DEFAULT_BATCH_SIZE, $dbr);
				$data = WAPUtil::generateTSVOutput($rows);
			}
			$rpt = self::getReportArray($data);
		return $rpt;
	}

	public function getCustomReport(&$urls, $langCode) {
		$dbr = self::getDBR();
		$where = array();
		$aids = array();

		foreach ($urls as $state => $stateUrls) {
			if ($state != 'invalid') {
				foreach ($stateUrls as $url) {
					if ($url['a'] && $url['a']->exists()) {
						$aids[] = $url['aid'];
					}
				}
			}
		}

		if (empty($aids)) {
			echo "No valid titles received!\n";
			die();
		}

		$where[] = "(ct_page_id IN (" . implode(",", $aids) . ") AND ct_lang_code = '$langCode')";

		$articleTable = $this->config->getArticleTableName();
		$where = implode(" OR ", $where);
		$rows = $dbr->select($articleTable, array('*'), array($where), __METHOD__);

		$data = array();
		foreach ($rows as $row) {
			$data[] = $row;
		}

		if (is_array($data)) {
			$data = WAPUtil::generateTSVOutput($data);
		} else {
			$data = "No articles found";
		}

		return self::getReportArray($data);
	}

	public function tagArticles($tagName) {
		global $IP;

		$dbr = self::getDBR();
		$dbInstance = WAPDB::getInstance($this->dbType);

		$count = $dbInstance->getTagCount($tagName);
		$data = "";
		if ($count >= self::MAX_RPT_SIZE) {
			$data .= self::MSG_RPT_TOO_LARGE;
		} else {
			$articles = $dbInstance->getArticlesByTagName($tagName, 0, self::MAX_RPT_SIZE, WAPArticleTagDB::ARTICLE_ALL, '');
			if (!empty($articles)) {
				$aids = array();
				foreach ($articles as $article) {
					$aids[] = $article->getArticleId();
				}
				$aids = "(" . implode(",", $aids) . ")";

				require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
				$articleTable = $this->config->getArticleTableName();
				$rows = DatabaseHelper::batchSelect($articleTable, array('*'), array("ct_page_id IN $aids"),
				__METHOD__, array(), DatabaseHelper::DEFAULT_BATCH_SIZE, $dbr);

				$data = WAPUtil::generateTSVOutput($rows);
			}
		}

		$rpt = self::getReportArray($data);
		return $rpt;
	}

	private function getReportArray(&$data) {
		if (empty($data)) {
			$data = self::NO_MATCHING_ARTICLES;
		}
		return array('ts' => wfTimestampNow(), 'data' => $data);
	}

	public function getAssignedArticles($langCode) {
		global $IP;
		$dbr = self::getDBR();
		require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
		$articleTable = $this->config->getArticleTableName();
		$rows = DatabaseHelper::batchSelect($articleTable, array('*'), array("ct_user_id > 0", "ct_completed" => 0, "ct_lang_code" => $langCode),
			__METHOD__, array(), DatabaseHelper::DEFAULT_BATCH_SIZE, $dbr);
		$this->formatData($rows);
		return self::getReportArray(WAPUtil::generateTSVOutput($rows));
	}

	public function getCompletedArticles($langCode, $fromDate, $toDate = null) {
		global $IP;
		$dbr = self::getDBR();
		require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
		$articleTable = $this->config->getArticleTableName();
		$defaultUser = $this->config->getDefaultUserName();

		$fromDate = $dbr->strencode($fromDate);
		$toDate = $dbr->strencode($toDate);

		$conditions =  array("ct_completed" => 1, "ct_completed_timestamp >= '$fromDate'", "ct_completed_timestamp <= '$toDate'", "ct_user_text != '$defaultUser'");

		if ($langCode != 'all') {
			$conditions["ct_lang_code"] = $langCode;
		}
		$rows = DatabaseHelper::batchSelect($articleTable, array('*'),
			$conditions, __METHOD__, array(), DatabaseHelper::DEFAULT_BATCH_SIZE, $dbr);
		$this->formatData($rows);
		return self::getReportArray(WAPUtil::generateTSVOutput($rows));
	}

	protected function formatData(&$rows) {}
}
