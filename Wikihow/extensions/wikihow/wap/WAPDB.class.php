<?
class WAPDB {
	// DB Types
	const DB_CONCIERGE = 1;
	const DB_BABELFISH = 2;
    const DB_EDITFISH = 3;
    const DB_TITLEFISH = 4;
	const DB_CHOCOFISH = 5;
	const DB_RETRANSLATEFISH = 6;

	// DB-specific configuration (table names, etc)
 	protected $wapConfig = null;

	// WAPDB database type
	protected $dbType = null;

	// Singelton DBs
	private static $conciergeDB = null;
	private static $babelfishDB = null;
	private static $editfishDB = null;
    private static $titlefishDB = null;
	private static $chocofishDB = null;
	private static $retranslatefishDB = null;

	// Tag DBs
	protected $articleTagDB = null;
	protected $userTagDB = null;

	public static function getInstance($dbType) {
		if ($dbType == self::DB_CONCIERGE) {
			if (is_null(self::$conciergeDB)) {
				$config = new WAPConciergeConfig();
				$dbClass = $config->getDBClassName();
				self::$conciergeDB = new $dbClass($config);
			}
			return self::$conciergeDB;
		} elseif ($dbType == self::DB_BABELFISH) {
			if (is_null(self::$babelfishDB)) {
				$config = new WAPBabelfishConfig();
				$dbClass = $config->getDBClassName();
				self::$babelfishDB =  new $dbClass($config);
			}
			return self::$babelfishDB;
		} elseif ($dbType == self::DB_EDITFISH) {
			if (is_null(self::$editfishDB)) {
				$config = new WAPEditfishConfig();
				$dbClass = $config->getDBClassName();
				self::$editfishDB =  new $dbClass($config);
			}
			return self::$editfishDB;
		} elseif ($dbType == self::DB_CHOCOFISH) {
			if (is_null(self::$chocofishDB)) {
				$config = new WAPChocofishConfig();
				$dbClass = $config->getDBClassName();
				self::$chocofishDB =  new $dbClass($config);
			}
			return self::$chocofishDB;
        } elseif ($dbType == self::DB_TITLEFISH) {
            if (is_null(self::$titlefishDB)) {
                $config = new WAPTitlefishConfig();
                $dbClass = $config->getDBClassName();
                self::$titlefishDB = new $dbClass($config);
            }
			return self::$titlefishDB;
		} elseif ($dbType == self::DB_RETRANSLATEFISH) {
			if (is_null(self::$retranslatefishDB)) {
				$config = new WAPRetranslatefishConfig();
				$dbClass = $config->getDBClassName();
				self::$retranslatefishDB = new $dbClass($config);
			}
			return self::$retranslatefishDB;
		} else {
			throw new Exception('No valid system provided');
		}
	}

	protected function __construct(WAPConfig $config) {
		$this->wapConfig = $config;
		$this->articleTagDB = WAPTagDB::getArticleTagDB($config);
		$this->userTagDB = WAPTagDB::getUserTagDB($config);
        $this->dbType = $config->getDBType();
	}

	public function parseUrlList(&$urlList, $langCode) {
		$pageList = preg_split('@[\r\n]+@', $urlList);
		$urls = array();
		foreach ($pageList as $url) {
			if (!empty($url)) {
				$className = $this->getWAPConfig()->getArticleClassName();
				$a = $className::newFromUrl($url, $langCode, $this->dbType);
				$urls[] = array('url' => $url, 'a' => $a, 'lang' => $langCode);
			}
		}
		return $urls;
	}

	/*
	*  Break apart submitted url list into valid, excluded and assigned urls across all supported langs
	*/
	public function processUrlList(&$urlList) {
		$langs = $this->getWAPConfig()->getSupportedLanguages();
		$urls = array();
		foreach ($langs as $lang) {
			$urls[$lang] = $this->processUrlListByLang($urlList, $lang);
		}
		return $urls;
	}

