<?php
abstract class WAPMaintenance {

	// DB-specific configuration (table names, etc)
 	protected $wapConfig = null;

	// WAPDB database type
	protected $dbType = null;

	// The date range of daily edits to perform maintenance on
	protected $startDate = null;
	protected $endDate = null;

	// Singelton DBs
	private static $concierge = null;
	private static $babelfish = null;
	private static $editfish = null;
	private static $chocofish = null;
	private static $retranslatefish = null;

	const EXCLUDED_TYPE = "excluded_articles";
	const BAD_TEMPLATE_TYPE = "bad_templates";
	const MOVED_TYPE = "moved_articles";
	const REDIRECT_TYPE = "redirected_articles";
	const COMPLETED_TYPE = "completed_articles";

	public static function getInstance($dbType) {
		if ($dbType == WAPDB::DB_CONCIERGE) {
			if (is_null(self::$concierge)) {
				$config = new WAPConciergeConfig();
				$maintenanceClass = $config->getMaintenanceClassName();
				self::$concierge = new $maintenanceClass($config);
			}
			return self::$concierge;
		} elseif ($dbType == WAPDB::DB_BABELFISH) {
			if (is_null(self::$babelfish)) {
				$config = new WAPBabelfishConfig();
				$maintenanceClass = $config->getMaintenanceClassName();
				self::$babelfish = new $maintenanceClass($config);
			}
			return self::$babelfish;
		} elseif ($dbType == WAPDB::DB_EDITFISH) {
			if (is_null(self::$editfish)) {
				$config = new WAPEditfishConfig();
				$maintenanceClass = $config->getMaintenanceClassName();
				self::$editfish = new $maintenanceClass($config);
			}
			return self::$editfish;
		} elseif ($dbType == WAPDB::DB_CHOCOFISH) {
			if (is_null(self::$chocofish)) {
				$config = new WAPChocofishConfig();
				$maintenanceClass = $config->getMaintenanceClassName();
				self::$chocofish = new $maintenanceClass($config);
			}
			return self::$chocofish;
		} elseif ($dbType == WAPDB::DB_RETRANSLATEFISH) {
			if (is_null(self::$retranslatefish)) {
				$config = new WAPRetranslatefishConfig();
				$maintenanceClass = $config->getMaintenanceClassName();
				self::$retranslatefish = new $maintenanceClass($config);
			}
			return self::$retranslatefish;
		} else {
			throw new Exception('No valid system provided');
		}
	}

	protected function __construct(WAPConfig $config) {
		$this->wapConfig = $config;
		$this->dbType = $config->getDBType();
		$this->startDate = wfTimestamp(TS_MW, strtotime("-1 day", strtotime(date('Ymd', time()))));
		$this->endDate = wfTimestamp(TS_MW, strtotime(date('Ymd', time())));
	}

	public function nightly() {
		$startDate = $this->startDate;
		$endDate = $this->endDate;
		$this->updateMovedArticles($startDate, $endDate);
		$this->removeDeletedPages($startDate, $endDate);
		$this->removeRedirects($startDate, $endDate);
		$this->removeExcludedPages();
		$this->removeBadTemplates($startDate, $endDate);
		$this->completedReport($startDate, $endDate);
		$this->checkup();
	}

	private function removeExcludedPages() {
		$excludedKey = $this->wapConfig->getExcludedArticlesKeyName();
		$excludedAids = explode("\n", ConfigStorage::dbGetConfig($excludedKey));
		$ids = array();
		foreach ($excludedAids as $aid) {
			if (is_numeric($aid)) {
				$ids[] = $aid;
			}
		}
		$this->removeIds($ids, self::EXCLUDED_TYPE);
	}

