<?php
abstract class WAPTagDB {
	protected $dbr = null;
	protected $dbw = null;
	protected $wapConfig = null;

	const MAX_TAG_LENGTH = 200;

	const TAG_ALL = 1;
	const TAG_ACTIVE = 2;
	const TAG_DEACTIVATED = 3;

	function __construct($wapConfig) {
		$this->wapConfig = $wapConfig;

		if (!is_object($this->dbw)) {
			$this->dbw = wfGetDB(DB_MASTER);
		}

		if (!is_object($this->dbr)) {
			$this->dbr = wfGetDB(DB_SLAVE);
		}
	}

	public static function getUserTagDB(WAPConfig $config) {
		return new WAPUserTagDB($config);
	}

	public static function getArticleTagDB(WAPConfig $config) {
		return new WAPArticleTagDB($config);
	}

	public function sanitizeRawTag($tag) {
		return urldecode(str_replace('-', ' ', $tag));
	}

	/*
	* Add tags that don't exist in the WAPTagDB yet
	*/
	protected function addNewTags($tags) {
		$tagMap = $this->getTagMap();
		// First, add any new tags
		foreach ($tags as $k => $tag) {
			// Double-check it wasn't added. This can happen in the case where someone doesn't referesh
			// the add tag dialog when adding a new tag
			$tagId = $tagMap[$tag['raw_tag']];
			if (!empty($tagId)) {
				$tags[$k]['tag_id'] = $tagId;
			} else {
				$tagId = $this->addTag($tag['raw_tag'], $tag['raw_tag']);
				$tags[$k]['tag_id'] = $tagId;
				$tagMap[$tag['raw_tag']] = $tagId;
			}
		}
		return $tags;
	}

	/*
	*  Create a new tag in the db
	*/
	protected function addTag($normalized_tag, $tag) {
		$dbw = wfGetDB(DB_MASTER);
		$tag = $dbw->strencode($tag);
		$normalized_tag = $dbw->strencode($normalized_tag);

		if (strlen($normalized_tag) > WAPTagDB::MAX_TAG_LENGTH) {
			throw new Exception('Tag name too many characters');
		}

		$table = $this->wapConfig->getTagTableName();

		$sql = "INSERT INTO $table (ct_tag, ct_raw_tag) VALUES ('$normalized_tag', '$tag')";
		$res = $dbw->query($sql, __METHOD__);
		return $dbw->insertId();
	}

	public function deleteTags($tagIds) {
		$dbw = wfGetDB(DB_MASTER);	
		$table = $this->wapConfig->getTagTableName();

		if (!empty($tagIds)) {
			$tagIds = "(" . implode(",", $tagIds) . ")";

			$sql = "DELETE FROM $table WHERE ct_id IN $tagIds";
			$dbw->query($sql);
		}
	}

	public function changeTagState($tagIds, $state) {
		$dbw = wfGetDB(DB_MASTER);
		$table = $this->wapConfig->getTagTableName();

		if (!empty($tagIds) && ($state == self::TAG_ACTIVE || $state == self::TAG_DEACTIVATED)) {
			$tagIds = "(" . implode(",", $tagIds) . ")";
			$deactivatedState = $state == self::TAG_ACTIVE ? 0 : 1;
			$dbw->update($table, array('ct_deactivated' => $deactivatedState), array("ct_id IN $tagIds"), __METHOD__);
		}
	}

	public function getTagByRawTag($raw_tag) {
		if (!isset($raw_tag)) {
			die("getTagByRawTag argument missing");
			return false;
		}

		$dbr = $this->dbr;
		$raw_tag = $dbr->strencode($raw_tag);
		$table = $this->wapConfig->getTagTableName();

		$sql = "SELECT ct_id, ct_raw_tag  FROM $table 
			WHERE 
			ct_raw_tag = '$raw_tag'
			LIMIT 1
			";	
		$res = $dbr->query($sql, __METHOD__);
		$tag = array();
		if ($row = $dbr->fetchObject($res)) {
			$tag = array('tag_id' => $row->ct_id, 'raw_tag' => $row->ct_raw_tag);
		}

		return $tag;
	}