	public function processUrlListByLang(&$urlList, $langCode) {
		$excludedKey = $this->getWAPConfig()->getExcludedArticlesKeyName();
		$excludeList = explode("\n", ConfigStorage::dbGetConfig($excludedKey));
		$urlList = Misc::getUrlDecodedData($urlList);

		$processedUrls = array(
			WAPArticle::STATE_INVALID => array(),
			WAPArticle::STATE_EXCLUDED => array(),
			WAPArticle::STATE_UNASSIGNED => array(),
			WAPArticle::STATE_ASSIGNED => array(),
			WAPArticle::STATE_COMPLETED => array(),
			WAPArticle::STATE_NEW => array());

		$urls = $this->parseURLlist($urlList, $langCode);
		foreach ($urls as $url) {
			// If it's in the system, put it in the right bucket
			$wa = $url['a'];
			if ($wa && $wa->exists()) {
				$url['aid'] = $aid = $wa->getArticleId();
				if (in_array($aid, $excludeList)) {
					$processedUrls[WAPArticle::STATE_EXCLUDED][] = $url;
				} elseif ($wa->isCompleted()) {
					$processedUrls[WAPArticle::STATE_COMPLETED][] = $url;
				} elseif ($wa->isAssigned()) {
					$processedUrls[WAPArticle::STATE_ASSIGNED][] = $url;
				} elseif (!$wa->isAssigned()) {
					$processedUrls[WAPArticle::STATE_UNASSIGNED][] = $url;
				} else {
					throw new Exception ("Bad url: {$url['url']} {$url['lang']}. Report to your trusty wikiHow engineer.");
				}
			} else {
				// If it's not in the system, see if it's even a valid title (exists and isn't a redirect)
				// If it is then check to see if it's excluded.
				// If it isn't excluded then it's considered a new article
				$page = Misc::getPagesFromURLs(array($url['url']), array('page_id', 'page_is_redirect'), true);
				$page = reset($page);
				if (!empty($page) && $page['page_is_redirect'] != 1) {
					$aid = $page['page_id'];
					$url['aid'] = $aid;
					if (in_array($aid, $excludeList)) {
						$processedUrls[WAPArticle::STATE_EXCLUDED][] = $url;
					} else {
						$processedUrls[WAPArticle::STATE_NEW][] = $url;
					}
				} else {
					$processedUrls[WAPArticle::STATE_INVALID][] = $url;
				}
			}
		}

		return $processedUrls;
	}

	public function removeExcludedArticles($langCode) {
		$excludedKey = $this->getWAPConfig()->getExcludedArticlesKeyName();
		$excludeList = explode("\n", ConfigStorage::dbGetConfig($excludedKey));
		if (!empty($excludeList)) {
			$this->removeArticles($excludeList, $langCode);
		}
	}

	public function reserveArticles(&$aids, $langCode, WAPUser &$wu) {
		// Mark completed 0 and reset timestamp since assigned articles can't be completed
		$ts = wfTimestampNow();
		$aids = array_unique($aids);
		$fields = array(
			'ct_user_id' => $wu->getId(),
			'ct_user_text' => $wu->getName(),
			'ct_reserved_timestamp' => $ts,
			'ct_completed' => 0,
			'ct_completed_timestamp' => '');
		$this->insertUrls($aids, $langCode, $fields, true);

		$dbw = wfGetDB(DB_MASTER);
		// marked reserved in article tag table
		$table = $this->wapConfig->getArticleTagTableName();
		$sql = "UPDATE $table SET ca_reserved = 1 WHERE ca_page_id IN (" . implode(",", $aids) . ") and ca_lang_code = '$langCode'";
		$dbw->query($sql);
	}

	/*
	* Reserve a single article.
	*/
	public function reserveArticle($aid, $langCode, &$wu) {
		$dbw = wfGetDB(DB_MASTER);
		$dbType = $this->getWAPConfig()->getDBType();
		$className = $this->getWAPConfig()->getArticleClassName();
		$article = $className::newFromId($aid, $langCode, $dbType);

		// If not a staff user only allow reservation of articles if user user tag matches article tag
		if (!$wu->canView($article)) {
			throw new Exception("You don't have permission to reserve this article.");
			return;
		}

		if (!$article->isReservable()) {
			throw new Exception("This article article has already been reserved. Please refresh this list.");
			return;
		}

		$aids = array($aid);
		$this->reserveArticles($aids, $langCode, $wu);
	}

	public function tagUsers(&$users, &$tags) {
		$uids = array();
		foreach ($users as $user) {
			$uids[] = $user;
		}
		$this->userTagDB->tagUsers($uids, $tags);
	}

