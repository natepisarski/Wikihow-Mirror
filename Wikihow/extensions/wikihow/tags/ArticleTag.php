<?php

if (!defined('MEDIAWIKI')) die();

/**
 * ArticleTag class exists to keep track of the tags, which can be associated with
 * a list of articles. ArticleTag controls the tag row, which ends up being associated
 * with a set of articles. This is opposed to the ArticleTagList class, which
 * focuses on the list of tags associated with an article.
 *
 * These tags are used to test features, control particular article behaviour, etc.
 * The articletag and articletaglinks tables store the relevant tags.
 */

/*db schema:
 *
CREATE TABLE articletag (
	at_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	at_tag VARBINARY(64) NOT NULL,
	at_prob INT UNSIGNED NOT NULL DEFAULT 0,
	UNIQUE KEY(at_tag)
);
CREATE TABLE articletaglinks (
	atl_page_id INT(10) UNSIGNED NOT NULL,
	atl_tag_id INT UNSIGNED NOT NULL,
	UNIQUE KEY(atl_page_id, atl_tag_id),
	KEY(atl_tag_id)
);
 */

class ArticleTag {

	private $tag,
		$row,
		$tagList,
		$newTagProbability;

	public function __construct(string $tag, int $newTagProbability = 0) {
		$this->tag = $tag;
		$this->row = null;
		$this->tagList = [];
		$this->newTagProbability = $newTagProbability; // used when creating a new row
	}

	/**
	 * Fetch the list of articles associated with a tag. The list
	 * is kept in memory after it is fetched once (per request).
	 */
	public function getArticleList() {
		if ($this->tagList) {
			return $this->tagList;
		}

		$dbw = wfGetDB(DB_MASTER);
		$list = [];
		$res = $dbw->select(
			['articletag', 'articletaglinks'],
			['atl_page_id', 'articletag.*'],
			['atl_tag_id = at_id', 'at_tag' => $this->tag],
			__METHOD__);
		foreach ($res as $row) {
			if (!$this->row) {
				$this->row = (array)$row;
			}
			$list[] = (int)$row->atl_page_id;
		}
		$this->tagList = $list;
		return $list;
	}

	private function dbLoadTagRow() {
		if (!$this->row) {
			$dbw = wfGetDB(DB_MASTER);
			$row = $dbw->selectRow('articletag', ['at_id', 'at_prob'], ['at_tag' => $this->tag], __METHOD__);
			if ($row) {
				$this->row = (array)$row;
			}
		}
	}

	private function fetchTagID() {
		$this->dbLoadTagRow();
		return $this->row ? $this->row['at_id'] : 0;
	}

	// Creates a tag row
	private function createTag() {
		$dbw = wfGetDB(DB_MASTER);
		$row = ['at_tag' => $this->tag, 'at_prob' => $this->newTagProbability];
		$dbw->insert('articletag', $row, __METHOD__);
		$tag_id = $dbw->insertId();
		$this->row = $row + ['at_id' => $tag_id];
		return $tag_id;
	}

	// Fetches tag ID and associated info. Creates a new tag if it
	// doesn't already exist.
	private function getTagID() {
		$this->dbLoadTagRow();
		if (!$this->row) {
			$this->createTag();
		}
		return $this->row['at_id'];
	}

	/**
	 * Deletes a tag from memcache, articletag and articletaglinks table.
	 */
	public function deleteTag() {
		$dbw = wfGetDB(DB_MASTER);
		$tag_id = $this->fetchTagID();
		$affected = 0;
		if ($tag_id) {
			// get list before we delete DB records, so we can clear memcache
			$list = $this->getArticleList();

			$dbw->delete('articletaglinks', ['atl_tag_id' => $tag_id], __METHOD__);
			$dbw->delete('articletag', ['at_id' => $tag_id], __METHOD__);
			$affected = $dbw->affectedRows();

			// delete objects from memcache as well
			foreach ($list as $aid) {
				ArticleTagList::clearCache($aid);
			}
		}
		return $affected > 0;
	}

	/**
	 * Get the probability attribute associated with a tag.
	 */
	public function getProbability() {
		$this->dbLoadTagRow();
		if (!$this->row) {
			throw new MWException('articletag row could not be loaded for tag: ' . $this->tag);
		}
		return $this->row['at_prob'];
	}

	/**
	 * Update the probability attribute associated with a tag.
	 */
	public function updateProbability(int $prob) {
		$dbw = wfGetDB(DB_MASTER);
		$tag_id = $this->getTagID();
		if (!$tag_id) {
			throw new MWException('assertion: tag_id must be set by now');
		}
		$res = $dbw->update('articletag', ['at_prob' => $prob], ['at_id' => $tag_id], __METHOD__);
		$this->row['at_prob'] = $prob;
		return $res;
	}

	/**
	 * Modifies the article list associated with a tag. This method adds any missing
	 * articles and removes any deleted articles. It does so incrementally rather than
	 * by deleting the whole list first.
	 *
	 * @return array numbers of articles added and deleted
	 */
	public function modifyTagList($list) {
		global $wgMemc;

		$prevList = $this->getArticleList();

		$newList = [];
		foreach ($list as $item) {
			if (@$item['title'] && $item['title']->exists()) {
				$newList[] = $item['title']->getArticleId();
			}
		}

		$newList = array_unique($newList);
		$prevList = array_unique($prevList);
		$toAdd = array_diff($newList, $prevList);
		$toDelete = array_diff($prevList, $newList);

		if ($toAdd || $toDelete) {
			$tag_id = $this->getTagID(); // creates new tag, if necessary
			if (!$tag_id) {
				throw new MWException('Assertion failed: tag_id must be positive when changing elements in articletaglinks');
			}
		}

		$dbw = wfGetDB(DB_MASTER);

		$added = 0;
		if ($toAdd) {
			$rows = [];
			foreach ($toAdd as $aid) {
				$rows[] = ['atl_tag_id' => $tag_id, 'atl_page_id' => $aid];
			}
			$dbw->insert('articletaglinks', $rows, __METHOD__);
			$added = $dbw->affectedRows();
		}

		$deleted = 0;
		if ($toDelete) {
			foreach ($toDelete as $aid) {
				$conds = ['atl_tag_id' => $tag_id, 'atl_page_id' => $aid];
				$dbw->delete('articletaglinks', $conds, __METHOD__);
				$deleted += $dbw->affectedRows();
			}
		}

		$this->tagList = $newList;

		// Update memcache for the list of article IDs that changed
		foreach ($toAdd + $toDelete as $aid) {
			ArticleTagList::clearCache($aid);
		}

		return [$added, $deleted];
	}

	// Hook called when config storage object is saved to db, if this
	// config storage message is an article list.
	public static function onConfigStorageStoreConfig($key, $pages, $newTagProbability, &$error) {
		$tags = new ArticleTag($key, $newTagProbability);
		$tags->modifyTagList($pages);
		return true;
	}

	// For automated testing
	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, [ __DIR__ . "/tests/" . get_class() . "Test.php" ] );
		return true;
	}
}
