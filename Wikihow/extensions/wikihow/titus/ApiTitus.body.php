<?php

global $IP;
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

class ApiTitus extends ApiBase {
	/**
	 * Get language and article info
	 */
	function execute() {
		$params = $this->extractRequestParams();
		$command = $params['subcmd'];
		$result = $this->getResult();
		$module = $this->getModuleName();

		switch ($command) {
			case 'article':
				if (!isset($params['page_id']) || !isset($params['language_code'])) {
					$error = "pageId or lang parameters not set";
				}
				$pageId = $params['page_id'];
				$lang = $params['language_code'];
				$dbr = wfGetDB(DB_REPLICA);
				$t = new TitusDB();
				$sql = "select * from titus_intl where ti_page_id=" . $dbr->addQuotes($pageId) . " AND ti_language_code=" . $dbr->addQuotes($lang);
				$res = $t->performTitusQuery($sql, 'read', __METHOD__);
				$found = false;
				foreach ($res as $row) {
					$result->addValue(null, $module, get_object_vars($row));
					$found = true;
				}
				if (!$found) {
					$error = "No data for article found";
				}
				break;
			case 'retranslatefish_auto_import':
				if (!isset($params['language_code'])) {
					$error = 'language code not set';
					break;
				}
				global $IP;
				require_once("$IP/extensions/wikihow/retranslatefish/RetranslatefishDB.class.php");

				$lang = $params['language_code'];
				$dbr = wfGetDB(DB_REPLICA);

				$rtfdb = WAPDB::getInstance(WAPDB::DB_RETRANSLATEFISH);
				$query = $rtfdb->getAutoImportQuery($lang);
				$res = $dbr->query($query, __METHOD__);

				$rows = array();
				foreach ($res as $row) {
					$rows['id_' . $row->en_page_id] = get_object_vars($row);
				}

				$result->addValue(null, $module, $rows);
				break;
			case 'retranslatefish_manual_update':
				if (!isset($params['language_code'])) {
					$error = 'language code not set';
					break;
				}
				global $IP;
				require_once("$IP/extensions/wikihow/retranslatefish/RetranslatefishDB.class.php");

				$lang = $params['language_code'];
				$dbr = wfGetDB(DB_REPLICA);

				$rtfdb = WAPDB::getInstance(WAPDB::DB_RETRANSLATEFISH);
				$query = $rtfdb->getManualUpdateQuery($lang);
				$res = $dbr->query($query, __METHOD__);

				$rows = array();
				foreach ($res as $row) {
					$rows['id_' . $row->en_page_id] = get_object_vars($row);
				}

				$result->addValue(null, $module, $rows);
				break;
			case 'retranslatefish_article_update':
				if (!isset($params['page_id']) || !isset($params['language_code'])) {
					$error = "pageId or lang parameters not set";
					break;
				}
				global $IP;
				require_once("$IP/extensions/wikihow/retranslatefish/RetranslatefishDB.class.php");

				$pageId = $params['page_id'];
				$lang = $params['language_code'];
				$dbr = wfGetDB(DB_REPLICA);

				$rtfdb = WAPDB::getInstance(WAPDB::DB_RETRANSLATEFISH);
				$query = $rtfdb->getArticleUpdateQuery($pageId, $lang);
				$res = $dbr->query($query, __METHOD__);
				$found = false;

				foreach ($res as $row) {
					$result->addValue(null, $module, get_object_vars($row));
					$found = true;
					break; // We only need one
				}

				if (!$found) {
					$error = 'No data for article found';
				}
				break;
			default:
				$error = "Command " . $command . " not found";
		}
		if ($error) {
			$result->addValue(null, $module, array('error' => $error));
		}

	}

	function getVersion() {
		return("1.0");
	}

  function getAllowedParams() {
		return(array('subcmd' => '', 'page_id' => '', 'language_code' => ''));
	}
}