	public function tagArticles(&$aids, $langCode, &$tags, $doInsert = true) {
		$aids = array_unique($aids);
		// Perf optimization.  Skip the inserting of urls if you know the articles you are tagging
		// already are in the database.  This is an edge case use solely for BabelfishDB::importBatch()
		if ($doInsert) {
			$this->insertUrls($aids, $langCode);
		}
		$rawTags = $this->getRawTags($tags);
		$this->processTagsOnWAPArticles($aids, $langCode, $rawTags, true);
		$this->articleTagDB->tagArticles($aids, $langCode, $tags);
	}

	public function addNotesToArticles(&$aids, $langCode, $notes) {
		$aids = array_unique($aids);

		$this->insertUrls($aids, $langCode);

		$articleTable = $this->getWAPConfig()->getArticleTableName();

		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(
			$articleTable,
			array('ct_notes' => $notes),
			array('ct_page_id' => $aids, 'ct_lang_code' => $langCode),
			__METHOD__
		);
	}

	// Data format: array(array('lang', 'url', 'note'), ...)
	public function addSeparateNotesToArticles(&$data) {
		$result = array(
			'added' => 0,
			'lengthError' => 0,
			'notFound' => array());

		$className = $this->getWAPConfig()->getArticleClassName();
		$dbType = $this->getWAPConfig()->getDBType();
		$tableName = $this->getWAPConfig()->getArticleTableName();

		$dbw = wfGetDB(DB_MASTER);

		foreach ($data as $row) {
			if (count($row) != 3) {
				$result['lengthError'] += 1;
				continue;
			}

			$lang = $row[0];
			$url = $row[1];
			$note = $row[2];

			$article = $className::newFromUrl($url, $lang, $dbType);
			if (!$article || !$article->exists()) {
				$result['notFound'][] = array('langCode' => $lang, 'url' => $url);
				continue;
			}

			$dbw->update(
				$tableName,
				array('ct_notes' => $note),
				array(
					'ct_page_id' => $article->getPageId(),
					'ct_lang_code' => $article->getLangCode()),
				__METHOD__
			);

			$result['added'] += 1;
		}

		return $result;
	}

	protected function insertUrls(&$aids, $langCode, $fields = array(), $override = false) {
		$dbw = wfGetDB(DB_MASTER);
		$data = array();

		// Escape all the data
		foreach ($fields as $k => $field) {
			$fields[$k] = $dbw->strencode($field);
		}

		// Add title-specific info
		foreach ($aids as $aid) {
			$t = Title::newFromId($aid);
			if (!$t || !$t->exists()) {
				print "GEORGE says 'could not load article id: $aid'\n";
				continue;
			}
			$datum = $fields;
			$datum['ct_page_id'] = $t->getArticleId();
			$datum['ct_lang_code'] = $langCode;
			$datum['ct_page_title'] = $dbw->strencode($t->getDBKey());
			$datum['ct_catinfo'] = Categoryhelper::getTitleCategoryMask($t);
			$datum['ct_categories'] = implode(",", $this->getTopLevelCategories($t));
			$data[] = $datum;
		}

		if (!empty($data)) {
			$table = $this->getWAPConfig()->getArticleTableName();
			$sql = WAPUtil::makeBulkInsertStatement($data, $table, $override);
			$dbw->query($sql, __METHOD__);
		}
	}

	public function getTagIds(&$tags) {
		$tagIds = array();
		foreach ($tags as $tag) {
			$tagIds[] = $tag['tag_id'];
		}
		return $tagIds;
	}

	public function getRawTags(&$tags) {
		$rawtags = array();
		foreach ($tags as $tag) {
			$rawTags[] = $tag['raw_tag'];
		}
		return $rawTags;
	}

	public function getUserIdsForTag(&$tag) {
		return  $this->userTagDB->getUserIdsWithTag($tag);
	}

	public function getTopLevelCategories(&$t) {
		global $wgLang;
		$cats = array();
		if ($t && $t->exists()) {
			$catTxt = $wgLang->getNsText (NS_CATEGORY) . ":";
			$cats = Categoryhelper::getTitleTopLevelCategories($t);
			foreach($cats as $i => $cat) {
				if ($cat == $catTxt . "WikiHow") {
					// Don't show wikiHow category
					unset($cats[$i]);
				} else {
					$cats[$i] = str_replace($catTxt, "",  $cat);
				}
			}
		}

		if (empty($cats)) {
			$cats[] = "N/A";
		}
		return $cats;
	}

	public function getUsersForTag(&$tag) {
        $uids = $this->getUserIdsForTag($tag);
        if (!$uids) {
            return array();
        }

		$users = array();
		$dbType = $this->getWAPConfig()->getDBType();
		$userClass = $this->getWAPConfig()->getUserClassName();
		foreach ($uids as $uid) {
			$users[] = $userClass::newFromId($uid, $dbType);
		}
		return $users;
	}