	protected function getTagTypeWhereClause($tagType) {
		$where = "";
		switch ($tagType) {
			case self::TAG_ALL:
				// Do nothing
                break;
			case self::TAG_DEACTIVATED:
				$where = 'ct_deactivated = 1';
				break;
			case self::TAG_ACTIVE:
				$where = 'ct_deactivated = 0';
				break;
		}
		return $where;
	}

	public function getAllTags($tagType = self::TAG_ACTIVE) {
		$dbr = $this->dbr;
		$table = $this->wapConfig->getTagTableName();

		$where = $this->getTagTypeWhereClause($tagType);
		if (!empty($where)) {
			$where = "WHERE $where";
		}

		$sql = "SELECT DISTINCT ct_id, ct_tag, ct_raw_tag, ct_deactivated FROM $table $where ORDER BY ct_tag ASC";
		$res = $dbr->query($sql, __METHOD__);
		$retarr = array();
		foreach ($res as $row) {
			$retarr[] = array(
					'tag_id' => $row->ct_id,
					'tag' => $row->ct_tag,
					'raw_tag' => $row->ct_raw_tag,
					'deactivated' => $row->ct_deactivated,
					);
		}
		return $retarr;
	}

	public function getTagMap() {
		$dbr = wfGetDB(DB_SLAVE);
		$table = $this->wapConfig->getTagTableName();
		$res = $dbr->select($table, array('ct_id', 'ct_raw_tag'), '', __METHOD__);
		$tagMap = array();	
		foreach ($res as $row) {
			$tagMap[$row->ct_raw_tag] = $row->ct_id;
		}
		return $tagMap;
	}

	public function getLimitSql($offset, $limit) {
		return $limit <= 0 ? "" : "LIMIT $offset, $limit";
	}

}

class WAPUserTagDB extends WAPTagDB  {
	function __construct(WAPConfig $config) {
		parent::__construct($config);
	}

   function getUserIdsWithTag($tag) {
		if (!isset($tag)) {
			return false;
		}               

		$dbr = $this->dbr;
		$tag = $this->getTagByRawTag($tag);
        $tagId = $tag['tag_id'];
        if (!isset($tagId)) {
            return false;
        }

		$userTagTable = $this->wapConfig->getUserTagTableName();

		$sql = "SELECT DISTINCT cr_user_id
			FROM $userTagTable
			WHERE cr_tag_id = $tagId
			ORDER BY cr_user_id ASC ";

			$res = $dbr->query($sql, __METHOD__);
		$retarr = array();
		foreach ($res as $row) {
			$retarr[] = $row->cr_user_id;
		}
		return $retarr;
	}

	function getUserTags($userId, $tagType = self::TAG_ACTIVE) {
		if (!isset($userId)) {
			return false;
		}		
		$dbr = $this->dbr;

		$where = $this->getTagTypeWhereClause($tagType);
		if (!empty($where)) {
			$where = " AND $where ";
		}

		$userTagTable = $this->wapConfig->getUserTagTableName();
		$tagTable = $this->wapConfig->getTagTableName();

		$sql = "SELECT DISTINCT ct_tag, ct_raw_tag, ct_deactivated
			FROM  $userTagTable INNER JOIN $tagTable ON (cr_tag_id = ct_id)
			WHERE cr_user_id = $userId $where
			ORDER BY ct_tag ASC";

		$res = $dbr->query($sql, __METHOD__);
		$retarr = array();
		foreach ($res as $row) {
			$retarr[] = array(
					'tag' => $row->ct_tag,
					'raw_tag' => $row->ct_raw_tag,
					'ct_deactivated' => $row->ct_deactivated,
					);
		}
		return $retarr;
	}