	private function removeDeletedPages($startDate, $endDate) {
		$type = DailyEdits::DELETE_TYPE;
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT de_page_id FROM daily_edits WHERE (de_timestamp >= '$startDate' AND de_timestamp < '$endDate')
			AND de_edit_type = $type";
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->de_page_id;
		}
		$this->removeIds($ids, $type);
	}

	private function removeRedirects($startDate, $endDate) {
		$deleteType = DailyEdits::DELETE_TYPE;
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT de_page_id FROM daily_edits de LEFT JOIN page p ON de_page_id = page_id
			WHERE (de_timestamp >= '$startDate' AND de_timestamp < '$endDate') AND de_edit_type != $deleteType AND page_is_redirect = 1";
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->de_page_id;
		}
		$this->removeIds($ids, DailyEdits::EDIT_TYPE);
	}

	private function updateMovedArticles($startDate, $endDate) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			'recentchanges',
			array('rc_params'),
			array(
				"rc_timestamp >= '$startDate' AND rc_timestamp < '$endDate'",
				"rc_log_action" => "move",
				"rc_namespace" => NS_MAIN
			),
			__METHOD__);
		$ids = array();
		foreach ($res as $row) {
			$params = unserialize($row->rc_params);
			// Get the target title to pull the appropriate article id
			$t = Title::newFromText($params["4::target"]);
			if ($t && $t->exists()) {
				$ids[] = $t->getArticleID();
			}
		}

		if (!empty($ids)) {
			$ids = implode(",", $ids);
			$dbw = wfGetDB(DB_MASTER);
			$articleTable = $this->wapConfig->getArticleTableName();
			$sql = "UPDATE $articleTable, page SET ct_page_title = page_title
				WHERE ct_page_id = page_id and ct_page_id IN ($ids)";
			echo "ids with moved titles: $ids\n";
			$dbw->query($sql, __METHOD__);
		}
	}

	protected function completedReport($startDate, $endDate) {
		$tagIds = implode(",", array(300, 301, 305));
		$articleTable = $this->wapConfig->getArticleTableName();
		$articleTagTable = $this->wapConfig->getArticleTagTableName();
		$sql = "select distinct ct_page_title, ct_user_text from $articleTable, $articleTagTable where
			ca_tag_id IN ($tagIds) and ca_page_id = ct_page_id and ct_completed = 1 and (ct_completed_timestamp >= '$startDate' AND ct_completed_timestamp < '$endDate')";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$urls[] = WAPLinker::makeWikiHowUrl($row->ct_page_title) . " | {$row->ct_user_text}";
		}

		if (!empty($urls)) {
			$subject = $this->getSubject("Completed Bonus Articles", "en");
			$body = "The following articles with a bonus tag were completed yesterday:\r\n\r\n " .
				implode("\r\n ", $urls);
			$emails = $this->wapConfig->getMaintenanceCompletedEmailList();
			mail($emails, $subject, $body);
		}
	}

	private function removeBadTemplates($startDate, $endDate) {
		$dbr = wfGetDB(DB_REPLICA);
		$deleteType = DailyEdits::DELETE_TYPE;
		$badTemplates = $this->getBadTemplates();
		if (!empty($badTemplates)) {
			$badTemplates = implode("','", $badTemplates);
			$ns = NS_TEMPLATE;
			$sql = "SELECT de_page_id FROM daily_edits de LEFT JOIN templatelinks t ON de_page_id = tl_from
			WHERE (de_timestamp >= '$startDate' AND de_timestamp < '$endDate') AND de_edit_type != $deleteType AND tl_namespace = $ns AND
			LOWER(tl_title) IN ('$badTemplates')";
			$res = $dbr->query($sql, __METHOD__);
			$ids = array();
			foreach ($res as $row) {
				$ids[] = $row->de_page_id;
			}
			$this->removeIds($ids, self::BAD_TEMPLATE_TYPE);
		}
	}

	protected function getBadTemplates() {
		return array('merge', 'nfd', 'copyvio', 'copyviobot', 'copyedit', 'cleanup', 'format', 'accuracy', 'speedy', 'stub');
	}

	protected function getSubject($subjectText, $lang) {
		$system = $this->wapConfig->getSystemName();
		return "$system - $lang: $subjectText";
	}

	private function removeIds(&$ids, $type) {
		if (!empty($ids)) {
			$dbw = wfGetDB(DB_MASTER);
			$idList = "(" . implode(", ", $ids) . ")";
			$articleTable = $this->wapConfig->getArticleTableName();
			$sql = "select ct_lang_code, ct_user_text, ct_user_id, ct_page_id, ct_page_title FROM $articleTable where ct_page_id IN $idList ORDER BY ct_user_id";
			$rows =	$dbw->query($sql, __METHOD__);

			$urls = array();
			$assignedIds = array();
			foreach ($rows as $row) {
				$userText = intval($row->ct_user_id) > 0 ? $row->ct_user_text : "[NOT ASSIGNED]";
				$urls[$row->ct_lang_code][] = "{$row->ct_page_id} | " . WAPLinker::makeWikiHowUrl($row->ct_page_title) . " | $userText";
				if (intval($row->ct_user_id) > 0) {
					$assignedIds[$row->ct_lang_code][] = $row->ct_page_id;
				} else {
					$unassignedIds[$row->ct_lang_code][] = $row->ct_page_id;
				}
			}

			$system = $this->wapConfig->getSystemName();
			$langs = $this->wapConfig->getSupportedLanguages();
			foreach ($langs as $lang) {
				// Email articles that are assigned to wikiphoto admins so they can sort it out
				$body = "";
				$subject = "$system Report: Forward this to Jordan <eom>";
				if ($type == DailyEdits::EDIT_TYPE) {
					$subject = "Redirect Articles";
					$body = "The following articles became redirects in wikiHow yesterday but exist in $system\r\n\r\n ";
				} elseif ($type == DailyEdits::DELETE_TYPE) {
					$subject = "Deleted Articles";
					$body = "The following articles were deleted in wikiHow yesterday but but exist in $system\r\n\r\n ";
				} elseif ($type == self::EXCLUDED_TYPE) {
					$subject = "Excluded Articles";
					$body = "The following articles are on the excluded article list but exist in $system\r\n\r\n ";
				} elseif ($type == self::BAD_TEMPLATE_TYPE) {
					$subject = "Articles with Bad Templates";
					$body = "The following articles had a bad template added yesterday but exist in $system\r\n\r\n ";
				}

				$urlsByLang = $urls[$lang];
				if (!empty($urlsByLang)) {
					$subject = $this->getSubject($subject, $lang);
					$body .= implode("\r\n ", $urlsByLang);
					$emails = $this->wapConfig->getMaintenanceStandardEmailList();
					mail($emails, $subject, $body);
				}

				$this->handleUnassignedIdRemoval($unassignedIds[$lang], $lang, $subject);
			}
		}
	}

	protected function handleUnassignedIdRemoval(&$idsToRemove, $lang, $subject) {
		$wapDB = WAPDB::getInstance($this->dbType);
		// Delete the unassigned ids from Concierge for each lang
		if (!empty($idsToRemove)) {
			echo "$subject - $lang for removal - " . implode(",", $idsToRemove) . "\n";
			// Delete articles that haven't been assigned/completed
			$wapDB->removeArticles($idsToRemove, $lang);
		}
	}

	/*
	* Do a checkup on the system to make sure there aren't any redirect, deleted or move stragglers. If there are
	* send an email
	*
	* IMPORTANT:  Checkup is very db intensive requiring joins against highly queried mediawiki tables.
	* This should only be run when WH_USE_BACKUP_DB is set to true. This is done in the corresponding
	* maintenance scripts (babelfish,php, concierge.php, editfish.php) for nighly maintenance.
	*/
	public function checkup() {
		$this->checkupDeletedArticles();
		$this->checkupRedirects();
		$this->checkupExcluded();
		$this->checkupBadTemplates();
		$this->checkupCompletedArticles();
	}


	private function checkupDeletedArticles() {
		$articleTable = $this->wapConfig->getArticleTableName();
		$type = DailyEdits::DELETE_TYPE;
		$sql = "SELECT ct_lang_code, ct_user_text, ct_user_id, ct_page_id, ct_page_title FROM $articleTable LEFT JOIN page ON ct_page_id = page_id WHERE page_title IS NULL
				AND ct_page_id NOT IN (SELECT de_page_id FROM daily_edits WHERE de_timestamp >= '{$this->endDate}' AND de_edit_type = $type)";
		$this->genericCheckup($sql, $type);
	}

	private function checkupRedirects() {
		$articleTable = $this->wapConfig->getArticleTableName();
		$type = DailyEdits::EDIT_TYPE;
		$deleteType = DailyEdits::DELETE_TYPE;
		$sql = "SELECT ct_lang_code, ct_user_text, ct_user_id, ct_page_id, ct_page_title FROM $articleTable, page WHERE ct_page_id = page_id and page_is_redirect = 1
				AND ct_page_id NOT IN (SELECT de_page_id FROM daily_edits WHERE de_timestamp >= '{$this->endDate}' AND de_edit_type != $deleteType)";
		$this->genericCheckup($sql, self::REDIRECT_TYPE);
	}

	private function checkupExcluded() {
		$excludedKey = $this->wapConfig->getExcludedArticlesKeyName();
		$excludedAids = explode("\n", ConfigStorage::dbGetConfig($excludedKey));
		$ids = array();
		foreach ($excludedAids as $aid) {
			if (is_numeric($aid)) {
				$ids[] = $aid;
			}
		}
		if (!empty($ids)) {
			$articleTable = $this->wapConfig->getArticleTableName();
			$sql = "SELECT ct_lang_code, ct_user_text, ct_user_id, ct_page_id, ct_page_title FROM $articleTable WHERE ct_page_id IN (" . implode(',', $ids) . ")";
			$this->genericCheckup($sql, self::EXCLUDED_TYPE);
		}
	}

	private function checkupBadTemplates() {
		$badTemplates = $this->getBadTemplates();
		$badTemplates = implode("','", $badTemplates);
		$deletedType = DailyEdits::DELETE_TYPE;
		$ns = NS_TEMPLATE;
		$articleTable = $this->wapConfig->getArticleTableName();
		$sql = "SELECT distinct ct_lang_code, ct_user_text, ct_user_id, ct_page_id, ct_page_title FROM $articleTable de LEFT JOIN templatelinks t ON ct_page_id = tl_from
			WHERE tl_namespace = $ns AND LOWER(tl_title) IN ('$badTemplates')
			AND ct_page_id NOT IN (SELECT de_page_id FROM daily_edits WHERE de_timestamp >= '{$this->endDate}' AND de_edit_type != $deletedType)";
		$this->genericCheckup($sql, self::BAD_TEMPLATE_TYPE);
	}


	protected function checkupCompletedArticles() {
		// Not relevant for most of the fishes.  Mainly used by babelfish. See BabelfishMaintenance for
		// implementation;
	}


	protected function genericCheckup($sql, $type) {
		$dbr = wfGetDB(DB_REPLICA);
		$rows = $dbr->query($sql, __METHOD__);

		$urls = array();
		foreach ($rows as $row) {
			$userText = intval($row->ct_user_id) > 0 ? $row->ct_user_text : "[NOT ASSIGNED]";
			$urls[$row->ct_lang_code][] = "{$row->ct_page_id} | " . WAPLinker::makeWikiHowUrl($row->ct_page_title) . " | $userText | {$row->extra}";
		}

		$system = $this->wapConfig->getSystemName();
		$langs = $this->wapConfig->getSupportedLanguages();
		foreach ($langs as $lang) {
			// Email articles that are assigned to wikiphoto admins so they can sort it out
			$body = "";
			$subject = "$system CHECKUP Report: Forward this to Jordan <eom>";
			if ($type == self::REDIRECT_TYPE) {
				$subject = "CHECKUP - Redirect Articles";
				$body = "The following articles became redirects in wikiHow but exist in $system\r\n\r\n ";
			} elseif ($type == DailyEdits::DELETE_TYPE) {
				$subject = "CHECKUP - Deleted Articles";
				$body = "The following articles were deleted in wikiHow but exist in $system\r\n\r\n ";
			} elseif ($type == self::EXCLUDED_TYPE) {
				$subject = "CHECKUP - Excluded Articles";
				$body = "The following articles are on the excluded article list but exist in $system\r\n\r\n ";
			} elseif ($type == self::BAD_TEMPLATE_TYPE) {
				$subject = "CHECKUP - Articles with Bad Templates";
				$body = "The following articles had a bad template but exist in $system\r\n\r\n ";
			} elseif ($type == self::MOVED_TYPE) {
				$subject = "CHECKUP - Moved Articles";
				$body = "The following articles should be moved in $system\r\n\r\n ";
			} elseif ($type == self::COMPLETED_TYPE) {
				$subject = "CHECKUP - Completed Articles";
				$body = "The following articles should be completed in $system\r\n\r\n ";
			}

			$urlsByLang = $urls[$lang];
			if (!empty($urlsByLang)) {
				$subject = $this->getSubject($subject, $lang);
				$body .= implode("\r\n ", $urlsByLang);
				$emails = 'jordan@wikihow.com';
				mail($emails, $subject, $body);
			}
		}
	}

}
