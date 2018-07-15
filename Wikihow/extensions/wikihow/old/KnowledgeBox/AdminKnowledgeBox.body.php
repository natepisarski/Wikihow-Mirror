<?php

class AdminKnowledgeBox extends UnlistedSpecialPage {
	public function __construct() {
		global $wgTitle;
		$this->specialpage = $wgTitle->getPartialUrl();

		parent::__construct($this->specialpage);
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLanguageCode, $wgHooks;

		// Disable the sidebar to make room for the article table
		$wgHooks['ShowSideBar'][] = array($this, 'removeSideBarCallback');

		$userGroups = $wgUser->getGroups();
		if ($wgLanguageCode != 'en' || $wgUser->isBlocked()
				|| !in_array('staff', $userGroups)) {
			$this->outputNoPermissionsHtml();
			return;
		}

		$action = $wgRequest->getVal('action');
		$kbAid = $wgRequest->getInt('kbAid');
		$kbTopic = $wgRequest->getVal('kbTopic');
		$kbPhrase = $wgRequest->getVal('kbPhrase');
		$kbData = $wgRequest->getVal('kbData');

		if ($action === 'addrow') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->addKBRow($kbAid, $kbTopic, $kbPhrase);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'editrow') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->editKBRow($kbId, $kbAid, $kbTopic, $kbPhrase);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'disablerow') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->setActiveKBRow($kbId, false);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'enablerow') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->setActiveKBRow($kbId, true);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'addbulk') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->addBulk($kbData);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'updatebulk') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->updateBulk($kbData);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'disablebulk') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->disableBulk($kbData);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'enablebulk') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->enableBulk($kbData);
			print_r(json_encode($result));
			return;
		}

		if ($action === 'disableall') {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->disableAll();
			print_r(json_encode($result));
			return;
		}

		if ($action === 'export') {
			$wgOut->disable();

			header('Content-disposition: attachment; filename="knowledgebox_articles.csv"');
			header('Content-type: text/csv');

			$result = $this->getCSVData();
			print($result);
			return;
		}

		$this->outputAdminPageHtml(); 
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public function getTemplateHtml($templateName, &$vars) {
		global $IP;
		$path = "$IP/extensions/wikihow/KnowledgeBox/";
		EasyTemplate::set_path($path);
		return EasyTemplate::html($templateName, $vars);
	}

	function outputAdminPageHtml() {
		global $wgOut, $wgRequest;

		$wgOut->setPageTitle("Knowledge Box Admin");

		if ($wgRequest->getVal('include_inactive')) {
			$kbArticles = $this->getAllKBArticles();
		} else {
			$kbArticles = $this->getActiveKBArticles();
		}

		$vars = $this->getDefaultVars();
		$vars['kb_articles'] = $kbArticles;

		$wgOut->addHtml($this->getTemplateHtml('admin.tmpl.php', $vars));
	}

	protected function outputNoPermissionsHtml() {
		global $wgOut;

		$wgOut->setRobotpolicy('noindex,nofollow');
		$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
	}

	protected function getDefaultVars() {
		$vars = array();
		$vars['css'] = HtmlSnips::makeUrlTag('/extensions/wikihow/KnowledgeBox/adminknowledgebox.css');
		$vars['js'] = HtmlSnips::makeUrlTag('/extensions/wikihow/KnowledgeBox/adminknowledgebox.js');
		$vars['js'] .= HtmlSnips::makeUrlTag('/extensions/wikihow/common/jquery.tablesorter.min.js');

		return $vars;
	}

	public function getAllKBArticles() {
		return $this->getKBArticles(true);
	}

	public function getActiveKBArticles() {
		return $this->getKBArticles(false);
	}

	private function getKBArticles($includeInactive) {
		global $wgServer;

		$dbr = wfGetDB(DB_SLAVE);

		// Join on a subquery to speed up execution
		$subquery = $dbr->selectSQLText(
			array('knowledgebox_contents'),
			array(
				'kbc_kbid',
				'subcount' => 'COUNT(kbc_kbid)'
			),
			array(),
			__METHOD__,
			array(
				'GROUP BY' => array('kbc_kbid')
			)
		);

		$subquery = "($subquery)";

		$where = array();

		if (!$includeInactive) {
			$where['kba.kba_active'] = 1;
		}

		$res = $dbr->select(
			array(
				'kba' => 'knowledgebox_articles',
				'kbc' => $subquery
			),
			array(
				'kba.*',
				'submissions' => 'IFNULL(kbc.subcount, 0)'
			),
			$where,
			__METHOD__,
			array(
				'ORDER BY' => array(
					'kba.kba_active DESC',
					'kba.kba_modified DESC',
					'submissions DESC',
					'kba.kba_id ASC',
					'kba.kba_aid ASC'
				)
			),
			array(
				'kbc' => array(
					'LEFT JOIN',
					array(
						'kbc.kbc_kbid = kba.kba_id'
					)
				)
			)
		);

		$kb_articles = array();
		foreach ($res as $row) {
			$aid = $row->kba_aid;

			$t = Title::newFromID($aid);

			if ($t && $t->exists()) {
				$titleText = $t->getPrefixedText();
				$titleURL = $t->getFullURL();
			} else {
				$titleText = '';
				$titleURL = '';
			}

			$kb_articles[] = array(
				'id' => $row->kba_id,
				'aid' => $aid,
				'title' => $titleText,
				'url' => $titleURL,
				'baseurl' => $wgServer,
				'timestamp' => wfTimestamp(TS_DB, $row->kba_timestamp),
				'topic' => $row->kba_topic,
				'phrase' => $row->kba_phrase,
				'active' => $row->kba_active,
				'modified' => wfTimestamp(TS_DB, $row->kba_modified),
				'submissions' => $row->submissions
			);
		}

		return $kb_articles;
	}

	private function addKBRow($aid, $topic, $phrase) {
		$aid = $aid ?: 0;

		if (!$topic || !$phrase) {
			return array(
				'error' => 'Empty topic and/or phrase not allowed.'
			);
		} elseif (!is_numeric($aid)) {
			return array(
				'error' => 'Article ID must be numeric.'
			);
		}

		$result = array();

		$dbr = wfGetDB(DB_SLAVE);

		$ts = wfTimestampNow();

		$dbw = wfGetDB(DB_MASTER);

		$dbw->insert(
			'knowledgebox_articles',
			array('kba_aid' => $aid,
				  'kba_topic' => $topic,
				  'kba_phrase' => $phrase,
				  'kba_timestamp' => $ts,
				  'kba_active' => 1,
				  'kba_modified' => $ts),
			__METHOD__
		);

		$title = $dbr->selectField(
			'page',
			'page_title',
			array('page_id' => $aid),
			__METHOD__
		);

		$url = '';
		if ($title === false) {
			$title = '';
		} else {
			$t = Title::newFromDBkey($title);
			if (!$t || !$t->exists()) {
				$title = '';
			} else {
				$title = $t->getPrefixedText();
				$url = $t->getFullURL();
			}
		}

		KnowledgeBox::clearTopicMemc();

		return $result + array(
			'id' => '??', // TODO: Fetch the id
			'aid' => $aid,
			'topic' => $topic,
			'phrase' => $phrase,
			'title' => $title,
			'url' => $url,
			'timestamp' => wfTimestamp(TS_DB, $ts),
			'modified' => wfTimestamp(TS_DB, $ts));
	}

	private function editKBRow($id, $aid, $topic, $phrase) {
		$aid = $aid ?: 0;

		if (!$id || !$topic || !$phrase) {
			return array(
				'error' => 'Empty fields not allowed.'
			);
		} elseif (!is_numeric($aid)) {
			return array(
				'error' => 'Article ID must be numeric.'
			);
		}

		$result = array();

		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->selectRow(
			'knowledgebox_articles',
			array('*'),
			array('kba_id' => $id),
			__METHOD__
		);

		if ($res === false) {
			return array(
				'error' => 'This article does not yet exist in KnowledgeBox'
			);
		}

		$ts = wfTimestampNow();

		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(
			'knowledgebox_articles',
			array(
				'kba_aid' => $aid,
				'kba_topic' => $topic,
				'kba_phrase' => $phrase,
				'kba_modified' => $ts
			),
			array(
				'kba_id' => $id
			),
			__METHOD__,
			array('IGNORE')
		);

		$title = $dbr->selectField(
			'page',
			'page_title',
			array('page_id' => $aid),
			__METHOD__
		);

		$url = '';
		if ($title === false) {
			$title = '';
		} else {
			$t = Title::newFromDBKey($title);
			if (!$t || !$t->exists()) {
				$title = '';
			} else {
				$title = $t->getPrefixedText();
				$url = $t->getFullURL();
			}
		}

		KnowledgeBox::clearTopicMemc();

		return $result + array(
			'id' => $id,
			'aid' => $aid,
			'topic' => $topic,
			'phrase' => $phrase,
			'title' => $title,
			'url' => $url,
			'modified' => wfTimestamp(TS_DB, $ts));
	}

	/*
	 * THIS IS NOT ACCESSIBLE FROM THE SPECIALPAGE.
	 */
	private function deleteKBRow($id) {
		if (!$id) {
			return array(
				'error' => 'KnowledgeBox ID not provided.'
			);
		} elseif (!is_numeric($id)) {
			return array(
				'error' => 'KnowledgeBox ID must be numeric.'
			);
		}

		$dbw = wfGetDB(DB_MASTER);

		$dbw->delete(
			'knowledgebox_articles',
			array('kba_id' => $id),
			__METHOD__
		);

		KnowledgeBox::clearTopicMemc();

		return array();
	}

	private function setActiveKBRow($id, $active) {
		if (!$id) {
			return array(
				'error' => 'KnowledgeBox ID not provided.'
			);
		} elseif (!is_numeric($id)) {
			return array(
				'error' => 'KnowledgeBox ID must be numeric.'
			);
		}

		$active = $active ? 1 : 0;

		$ts = wfTimestampNow();

		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(
			'knowledgebox_articles',
			array(
				'kba_active' => $active,
				'kba_modified' => $ts
			),
			array('kba_id' => $id),
			__METHOD__,
			array('IGNORE')
		);

		KnowledgeBox::clearTopicMemc();

		return array(
			'modified' => wfTimestamp(TS_DB, $row->kba_modified)
		);
	}

	private function addBulk(&$kbData) {
		if (!$kbData) {
			return array('error' => 'No data received.');
		}

		$data = json_decode($kbData);

		if (!$data) {
			return array('error' => 'No data received.');
		}

		$sqlInsertData = array();
		$ts = wfTimestampNow();

		foreach ($data as $line) {
			$sqlInsertData[] = array(
				'kba_aid' => $line[0],
				'kba_topic' => $line[1],
				'kba_phrase' => $line[2],
				'kba_timestamp' => $ts,
				'kba_active' => 1,
				'kba_modified' => $ts
			);
		}

		$dbw = wfGetDB(DB_MASTER);

		if (!empty($sqlInsertData)) {
			$dbw->insert(
				'knowledgebox_articles',
				$sqlInsertData,
				__METHOD__
			);
		}

		KnowledgeBox::clearTopicMemc();

		return array(
			'type' => 'add',
			'updatedData' => $sqlInsertData
		);
	}

	private function updateBulk(&$kbData) {
		if (!$kbData) {
			return array('error' => 'No data received.');
		}

		$data = json_decode($kbData);

		if (!$data) {
			return array('error' => 'No data received.');
		}

		$ids = array();
		$values = array();
		$ts = wfTimestampNow();

		foreach ($data as $line) {
			$ids[] = $line[0];
			$values[] = array(
				'kba_id' => $line[0],
				'kba_aid' => $line[1],
				'kba_topic' => $line[2],
				'kba_phrase' => $line[3],
				'kba_modified' => $ts
			);
		}

		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			'knowledgebox_articles',
			'kba_id',
			array('kba_id' => $ids)
		);
		$selectedIds = array();

		foreach ($res as $row) {
			$selectedIds[] = $row->kba_id;
		}

		$diff = array_diff($ids, $selectedIds);
		if ($diff) {
			return array('error' => 'Received broken IDs: ' . implode(',', $diff));
		}

		$dbw = wfGetDB(DB_MASTER);

		$dbw->upsert(
			'knowledgebox_articles',
			$values,
			array('kba_id'),
			array(
				'kba_aid=VALUES(kba_aid)',
				'kba_topic=VALUES(kba_topic)',
				'kba_phrase=VALUES(kba_phrase)',
				'kba_modified=VALUES(kba_modified)'
			),
			__METHOD__
		);

		$res = $dbr->select(
			'knowledgebox_articles',
			'*',
			array('kba_id' => $ids),
			__METHOD__
		);

		$updatedData = array();
		foreach ($res as $row) {
			$updatedData[] = $row;
		}

		KnowledgeBox::clearTopicMemc();

		return array(
			'type' => 'update',
			'updatedData' => $updatedData
		);
	}

	/*
	 * THIS IS NOT ACCESSIBLE FROM THE SPECIALPAGE.
	 */
	private function deleteBulk(&$kbData) {
		if (!$kbData) {
			return array('error' => 'No data received.');
		}

		$ids = json_decode($kbData);

		if (!$ids) {
			return array('error' => 'No data received.');
		}

		$dbr = wfGetDB(DB_SLAVE);

		$count = $dbr->selectField(
			'knowledgebox_articles',
			'COUNT(*)',
			array('kba_id' => $ids),
			__METHOD__
		);

		$dbw = wfGetDB(DB_MASTER);

		$dbw->delete(
			'knowledgebox_articles',
			array('kba_id' => $ids),
			__METHOD__
		);

		KnowledgeBox::clearTopicMemc();

		return array(
			'deletedCount' => $count
		);
	}

	private function setActiveBulk(&$kbData, $active) {
		if (!$kbData) {
			return array('error' => 'No data received.');
		}

		$ids = json_decode($kbData);

		if (!$ids) {
			return array('error' => 'No data received.');
		}

		if ($active) {
			$setActive = '1';
			$selectActive = '0';
			$type = 'enable';
		} else {
			$setActive = '0';
			$selectActive = '1';
			$type = 'disable';
		}

		$dbr = wfGetDB(DB_SLAVE);

		$count = $dbr->selectField(
			'knowledgebox_articles',
			'COUNT(*)',
			array(
				'kba_id' => $ids,
				'kba_active' => $selectActive
			),
			__METHOD__
		);

		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(
			'knowledgebox_articles',
			array(
				'kba_active' => $setActive,
				'kba_modified' => wfTimestampNow()
			),
			array('kba_id' => $ids),
			__METHOD__,
			array('IGNORE')
		);

		KnowledgeBox::clearTopicMemc();

		return array(
			'updatedCount' => $count,
			'type' => $type
		);
	}

	private function disableBulk(&$kbData) {
		return $this->setActiveBulk($kbData, '0');
	}

	private function enableBulk(&$kbData) {
		return $this->setActiveBulk($kbData, '1');
	}

	/*
	 * THIS IS NOT ACCESSIBLE FROM THE SPECIALPAGE.
	 */
	private function deleteAll() {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->delete(
			'knowledgebox_articles',
			'*',
			__METHOD__
		);

		KnowledgeBox::clearTopicMemc();

		return array();
	}

	private function disableAll() {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(
			'knowledgebox_articles',
			array('kba_active' => 0),
			array(),
			__METHOD__,
			array('IGNORE')
		);

		Knowledge::clearTopicMemc();

		return array();
	}

	private function getCSVData() {
		$kbArticles = $this->getAllKBArticles();
		$data = '';

		$data .=
			"ID,Article ID,Active,URL,Topic,Phrase,Submissions,"
			. "Time created,Time modified\n";

		foreach ($kbArticles as $kbArticle) {
			$data .=
				$kbArticle['id'] . ','
				. $kbArticle['aid'] . ','
				. $kbArticle['active'] . ','
				. $kbArticle['url'] . ','
				. $kbArticle['topic'] . ','
				. $kbArticle['phrase'] . ','
				. $kbArticle['submissions'] . ','
				. $kbArticle['timestamp'] . ','
				. $kbArticle['modified'] . "\n";
		}

		return $data;
	}
}
