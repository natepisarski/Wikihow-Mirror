<?php
abstract class WAPUser {
	var $dbType = null;
	var $u = null;
	var $tags = null;

	const ARTICLE_ASSIGNED  = 1;
	const ARTICLE_COMPLETED = 2;
	const ARTICLE_ALL= 3;

	const DEFAULT_OFFSET = 0;
	const DEFAULT_ROWS = 100;

	const EX_NO_LANG_TAG = "No language tag assigned to user";

	// initializes the object
	protected function init(&$u, $dbType) {
		$this->dbType = $dbType;
		$this->u = $u;
		$this->u->load();
	}

	abstract public static function newFromId($uid, $dbType);

	abstract public static function newFromName($userName, $dbType);

	public function getId() {
		return $this->u->getId();
	}

	public function getName() {
		return $this->u->getName();
	}

	public function getRealName() {
		return $this->u->getRealName();
	}

	public function removeGroup($group) {
		return $this->u->removeGroup($group);
	}

	public function getTags() {
		if (is_null($this->tags)) {
			$tu = WAPDB::getInstance($this->dbType)->getUserTagDB();
			$this->tags = $tu->getUserTags($this->u->getId());
		}
		return $this->tags;
	}

	public function getRawTags() {
		$tags =  $this->getTags();
		$rawTags = array();
		foreach ($tags as $tag) {
			$rawTags[] = $tag['raw_tag'];
		}
		return $rawTags;
	}


	public function hasTag(&$tag) {
		return in_array($tag, $this->getRawTags());
	}


	// The language tag the user is identified with, eg. en, es, de
	abstract public function getLanguageTag();

	public function canView(WAPArticle &$article) {
		$adminGroup = WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowAdminGroupName();
		if ($this->inGroup($adminGroup)) {
			$viewable = true;
		} elseif (($article->isAssigned() || $article->isCompleted())
			&& $this->getId() == $article->getUserId()) {
			// Viewable if the article is assigned to or completed by user
			$viewable = true;
		} else {
			// See if there is a tag intersection.
			$viewable = false;
			$tags = $article->getRawTags();
			foreach ($tags as $tag) {
				if ($this->hasTag($tag)) {
					$viewable = true;
					break;
				}
			}
		}
		return $viewable;
	}

	// JTODO fix
	public function getCompletedArticles($offset = self::DEFAULT_OFFSET, $limit = self::DEFAULT_ROWS) {
		$wapDB = WAPDB::getInstance($this->dbType);
		return $wapDB->getArticlesByUser($this->getId(), $offset, $limit, self::ARTICLE_COMPLETED);
	}

	// JTODO fix
	public function getAssignedArticles($offset = self::DEFAULT_OFFSET, $limit = self::DEFAULT_ROWS) {
		$wapDB = WAPDB::getInstance($this->dbType);
		return $wapDB->getArticlesByUser($this->getId(), $offset, $limit, self::ARTICLE_ASSIGNED);
	}

	public function isAdmin() {
		$adminGroup = WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowAdminGroupName();
		return $this->inGroup($adminGroup);
	}

	public function isPowerUser() {
		$group = WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowPowerUserGroupName();
		$userGroup = WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowGroupName();
		return $this->inGroup($group) && $this->inGroup($userGroup);
	}

	public function inGroup($group) {
		return in_array($group, $this->u->getGroups());
	}

	function hasPermissions($action = array('')) {
		global $wgIsDevServer;
		$hasPermissions = false;
		$adminGroup = WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowAdminGroupName();
		$userGroup = WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowGroupName();

		if ($this->inGroup($userGroup)) {
			switch ($action[0]) {
				case 'tag':
					if ($this->hasTag(WAPTagDB::sanitizeRawTag($action[1]))) {
						$hasPermissions = true;
					}
					break;
				case 'user':
					if ($action[1] == $this->getId()) {
						$hasPermissions = true;
					}
					break;
				case 'reserve_article':
				case 'complete_article':
				case 'article_details':
				case 'release_article':
				case 'assigned_list_more_rows':
				case 'tag_list_more_rows':
					$hasPermissions = true;
			}
		}

		if ($this->isPowerUser()) {
			switch ($action[0]) {
				case 'rpt_assigned_articles':
				case 'rpt_completed_articles':
					$hasPermissions = true;
			}
		}

		if ($this->inGroup($adminGroup)) {
			// Admins can only access the system from dev or a wikiknowhow.com domain
			// for perf reasons
			$hasPermissions = $wgIsDevServer;
			$hasPermissions = true;
		}


		return $hasPermissions;
	}

	public static function compareTo($a, $b) {
		$al = strtolower($a->getName());
		$bl = strtolower($b->getName());
		if ($al == $bl) {
			return 0;
		}
		return ($al > $bl) ? +1 : -1;
	}
}
