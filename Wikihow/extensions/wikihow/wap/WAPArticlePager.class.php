<?
class WAPArticlePager {
	const NUM_ROWS = 500;
	protected $dbType = null;

	public function __construct($dbType) {
		$this->dbType = $dbType;
	}

	public function getUserAssignedPager(WAPUser &$u, $offset, $rows = NUM_ROWS) {
		$vars['assigned'] = $this->getUserAssignedRows($u, $offset, $rows);
		$vars['offset'] = $offset;
		$vars['numrows'] = $rows;
		$vars['u'] = $u;

		$tmpl = new WAPTemplate($this->dbType);
		return $tmpl->getHtml('user_assigned_pager.tmpl.php', $vars);
	}

	public function getUserAssignedRows(WAPUser &$u, $offset, $rows = NUM_ROWS) {
		global $wgUser; 

		$vars['u'] = $u;
		$vars['articles'] = $u->getAssignedArticles($offset, $rows);
		$vars['numrows'] = $rows;
		$config = WAPDB::getInstance($this->dbType)->getWAPConfig();
		$userClass = $config->getUserClassName();
		$vars['currentUser'] = $userClass::newFromUserObject($wgUser, $this->dbType);
		$linkerClass = $config->getLinkerClassName();
		$vars['linker'] = new $linkerClass($this->dbType);

		$tmpl = new WAPTemplate($this->dbType);
		return $tmpl->getHtml('user_assigned_pager_rows.tmpl.php', $vars);
	}

	public function getTagListPager($rawTag, $offset, $rows = NUM_ROWS) {
		$context = RequestContext::getMain();
		$config = WAPDB::getInstance($this->dbType)->getWAPConfig();
		$userClass = $config->getUserClassName();
		$vars['u'] = $userClass::newFromUserObject($context->getUser(), $this->dbType);
		$vars['rows'] = $this->getTagListRows($rawTag, $offset, $rows);
		$vars['offset'] = $offset;
		$vars['numrows'] = $rows;
		$vars['tag'] = $rawTag;

		$tmpl = new WAPTemplate($this->dbType);
		return $tmpl->getHtml('tag_list_pager.tmpl.php', $vars);
	}

	public function getTagListRows($rawTag, $offset, $rows = NUM_ROWS, $filter = null) {
		$context = RequestContext::getMain();
		$db = WAPDB::getInstance($this->dbType);

		$config = WAPDB::getInstance($this->dbType)->getWAPConfig();
		$userClass = $config->getUserClassName();
		$vars['u'] = $userClass::newFromUserObject($context->getUser(), $this->dbType);
		$vars['articles'] = $db->getArticlesByTagName($rawTag, $offset, $rows, WAPArticleTagDB::ARTICLE_UNASSIGNED, $filter);
		$vars['numrows'] = $rows;
		$vars['tag'] = $rawTag;
		$config = WAPDB::getInstance($this->dbType)->getWAPConfig();
		$linkerClass = $config->getLinkerClassName();
		$vars['linker'] = new $linkerClass($this->dbType);

		$tmpl = new WAPTemplate($this->dbType);
		return $tmpl->getHtml('tag_list_pager_rows.tmpl.php', $vars);
	}
}