	public function removeTagsFromArticles(&$urlList, $langCode, &$tags) {
		$processedUrls = $this->processUrlListByLang($urlList, $langCode);

		$aids = array();
		foreach ($processedUrls as $type => $urls) {
			if ($type != 'invalid') {
				foreach ($urls as $url) {
					$aids[] = $url['aid'];
				}
			}
		}

		$aids = array_unique($aids);
		$tagIds = $this->getTagIds($tags);
		$rawTags = $this->getRawTags($tags);
		$this->processTagsOnWAPArticles($aids, $langCode, $rawTags, false);
		$this->articleTagDB->deleteArticleTagsWithTagIds($aids, $langCode, $tagIds);
	}

	public function removeNotesFromArticles(&$urlList, $langCode) {
		$processedUrls = $this->processUrlListByLang($urlList, $langCode);

		$aids = array();
		foreach ($processedUrls as $type => $urls) {
			if ($type != 'invalid') {
				foreach ($urls as $url) {
					$aids[] = $url['aid'];
				}
			}
		}

		$aids = array_unique($aids);

		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(
			$this->getWAPConfig()->getArticleTableName(),
			array('ct_notes' => ''),
			array('ct_page_id' => $aids, 'ct_lang_code' => $langCode),
			__METHOD__
		);
	}

	public function removeTagsFromUsers(&$uids, &$tags) {
		$tagIds = $this->getTagIds($tags);
		$this->userTagDB->deleteUserTagsWithTagIds($uids, $tagIds);
	}

	protected function logError($msg) {
		return; // Disable logging for now
		global $IP;
		$system = $this->getWAPConfig()->getSystemName();
		$ts = wfTimestampNow();
		@error_log("$system: $ts: $msg\n", 3, "$IP/extensions/wikihow/wap/error_log");
	}

	/*
	*  Add or remove tags to the ct_tag_list field in the articles table. This field is a performance optimization to allow for dumps
	*  of all articles with corresponding tags without having to query the tagdb for each row
	*/
	private function processTagsOnWAPArticles(&$aids, $langCode, $tags, $add = true) {
		$dbr = wfGetDB(DB_SLAVE);
		$dbw = wfGetDB(DB_MASTER);

		$aids = array_unique($aids);
		$pageIds = "(" . implode(",", $aids) . ")";
		$articleTable = $this->getWAPConfig()->getArticleTableName();
		$res = $dbw->select($articleTable,
			array('ct_page_id', 'ct_lang_code', 'ct_tag_list'),
			array("ct_lang_code" => $langCode, "ct_page_id IN $pageIds"),
			__METHOD__);
		$articles = array();
		foreach ($res as $row) {
			$row = get_object_vars($row);
			$this->logError($add ? "add tags" : "remove tags");
			$this->logError("(aid, lang, current tags) - (" . implode(",", array_values($row)) . ")");
			$row['ct_tag_list'] = $add ?
				$this->addTagsToList($row['ct_tag_list'], $tags) :
				$this->removeTagsFromList($row['ct_tag_list'], $tags);
			$row['ct_tag_list'] = $dbw->strencode($row['ct_tag_list']);
			$this->logError("new tags: " . $row['ct_tag_list']);
			$articles[] = $row;
		}

		if (!empty($articles)) {
			$sql = WAPUtil::makeBulkInsertStatement($articles, $articleTable);
			$this->logError(__METHOD__ . ": sql: $sql");
			$dbw->query($sql);
		}
	}

	private function addTagsToList($tagList, $tags) {
		$tagList = empty($tagList) ? array() : explode(",", trim($tagList));
		foreach ($tags as $tag) {
			$pos = array_search($tag, $tagList);
			if ($pos === false) {
				$tagList[] = $tag;
			}
		}
		if (empty($tags)) {
			throw new Exception('$tags list is empty');
		}

		if (empty($tagList)) {
			throw new Exception('$tagList list is empty');
		}

		return $this->makeTagList($tagList);
	}

	private function removeTagsFromList($tagList, $tags) {
		$tagList = empty($tagList) ? array() : explode(",", trim($tagList));
		foreach ($tags as $tag) {
			$pos = array_search($tag, $tagList);
			if ($pos !== false) {
				unset($tagList[$pos]);
			}
		}
		return $this->makeTagList($tagList);
	}

