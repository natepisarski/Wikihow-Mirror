<?php

class TitusQueryTool extends UnlistedSpecialPage {
	var $titus = null;
	var $excluded = null;
	const MIME_TYPE = 'application/vnd.ms-excel';
	const FILE_EXT = '.xls';

	var $languageInfo  = array();

	function __construct() {
		parent::__construct('TitusQueryTool');

		// Give php a higher memory limit for Titus
		ini_set('memory_limit', '2048M');

		$this->language="";
		$this->languageInfo = Misc::getActiveLanguageNames();
		$GLOBALS['wgHooks']['ShowSideBar'][] = array('TitusQueryTool::removeSideBarCallback');
	}

	static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	function initialize() {
		global $wgRequest, $wgOut, $IP, $wgLoadBalancer;

		set_time_limit(600);

		if ($this->isPageRestricted()) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return false;
		}

		require_once("$IP/extensions/wikihow/titus/Titus.class.php");
		$this->titus = new TitusDB(false);

		$wgOut->addModules('ext.wikihow.titusquerytool');
		return true;
	}

	function isPageRestricted() {
		global $wgUser, $wgIsToolsServer, $wgIsTitusServer, $wgIsDevServer;

		$userGroups = $wgUser->getGroups();

		return !($wgIsToolsServer || $wgIsTitusServer || $wgIsDevServer)
			|| $wgUser->isBlocked()
			|| !in_array('staff', $userGroups);
	}

	// method is necessary to stop redirects on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	function execute($par) {
		global $wgOut, $wgRequest;

		if (!$this->initialize()) {
			return;
		}

		if ($wgRequest->wasPosted()) {
			$action = $wgRequest->getVal('action');
			if ($action == 'query') {
				$this->loadExcluded();
				$this->handleQuery();
			}
			else {
				$this->handleVault($action);
			}
			$wgOut->disable();
		} else {
Misc::maybeApril1Redirect();
			$wgOut->setPageTitle('Dear Titus...');
			$wgOut->addHtml($this->getToolHtml());
		}
	}


	function getHeaderRow(&$res, $delimiter = "\t") {
		$dbr = wfGetDB(DB_SLAVE);
		$n = $dbr->numFields($res);
		$fields = array('titus_query_url', 'titus_status', 'redirect_target');
		for( $i = 0; $i < $n; $i++ ) {
			$fields[] = $dbr->fieldName($res,$i);
		}
		return implode($delimiter, $fields) . "\n";
	}

	function getTitusFields() {
		$data = array();
		$dbr = wfGetDB(DB_SLAVE);
		$titus = $this->titus;
		$res = $titus->performTitusQuery("SELECT COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $titus->getDBName() . "' AND TABLE_NAME = '" . $titus::TITUS_INTL_TABLE_NAME . "'", 'read', __METHOD__);
		foreach ($res as $r) {
			$row = get_object_vars($r);
			switch ($row['DATA_TYPE']) {
				case 'varchar':
				case 'mediumtext':
					$ftype = 'string';
					break;
				case 'int':
				case 'bigint':
					$ftype = 'integer';
					break;
				case 'tinyint':
				case 'varbinary':
					$ftype = 'boolean';
					break;
				case 'decimal':
					$ftype = 'double';
					break;
				default:
					$ftype = 'string';
					break;
			}

			if (preg_match('/date/', $row['COLUMN_NAME'])) {
				$ftype = 'date';
			} elseif (preg_match('/time/', $row['COLUMN_NAME'])) {
				$ftype = 'datetime';
			}

			if (preg_match('/ti_alt_methods|ti_num_/', $row['COLUMN_NAME']) && $ftype == 'boolean') {
				$ftype = 'integer';
			}
			if (preg_match('/ti_helpful_percentage/', $row['COLUMN_NAME']) && $ftype == 'boolean') {
				$ftype = 'double';
			}

			$data[] = [
				'field' => 'titus.' . $row['COLUMN_NAME'],
				'name'	=> $row['COLUMN_NAME'],
				'id'		=> $row['ORDINAL_POSITION'],
				'ftype'	=> $ftype
			];
		}
		return json_encode($data);
	}

	function handleQuery() {
		global $wgRequest;
		global $wgWikiHowLanguages;

		$ids = array();
		$urlQuery = $wgRequest->getVal('page-filter') == 'urls';
		$pageFilter = $wgRequest->getVal('page-filter');
		if($pageFilter == 'urls') {
			$ids = self::getIdsFromUrls(urldecode(trim($wgRequest->getVal('urls'))));
		}
		else {
			if(in_array($pageFilter,$wgWikiHowLanguages) || $pageFilter=="en" || $pageFilter == "noen") {
				$this->language = $pageFilter;
			}
		}
		$sql = $this->buildSQL($ids);
		$titus = $this->titus;
		$res = $titus->performTitusQuery($sql, 'read', __METHOD__);
		$headerRow = $this->getHeaderRow($res);
		$outputValid = $wgRequest->getVal('ti_exclude');
		$timestamp = date('Y_m_d');
		$filename = sprintf("titus_query_%s.xls", $timestamp);
		$this->startOutput($filename, $headerRow);

		if($urlQuery) {
			// build $rows variable from titus results
			$rows = array();
			foreach($res as $row) {
				$r = get_object_vars($row);
				if(isset($r['ti_page_id']) && isset($r['ti_language_code'])) {
					$rows[$r['ti_language_code'] . $r['ti_page_id']]=$r;
				}
			}

			// relate input back to titus results
			foreach($ids as $id) {
				$row = array();
				$rowKey = $id['language'] . $id['page_id'];
				if(isset($rows[$rowKey])) {
					$row = $rows[$rowKey];
				}
				$status = 'invalid';
				if(!empty($id['language'])) {
					if ($id['language']=="en" && $this->isExcludedPageId($id['page_id'])) {
						$status = 'excluded';
					} else {
						if (empty($row)) {
							if (isset($id['redirect_target']) && $id['redirect_target']) {
								$status = 'redirect';
							} else {
								$status = 'not found';
							}
						} else {
							$status = 'found';
						}
					}
				}
				if (!$outputValid || ($status == 'found' && $outputValid)) {
					$this->addOutput($this->outputRow($row, $id, $status));
				}
			}
			exit;
		} else {
			$url = 'N/A';
			foreach ($res as $row) {
				$row = get_object_vars($row);
				$status = 'found';
				if ($row->ti_language_code =="en" && $this->isExcludedPageId($row->ti_page_id)) {
					$status = 'excluded';
				}
				if (!$outputValid || ($status == 'found' && $outputValid)) {
					$this->addOutput($this->outputRow($row, ['url' => $url], $status));
					ob_flush();
				}
			}
			exit;
		}
	}

	function buildSQL(&$ids) {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$sql = urldecode( $wgRequest->getVal( 'sql' ) );
		if(stripos($sql, "FROM titus") > 0) {
			// Always get the language_code
			if(stripos($sql, "ti_language_code") == FALSE) {
				$sql = str_replace("FROM titus", ', ti_language_code as "ti_language_code" FROM titus',$sql);
			}
			$sql = str_replace("FROM titus","FROM " . TitusDB::TITUS_TABLE_NAME , $sql);
			//Hack to include ti_page_id and ti_language_code
			if (!preg_match('@SELECT\ +\*@i', $sql) && !preg_match('@ti_page_id as "ti_page_id"@', $sql)) {
					$sql = preg_replace('@SELECT @', 'SELECT ti_page_id as "ti_page_id",', $sql);
			}

		}
		else {
			$sql = "SELECT * FROM " . TitusDB::TITUS_TABLE_NAME;
		}
		$pageCondition = "";
		if($this->language != "") {
			if($this->language == "noen") {
				$pageCondition = "ti_language_code <> 'en'";
			}
			else {
				$pageCondition = " ti_language_code=" . $dbr->addQuotes( $this->language );
			}
		}
		$langConditions = array();
		if(is_array($ids) && sizeof($ids) > 0) {
			$sz=sizeof($ids);
			foreach($ids as $id) {
				if(!empty($id['page_id'])) {
					if(!isset($langConditions[$id['language']]) ) {
						$langConditions[$id['language']] = array();
					}
					$langConditions[$id['language']][] = $id['page_id'];
				}
				#$pageCondition .= "(ti_page_id ='" . $id['page_id'] . "' AND ti_language_code='" . $id['language'] ."')";
			}
			foreach($langConditions as $lang => $langIds) {
				if($pageCondition != "") {
					$pageCondition .= " OR ";
				}

				$pageCondition .= "(ti_language_code='" . $lang . "' AND ti_page_id in (" . implode(",",$langIds) . "))";
			}

		}
		if (stripos($sql, "WHERE ") ) {
			if($pageCondition) {
				$pageCondition = " AND (" . $pageCondition . ")";
			}
			$sql = preg_replace("@WHERE (.+)$@", "WHERE (\\1) $pageCondition", $sql);
		} elseif($pageCondition!="") {
			$sql .= " WHERE $pageCondition $orderBy";
		}
		else {
			$sql .= " $orderBy";
		}


		return $sql;
	}

	function outputRow(&$data, $input, $status) {
		// output a URLs instead of the title text
		if($data['ti_page_title']) {
			$data['ti_page_title'] = Misc::getLangBaseUrl($data['ti_language_code']) . '/' . rawurlencode($data['ti_page_title']);
		}
		if (isset($input['redirect_target']) && $input['redirect_target']) {
			$redirect_target = Misc::getLangBaseUrl($input['language']) . '/' . rawurlencode($input['redirect_target']);
		} else {
			$redirect_target = '';
		}
		return "{$input['url']}\t$status\t$redirect_target\t" . implode("\t", array_values($data)) . "\n";
	}

	function loadExcluded() {
		if (is_null($this->excluded)) {
			$this->excluded = explode("\n", ConfigStorage::dbGetConfig('wikiphoto-article-exclude-list'));
		}
		return $ids;
	}

	function isExcludedPageId($pageId) {
		// We don't want to exclude NULL or 0 pageIds, because they could be redirects
		return $pageId && in_array($pageId, $this->excluded);
	}

	// This method is used outside of titus class, as well
	public static function getIdsFromUrls($lines) {
		$ids = array();
		$lines = explode("\n", trim($lines));
		$dbr = wfGetDB(DB_SLAVE);
		foreach ($lines as $line) {
			$ids[] = self::parseInputLine($dbr, $line);
		}
		return $ids;
	}

	// Parses wikihow URLs in all languages and for both mobile and desktop.
	// Parses wikihow page IDs as well. Examples:
	//	 http://www.wikihow.com/Live			 # Canonical url
	//	 https://www.wikihow.com/Kiss			 # Secure url
	//	 www.wikihow.com/Surf							 # url without http://
	//	 Serve-a-Volleyball								 # (default) EN url without domain
	//	 http://m.wikihow.com/Rap					 # Mobile url
	//	 http://de.m.wikihow.com/Surfen		 # INTL url
	//	 https://www.wikihow.vn/....			 # CCTLD url
	//	 45679														 # English page ID
	//	 es:1234													 # Spanish page ID
	public static function parseInputLine($dbr, $line) {
		// If URL is actually a pageid, or in langcode:pageid format
		$out = ['url' => $line];
		if (preg_match('@^\s*(([a-z]{2})\s*:\s*)?([0-9]+)\s*$@', $line, $matches)) {
			$language = $matches[2];
			if (!$language) $language = 'en';
			$pageId = (int)$matches[3];

			$databaseName = Misc::getLangDB($language);
			if (!$dbr || !$databaseName) {
				return $out;
			}

			$out['language'] = $language;

			// Lookup the page title for this pageid
			$res = $dbr->query(
				"SELECT page_id, page_title, page_is_redirect FROM $databaseName.page
					WHERE page_id = " . $dbr->addQuotes($pageId) . "
						AND page_namespace = '0'", __METHOD__);
			$row = $dbr->fetchObject($res);
			if (!$row) {
				return $out;
			}
			$url = Misc::getLangBaseURL($language) . '/' . $row->page_title;
			$out['url'] = $url;
			if ($row->page_is_redirect) {
				$out['redirect_target'] = self::getRedirectFromID($row->page_id);
				return $out;
			}
			$out['page_id'] = $pageId;
			return $out;
		} else {
			$url = trim($line);
			// If URL doesn't start with http:// then we try to complete it
			if (!preg_match('@^https?://@', $url)) {
				// Check if format is www.wikihow.com/Page-Title or //www.wikihow.com/Page-Title
				if (preg_match('@^(//)?[^/]+\..*/@', $url, $matches)) {
					$prefix = $matches[1] ? 'http:' : 'http://';
					$url = $prefix . $url;
				// Check if format is Page-Title or /Page-Title
				} elseif (preg_match('@^(/?)[^?]+@', $url, $matches)) {
					$slash = $matches[1] ? '' : '/';
					$url = Misc::getLangBaseURL('en') . $slash . $url;
				}
			}

			// Try to pull out the language and page title from the URL
			$decoded = str_replace(" ", "+", urldecode($url) );
			list($language, $pageTitle) = Misc::getLangFromFullURL($decoded, false); // try desktop urls
			if (!$language) {
				list($language, $pageTitle) = Misc::getLangFromFullURL($decoded, true); // try mobile urls
			}
			if (!$language || !$pageTitle) {
				return $out;
			}
			$databaseName = Misc::getLangDB($language);
			if (!$dbr || !$databaseName) {
				return $out;
			}

			// Lookup the pageid for this page title
			$res = $dbr->query(
				"SELECT page_id, page_is_redirect FROM $databaseName.page
					WHERE page_title = " . $dbr->addQuotes($pageTitle) . "
						AND page_namespace = '0'", __METHOD__);
			$row = $dbr->fetchObject($res);
			$out['url'] = $url;
			$out['language'] = $language;

			if ( !$row ) {
				$pageId = 0;
				$caseRedirect = Misc::getCaseRedirect( Title::newFromText( $pageTitle ) );
				if ( $caseRedirect ) {
					$out['redirect_target'] = $caseRedirect->getDBkey();
				}
			} else if ( $row->page_is_redirect ) {
				$pageId = 0;
				$out['redirect_target'] = self::getRedirectFromID( $row->page_id );
			} else {
				$pageId = $row->page_id;
			}
			$out['page_id'] = $pageId;
			return $out;
		}
	}

	private function getRedirectFromID($pageid) {
		$title = Title::newFromID($pageid);
		if ($title && $title->isRedirect()) {
			$wikiPage = WikiPage::factory( $title );
			if ($wikiPage) {
				$target = $wikiPage->getRedirectTarget();
				if ($target && $target->exists()) {
					return $target->getDBkey();
				}
			}
		}
		return '';
	}

	function startOutput($filename, $headerRow, $mimeType  = 'text/tsv') {
		global $wgOut, $wgRequest;
		#$wgOut->setArticleBodyOnly(true);
		#$wgRequest->response()->header('Content-type: ' . $mimeType);
		#$wgRequest->response()->header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
		header("Content-Type: $mimeType");
		header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
		print $headerRow;
	}

	function addOutput(&$output) {
		print $output;
	}

	function getCurrentUserName() {
		global $wgUser;

		return $wgUser->getName();
	}

	function getToolVars() {
		$curUser = $this->getCurrentUserName();
		$curUserQueries = $this->getCurrentUserQueries();
		$allQueries = $this->getAllUserQueries();
		if (array_key_exists($curUser, $allQueries)) {
			unset($allQueries[$curUser]);
		}

		return array(
			'dbfields' => $this->getTitusFields(),
			'languages' => $this->languageInfo,
			'curUser' => $curUser,
			'curUserQueries' => $curUserQueries,
			'allQueries' => $allQueries
		);
	}

	function getToolHtml() {
		$vars = $this->getToolVars();
		EasyTemplate::set_path(dirname(__FILE__).'/');
		return EasyTemplate::html('resources/templates/titusquerytool.tmpl.php', $vars);
	}

	function handleVault($action) {
		global $wgRequest;
		$id = $wgRequest->getVal('id');
		if ($action === 'store') {
			$result = array(
				'success' => true
			);

			$qName = $wgRequest->getVal('name');
			$qDescription = $wgRequest->getVal('description');
			$query = $wgRequest->getVal('query');

			if (!$qName) {
				$result['success'] = false;
				$result['errors']['noname'] = 'No query name given';
			}
			if (!$query) {
				$result['success'] = false;
				$result['errors']['noquery'] = 'No query given';
			}

			if ($result['success'] === true) {
				if ($id) {
					$result = $this->updateQuery($qName, $qDescription, $query, $id);
				} else {
					$result = $this->storeQuery($qName, $qDescription, $query);
				}
			}

			print json_encode($result);
		} elseif ($action === 'get_by_id') {
			$result = $this->getQueryInfoById($id);
			$result['success'] = true;
			print json_encode($result);
		} elseif ($action === 'delete') {
			$result = $this->deleteQuery($id);
			print json_encode($result);
		} else {
			print json_encode(array(
				'success' => false,
				'errors' => array('noaction' => 'No action specified')
			));
		}
	}

	function storeQuery($queryName, $queryDescription, $query, &$user=null) {
		if (!$user) {
			global $wgUser;
			$user = $wgUser;
		}

		$result = array(
			'success' => false
		);

		if ($user->isAnon()) {
			$result['errors']['useranon'] = 'Tool restricted for anonymous users';
			return $result;
		}

		if ($this->getQueryByName($user->getName(), $queryName) !== false) {
			$result['errors']['hasquery'] =
				"Query {$user->getName()}#{$queryName} already exists";
			return $result;
		}

		$dbw = wfGetDb(DB_MASTER);

		try {
			$status = $dbw->insert(
				'titusdb2.titus_query_vault',
				array(
					'qv_user' => $user->getId(),
					'qv_user_text' => $user->getName(),
					'qv_name' => $queryName,
					'qv_description' => $queryDescription,
					'qv_timestamp' => wfTimestampNow(),
					'qv_query' => $query
				),
				__METHOD__
			);
		} catch (DBQueryError $e) {
			$status = false;
		}

		if ($status === false) {
			$result['errors']['dberror'] = 'A database error has occurred. Did you provide a unique query name?';
			return $result;
		}

		$id = $dbw->insertId();

		$result['success'] = true;
		$result['id'] = $id;
		$result['user_text'] = $user->getName();


		return $result;
	}

	function getQueryInfoById($id) {
		$dbr = wfGetDB(DB_SLAVE);

		$row = $dbr->selectRow(
			'titusdb2.titus_query_vault',
			'*',
			array(
				'qv_id' => $id
			),
			array(),
			__METHOD__
		);

		return $this->formatQueryInfoRow($row);
	}

	protected function validateExistsAndAllowed($id) {
		$dbr = wfGetDB(DB_SLAVE);

		$row = $dbr->selectRow(
			'titusdb2.titus_query_vault',
			'*',
			array(
				'qv_id' => $id
			),
			array(),
			__METHOD__
		);

		if ($row === false) {
			return array(
				'success' => false,
				'errors' => array('notfound' => "Query with ID $id not found")
			);
		}

		$user = $this->getCurrentUserName();

		if ($row->qv_user_text != $user) {
			return array(
				'success' => false,
				'errors' => array('nopermission' => "User $user does not have permission to edit {$row->qv_user_text}'s query")
			);
		}

		return array('success' => true);
	}

	private function updateQuery($qName, $qDescription, $query, $id) {
		$result = $this->validateExistsAndAllowed($id);

		if (!$result['success']) {
			return $result;
		}
		unset($result);

		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(
			'titusdb2.titus_query_vault',
			array(
				'qv_name' => $qName,
				'qv_description' => $qDescription,
				'qv_query' => $query
			),
			array(
				'qv_id' => $id
			),
			__METHOD__,
			array(
				'IGNORE'
			)
		);

		$result = $this->getQueryInfoById($id);
		$result['success'] = true;
		return $result;
	}

	private function deleteQuery($id) {
		$result = $this->validateExistsAndAllowed($id);

		if (!$result['success']) {
			return $result;
		}
		unset($result);

		$dbw = wfGetDB(DB_MASTER);

		$dbw->delete(
			'titusdb2.titus_query_vault',
			array(
				'qv_id' => $id
			),
			__METHOD__
		);

		return array('success' => true);
	}

	function getCurrentUserQueries() {
		global $wgUser;

		if ($wgUser->isAnon()) {
			return array();
		}

		$queries = array();
		$username = $wgUser->getName();

		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			'titusdb2.titus_query_vault',
			array(
				'qv_id',
				'qv_name'
			),
			array(
				'qv_user_text' => $username
			),
			__METHOD__
		);

		foreach ($res as $row) {
			$queryInfo = array(
				'id' => $row->qv_id,
				'name' => htmlspecialchars($row->qv_name)
			);

			$queries[] = $queryInfo;
		}

		return $queries;
	}

	function getAllUserQueries() {
		$userQueries = array();

		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			'titusdb2.titus_query_vault',
			array(
				'qv_id',
				'qv_user_text',
				'qv_name',
			),
			array(),
			__METHOD__
		);

		foreach ($res as $row) {
			$queryInfo = array(
				'id' => $row->qv_id,
				'name' => htmlspecialchars($row->qv_name),
			);

			$userQueries[$row->qv_user_text][$row->qv_id] = $queryInfo;
		}

		return $userQueries;
	}

	function formatQueryInfoRow(&$row) {
		return array(
			'id' => $row->qv_id,
			'user_id' => $row->qv_user,
			'user_text' => $row->qv_user_text,
			'name' => $row->qv_name,
			'description' => $row->qv_description,
			'timestamp' => $row->qv_timestamp,
			'query' => $row->qv_query
		);
	}

	function getQueryByName($userName, $queryName) {
		$dbr = wfGetDB(DB_SLAVE);

		return $dbr->selectField(
			'titusdb2.titus_query_vault',
			'qv_query',
			array(
							'qv_user_text' => $userName,
							'qv_name' => $queryName
			),
			__METHOD__
		);
	}
}