	function tagUsers($userIds, $tags) {
		$dbw = $this->dbw;	
		$table = $this->objectTable;
		
		// Check for and add new tags
		$tags = $this->addNewTags($tags);
		
		$ts = wfTimestampNow(TS_UNIX);
		$data = array();
		foreach ($userIds as $uid) {
			foreach($tags as $tag) {
			$data[] = array(
				"cr_tag_id" => $tag['tag_id'],
				"cr_user_id" => $uid,
				"cr_tagged_on" => $ts);
			}
		}

		if (!empty($data)) {
			$userTagTable = $this->wapConfig->getUserTagTableName();
			$sql = WAPUtil::makeBulkInsertStatement($data, $userTagTable);
			$dbw->query($sql);
		}
	}

	function deleteAllUserTagsByUser($userId) {
		$dbw = $this->dbw;

		$table = $this->wapConfig->getUserTagTableName();
		if ($userId > 0) {
			$sql = "DELETE FROM $table WHERE cr_user_id = $userId";	
			$res = $dbw->query($sql, __METHOD__);
			return true;
		} else {
			return false;	
		}
	}

	function deleteAllUserTagsWithTagIds($tagIds) {
		$dbw = wfGetDB(DB_MASTER);	
		$table = $this->wapConfig->getUserTagTableName();

		if (!empty($tagIds)) {
			$tagIds = "(" . implode(",", $tagIds) . ")";

			$sql = "DELETE FROM $table WHERE cr_tag_id IN $tagIds";
			$dbw->query($sql);
		}
	}

	function deleteUserTagsWithTagIds($userIds, $tagIds) {
		if (empty($userIds)|| empty($tagIds)) {
			die("deleteUserTagsWithTagIds arguments missing or empty");
			return false;
		}
		$dbw = $this->dbw;

		$userIds = "(" . implode(", ", $userIds) . ")";
		$tagIds = "(" . implode(", ", $tagIds) . ")";
		$table = $this->wapConfig->getUserTagTableName();

		$sql = "DELETE FROM $table WHERE cr_user_id IN $userIds AND cr_tag_id in $tagIds";	
		$res = $dbw->query($sql, __METHOD__);
		return true;
	}
}

/*
 * TODO: Abstract this out and use separate tag DBs accessed through config?
 */
class WAPArticleTagDB extends WAPTagDB {

	const ARTICLE_UNASSIGNED = 0;
	const ARTICLE_ASSIGNED = 1;
	const ARTICLE_ALL = 2;
	const ARTICLE_ASSIGNABLE = 3;

	function __construct(WAPConfig $config) {
		parent::__construct($config);
	}

	function getAssignedArticleTags($tagType = self::TAG_ACTIVE) {
		$dbr = $this->dbr;

		$where = $this->getTagTypeWhereClause($tagType);
		if (!empty($where)) {
			$where = "WHERE $where";
		}

		$tagTable = $this->wapConfig->getTagTableName();
		$articleTagTable = $this->wapConfig->getArticleTagTableName();
		$sql = "SELECT DISTINCT ct_id, ct_tag, ct_raw_tag, ct_deactivated
			FROM $tagTable INNER JOIN $articleTagTable ON (ca_tag_id = ct_id)
			$where
			ORDER BY ct_tag ASC
			";
		$res = $dbr->query($sql, __METHOD__);
		$retarr = array();
		foreach ($res as $row) {
			$retarr[] = array(
				'tag_id' => $row->ct_id,
				'tag' => $row->ct_tag,
				'raw_tag' => $row->ct_raw_tag,
				'deactivated' => $row->ct_deactivated,
				);
		}
		return $retarr;
	}