	private function makeTagList($rawTags) {
		if (sizeof($rawTags) > 1) {
			$tagList = implode(",", $rawTags);
		} elseif (sizeof($rawTags) == 1) {
			$tagList = array_pop($rawTags);
		} else {
			$tagList = "";
		}
		return $tagList;
	}

	public function deactivateTags($tags) {
		$tagIds = $this->getTagIds($tags);
		$this->articleTagDB->changeTagState($tagIds, WAPTagDB::TAG_DEACTIVATED);
	}

	public function activateTags($tags) {
		$tagIds = $this->getTagIds($tags);
		$this->articleTagDB->changeTagState($tagIds, WAPTagDB::TAG_ACTIVE);
	}

	public function removeTagsFromSystem(&$tags) {
		$tagsToRemove = $this->getTagIds($tags);
		$unassignedTags = $this->getTagIds($this->articleTagDB->getUnassignedTags());

		// If tags aren't assigned to articles, then they are candidates for removal
		$unassignedTagsToRemove = array_intersect($tagsToRemove, $unassignedTags);

		// These tags are still assigned to 1 or more articles, so we can't remove
		$assigned = array_diff($tagsToRemove, $unassignedTags);

		// Remove unassigned tags from users so they no longer show up on profile page
		$this->userTagDB->deleteAllUserTagsWithTagIds($unassignedTagsToRemove);
		// Finally remove unassigned tags
		$this->userTagDB->deleteTags($unassignedTagsToRemove);

		// Get full tags to return to caller
		$assignedTags = array();
		foreach ($assigned as $tag_id) {
			foreach ($tags as $tag) {
				if ($tag['tag_id'] == $tag_id) {
					$assignedTags[] = $tag;
				}
			}
		}
		return $assignedTags;
	}

	// Return false if user has assigned articles
	public function deactivateUser($uid) {
		$assignedArticles = $this->getArticlesByUser($uid, 0, 1, WAPUser::ARTICLE_ASSIGNED);
		if (empty($assignedArticles)) {
			$ret = true;
			$config = $this->getWAPConfig();
			$dbType = $this->getWAPConfig()->getDBType();
			$userClass = $config->getUserClassName();
			$groupName = $config->getWikiHowGroupName();

			$u = $userClass::newFromId($uid, $dbType);
			$u->removeGroup($groupName);
		} else {
			$ret = false;
		}

		return $ret;
	}

	public function removeUsers($uids) {
		$config = $this->getWAPConfig();
		$db = $this->userTagDB;
		$dbw = wfGetDB(DB_MASTER);
		$dbType = $this->getWAPConfig()->getDBType();
		$userClass = $config->getUserClassName();
		$groupName = $config->getWikiHowGroupName();
		foreach ($uids as $uid) {
			$u = $userClass::newFromId($uid, $dbType);
			$u->removeGroup($groupName);
			// Remove all tags associated with the user
			$db->deleteAllUserTagsByUser($uid);

			// Remove assigned flag
			$articleTable = $this->getWAPConfig()->getArticleTableName();
			$articleTagTable = $this->getWAPConfig()->getArticleTagTableName();
			$sql = "UPDATE $articleTable, $articleTagTable SET ca_reserved = 0
				WHERE ca_page_id = ct_page_id AND ct_lang_code = ca_lang_code AND ct_user_id = $uid AND ct_completed = 0";
			$dbw->query($sql);

			$dbw->update($articleTable, array('ct_user_id' => 0, 'ct_user_text' => '', 'ct_reserved_timestamp' => ''),
				array('ct_user_id' => $uid, 'ct_completed' => 0), __METHOD__);
		}
	}

	public function addUser(&$url, $powerUser = false) {
		$url = trim($url);
		if	(!preg_match('@^https?://www\.wikihow\.com/User:@', $url)) {
			throw new Exception('User URLs should start with http(s)://www.wikihow.com/User:');
		}

		$uname = WAPUtil::getUserNameFromUserUrl($url);
		$success = false;
		$u = User::newFromName($uname);
		if ($u) {
			$uid = $u->getId();
			if (!empty($uid)) {
				$groupName = $this->getWAPConfig()->getWikiHowGroupName();
				$u->addGroup($groupName);

				if ($powerUser) {
					$powerUserGroup = WAPDB::getInstance($this->dbType)->getWAPConfig()->getWikiHowPowerUserGroupName();
					$u->addGroup($powerUserGroup);
				}
				$success = true;
			}
		}
		return $success;
	}