	function getUnassignedTags($tagType = self::TAG_ACTIVE) {
		$dbr = $this->dbr;

		$tagTable = $this->wapConfig->getTagTableName();
		$articleTagTable = $this->wapConfig->getArticleTagTableName();

		$where = $this->getTagTypeWhereClause($tagType);
		if (!empty($where)) {
			$where = " AND $where";
		}

		$sql = "SELECT DISTINCT a.ct_id, a.ct_tag, a.ct_raw_tag
			FROM $tagTable as a
			WHERE
				NOT EXISTS (SELECT b.ca_tag_id FROM $articleTagTable b WHERE b.ca_tag_id = a.ct_id LIMIT 1) $where
			ORDER BY ct_tag ASC
			";
		$res = $dbr->query($sql, __METHOD__);
		$retarr = array();
		foreach ($res as $row) {
			$retarr[] = array(
					'tag_id' => $row->ct_id,
					'tag' => $row->ct_tag,
					'raw_tag' => $row->ct_raw_tag,
					'deactivated' => $row->ct_deactivated,
					);
		}
		return $retarr;
	}

	function tagArticles(&$pageIds, $langCode, $tags) {
		$dbw = $this->dbw;	

		// Check for and add new tags
		$tags = $this->addNewTags($tags);
		
		$ts = wfTimestampNow(TS_UNIX);
		$data = array();
		foreach ($pageIds as $aid) {
			foreach($tags as $tag) {
			$data[] = array(
				"ca_tag_id" => $tag['tag_id'],
				"ca_page_id" => $aid,
				"ca_lang_code" => $langCode,
				"ca_tagged_on" => $ts);
			}
		}

		if (!empty($data)) {
			$articleTagTable = $this->wapConfig->getArticleTagTableName();
			$articleTable = $this->wapConfig->getArticleTableName();
			$sql = WAPUtil::makeBulkInsertStatement($data, $articleTagTable);
			$dbw->query($sql);

			// Update reserved flag
			$ids = implode(",", $pageIds);
			$sql = "UPDATE $articleTable, $articleTagTable SET ca_reserved = 1
				WHERE ca_page_id = ct_page_id AND ca_lang_code = ct_lang_code AND ca_lang_code = '$langCode' AND (ct_user_id > 0 OR ct_completed = 1) AND ca_page_id IN ($ids)";
			$dbw->query($sql);
		}
	}

	/*
	 * NOTE: Titlefish-specific
	 * TODO: Subclass this out?
	 */
	function tagArticlesByTitle(&$titles, $langCode, $tags) {
		$dbw = $this->dbw;

		// Check for and add new tags
		$tags = $this->addNewTags($tags);
		$ts = wfTimestampNow(TS_UNIX);
		$data = array();
		foreach ($titles as $title) {
			foreach ($tags as $tag) {
				$data[] = array(
					'ca_tag_id' => $tag['tag_id'],
					'ca_page_title' => $dbw->strencode($title),
					'ca_lang_code' => $langCode,
					'ca_tagged_on' => $ts);
			}
		}

		if (!empty($data)) {
            $dbType = $this->wapConfig->getDBType();
			$articleClass = $this->wapConfig->getArticleClassName();
			$articleTagTable = $this->wapConfig->getArticleTagTableName();
			$articleTable = $this->wapConfig->getArticleTableName();
			$sql = WAPUTIL::makeBulkInsertStatement($data, $articleTagTable);
			$dbw->query($sql);

			// Update number of reservations in article tags
			$titlesByNumReserved = array();
			foreach($titles as $title) {
				$a = $articleClass::newFromTitle($title, $langCode, $dbType);
				$titlesByNumReserved[$a->getNumReservations()][] = $title;
			}

			foreach ($titlesByNumReserved as $n => $titleGroup) {
				$pageTitles = "(" . implode(",", array_map(array($dbw, 'addQuotes'), $titleGroup)) . ")";
				$sql = "UPDATE $articleTable, $articleTagTable
						SET ca_reservations = $n
						WHERE ca_page_title = ct_page_title
							AND ca_lang_code = '$langCode'
							AND ca_page_title in $pageTitles";
				$dbw->query($sql);
			}
		}
	}