	/*
	* Release an article by removing reserve and completed row data in articles and article tags tables
	*/
	public function releaseArticles(&$aids, $langCode, WAPUser &$wu) {
		if (!empty($aids) && !empty($langCode)) {
			$aids = array_unique($aids);
			$ids = "(" . implode(",", $aids) . ")";
			$conds = array("ct_page_id IN $ids", "ct_lang_code" => $langCode);
			// If not a staff user, only allow updates to rows which user is assigned to
			if (!$wu->inGroup('staff')) {
				$conds["ct_user_id"] = $wu->getId();
			}

			$dbw = wfGetDB(DB_MASTER);
			$articleTable = $this->getWAPConfig()->getArticleTableName();
			$articleTagTable = $this->getWAPConfig()->getArticleTagTableName();
			$dbw->update($articleTable, array('ct_completed' => 0, 'ct_reserved_timestamp' => '', 'ct_user_id' => 0, 'ct_user_text' => ''), $conds, __METHOD__);
			$dbw->update($articleTagTable, array('ca_reserved' => 0), array("ca_page_id IN $ids", "ca_lang_code" => $langCode), __METHOD__);
		}
	}

	/*
	* Remove an article by deleting row data in articles and article tags tables
	*/
	public function removeArticles(&$aids, $langCode) {
		if (!empty($aids) && !empty($langCode)) {
			$aids = array_unique($aids);
			$ids = "(" . implode(",", $aids) . ")";
			$dbw = wfGetDB(DB_MASTER);
			$articleTable = $this->getWAPConfig()->getArticleTableName();
			$articleTagTable = $this->getWAPConfig()->getArticleTagTableName();
			$dbw->delete($articleTagTable, array("ca_page_id IN $ids", "ca_lang_code" => $langCode), __METHOD__);
			$dbw->delete($articleTable, array("ct_page_id IN $ids", "ct_lang_code" => $langCode), __METHOD__);
		}
	}

	public function completeArticles(&$aids, $langCode, &$wu) {
		if (!empty($aids) && !empty($langCode)) {
			$aids = array_unique($aids);
			$ids = "(" . implode(",", $aids) . ")";
			$dbw = wfGetDB(DB_MASTER);
			// Update articles table and set article to complete.
			// Articles must also be assigned, so update the assigned state in case it wasn't already
			$ts = wfTimestampNow();
			$fields = array(
				'ct_user_id' => $wu->getId(),
				'ct_user_text' => $wu->getName(),
				'ct_reserved_timestamp' => $ts,
				'ct_completed' => 1,
				'ct_completed_timestamp' => $ts);
			$this->insertUrls($aids, $langCode, $fields, true);

			// Mark reserved in article tag table
			$table = $this->getWAPConfig()->getArticleTagTableName();
			$sql = "UPDATE $table SET ca_reserved = 1 WHERE ca_page_id IN (" . implode(",", $aids) . ") and ca_lang_code = '$langCode'";
			$dbw->query($sql);
		}
	}

	public function completeArticle($aid, $langCode, &$wu) {
		global $wgRequest, $wgOut;

		$dbw = wfGetDB(DB_MASTER);
		$dbType = $this->getWAPConfig()->getDBType();
		$className = $this->getWAPConfig()->getArticleClassName();
		$wa = $className::newFromId($aid, $langCode, $dbType);
		// If not a staff user, only allow updates to rows which user is assigned to
		if (!$wu->canView($wa)) {
			throw new Exception("You don't have permissions to complete this article");
		}
		$conds = array('ct_page_id' => $aid, 'ct_lang_code' => $langCode);
		$table = $this->getWAPConfig()->getArticleTableName();
		$dbw->update($table, array('ct_completed' => 1, 'ct_completed_timestamp' => wfTimestampNow()), $conds, __METHOD__);

		// Mark reserved in article tag table
		$table = $this->getWAPConfig()->getArticleTagTableName();
		$sql = "UPDATE $table SET ca_reserved = 1 WHERE ca_page_id = $aid and ca_lang_code = '$langCode'";
		$dbw->query($sql);
	}

	public function getWAPConfig() {
		return $this->wapConfig;
	}

	public function getArticles(&$aids, $langCode) {
		$dbr = wfGetDB(DB_SLAVE);
		$articles = array();
		if (sizeof($aids)) {
			$table = $this->getWAPConfig()->getArticleTableName();
			$res = $dbr->select($table, '*',
				array('ct_lang_code' => $langCode, 'ct_page_id in (' . implode(',', $aids) . ')'), __METHOD__);

			$dbType = $this->getWAPConfig()->getDBType();
			$className = $this->getWAPConfig()->getArticleClassName();
			foreach ($res as $row) {
				$articles[] = $className::newFromDBRow($row, $dbType);
			}
		}
		return $articles;
	}

	public function getArticlesByUser($uid, $offset, $limit, $articleState) {
		$dbr = wfGetDB(DB_SLAVE);
		$options = array('OFFSET' => $offset, 'LIMIT' => $limit);
		$conds['ct_user_id'] = $uid;
		if (WAPUser::ARTICLE_ASSIGNED == $articleState) {
			$conds['ct_completed'] = 0;
		} elseif (WAPUser::ARTICLE_COMPLETED == $articleState) {
			$conds['ct_completed'] = 1;
			$options['ORDER BY'] = 'ct_completed_timestamp DESC';
		}

		$table = $this->getWAPConfig()->getArticleTableName();
		$res = $dbr->select($table, array('*'), $conds, __METHOD__, $options);
		$dbType = $this->getWAPConfig()->getDBType();
		$className = $this->getWAPConfig()->getArticleClassName();
		$articles = array();
		foreach ($res as $row) {
			$articles[] = $className::newFromDBRow($row, $dbType);
		}
		return $articles;
	}

	public function getArticlesByTagName($tag, $offset, $limit, $articleState, $catFilter, $orderBy = '') {
		if (!isset($tag)) {
			return false;
		}

		$tag = $this->articleTagDB->getTagByRawTag($tag);
        $tagId = $tag['tag_id'];
        if (!isset($tagId)) {
            return false;
        }

		$dbr = $this->dbr;

		$limitSql = $this->articleTagDB->getLimitSql($offset, $limit);
		$reserved = $articleState == WAPArticleTagDB::ARTICLE_ALL ? "" : "ca_reserved = $articleState AND ";


		$catWhere = "";
		if (!empty($catFilter)) {
			$catWhere = " AND ct_catinfo & $catFilter > 0 ";
		}

		if (!empty($orderBy)) {
			$orderBy = " ORDER BY $orderBy ";
		}

		$articleTagTable = $this->getWAPConfig()->getArticleTagTableName();
		$articleTable = $this->getWAPConfig()->getArticleTableName();
		$sql = "SELECT ct.*
			FROM  $articleTagTable, $articleTable ct
			WHERE
			$reserved
			ca_tag_id = $tagId AND
			ca_page_id = ct_page_id
			$catWhere
			AND ca_lang_code = ct_lang_code
			$orderBy
			$limitSql
			";
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->query($sql, __METHOD__);
		$articles = array();
		$articleClass = $this->getWAPConfig()->getArticleClassName();
		foreach ($res as $row) {
			$articles[] = $articleClass::newFromDBRow($row, $this->dbType);
		}
		return $articles;
	}
	public function getTagCount($tag) {
		if (!isset($tag)) {
			return false;
		}

		$tag = $this->articleTagDB->getTagByRawTag($tag);
		$tagId = $tag['tag_id'];
		if (!isset($tagId)) {
			return false;
		}

		$articleTagTable = $this->getWAPConfig()->getArticleTagTableName();
		$articleTable = $this->getWAPConfig()->getArticleTableName();
		$sql = "SELECT COUNT(*) AS count FROM $articleTagTable WHERE ca_tag_id = $tagId";
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->query($sql, __METHOD__);
		$row = $res->fetchRow();
		return $row['count'];

	}
	public function getUserTagDB() {
		return $this->userTagDB;
	}

	public function getArticleTagDB() {
		return $this->articleTagDB;
	}

	public function getUsers() {
		$dbr = wfGetDB(DB_SLAVE);
		$groupName = $this->wapConfig->getWikiHowGroupName();
		$res = $dbr->select(array('user_groups'), array('ug_user'), array('ug_group' => $groupName), __METHOD__);
		$users = array();
		$userClass = $this->wapConfig->getUserClassName();
		foreach ($res as $row) {
			$users[] = $userClass::newFromId($row->ug_user, $this->dbType);
		}
		usort($users, "$userClass::compareTo");

		return $users;
	}
}