	public function getTagsOnArticle($pageId, $langCode, $tagType = self::TAG_ACTIVE) {
		if (!isset($pageId) && !isset($langCode)) {
			return false;
		}		
		$dbr = $this->dbr;
		$articleTagTable = $this->wapConfig->getArticleTagTableName();
		$tagTable = $this->wapConfig->getTagTableName();

		$where = $this->getTagTypeWhereClause($tagType);
		if (!empty($where)) {
			$where = " AND $where";
		}

		$sql = "SELECT DISTINCT ct_tag, ct_raw_tag, ct_deactivated
			FROM $articleTagTable INNER JOIN $tagTable ON (ca_tag_id = ct_id)
			WHERE ca_page_id = $pageId AND ca_lang_code = '$langCode' $where
			ORDER BY ct_tag ASC";

		$res = $dbr->query($sql, __METHOD__);
		$retarr = array();
		foreach ($res as $row) {
			$retarr[] = array(
				'tag' => $row->ct_tag,
				'raw_tag' => $row->ct_raw_tag,
				'deactivated' => $row->ct_deactivated,
				);
		}
		return $retarr;
	}

	public function getTagsOnArticleByTitle($pageTitle, $langCode, $tagType = self::TAG_ACTIVE) {
		if (!isset($pageTitle) && !isset($langCode)) {
			return false;
		}
		$dbr = $this->dbr;
		$articleTagTable = $this->wapConfig->getArticleTagTableName();
		$tagTable = $this->wapConfig->getTagTableName();


		$where = $this->getTagTypeWhereClause($tagType);
		if (!empty($where)) {
			$where = " AND $where";
		}

		$pageTitle = $dbr->addQuotes($pageTitle);

		$sql = "SELECT DISTINCT ct_tag, ct_raw_tag
			FROM $articleTagTable INNER JOIN $tagTable ON (ca_tag_id = ct_id)
			WHERE ca_page_title = $pageTitle AND ca_lang_code = '$langCode' $where
			ORDER BY ct_tag ASC";

		$res = $dbr->query($sql, __METHOD__);
		$retarr = array();
		foreach($res as $row) {
			$retarr[] = array(
				'tag' => $row->ct_tag,
				'raw_tag' => $row->ct_raw_tag,
				'deactivated' => $row->ct_deactivated,
				);
		}
		return $retarr;
	}

	function deleteArticleTagsWithTagIds($pageIds, $langCode, $tagIds) {
		if (empty($pageIds)|| empty($tagIds) || empty($langCode)) {
			die("deleteArticleTagsWithTagIds arguments missing or empty");
			return false;
		}
		$dbw = $this->dbw;

		$pageIds = "(" . implode(", ", $pageIds) . ")";
		$tagIds = "(" . implode(", ", $tagIds) . ")";
		$table = $this->wapConfig->getArticleTagTableName();

		$sql = "DELETE FROM $table
			WHERE 
			ca_page_id IN $pageIds
			AND ca_lang_code = '$langCode'
			AND ca_tag_id in $tagIds
			";	
		$res = $dbw->query($sql, __METHOD__);
		return true;
	}

	function deleteArticleTagsWithTagIdsByTitle($pageTitles, $langCode, $tagIds) {
		if (empty($pageTitles) || empty($tagIds) || empty($langCode)) {
			die("deleteArticleTagsWithTagIdsByTitle arguments missing or empty");
			return false;
		}
		$dbw = $this->dbw;

		$pageTitles = "(" . implode(", ", array_map(array($dbw, 'addQuotes'), $pageTitles)) . ")";
		$tagIds = "(" . implode(", ", $tagIds) . ")";
		$table = $this->wapConfig->getArticleTagTableName();

		$sql = "DELETE FROM $table
			WHERE
			ca_page_title IN $pageTitles
			AND ca_lang_code = '$langCode'
			AND ca_tag_id in $tagIds
			";
		$res = $dbw->query($sql, __METHOD__);
		return true;
	}
}
