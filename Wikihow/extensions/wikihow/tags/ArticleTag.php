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
	at_translation TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	UNIQUE KEY(at_tag)
);
CREATE TABLE articletaglinks (
	atl_page_id INT(10) UNSIGNED NOT NULL,
	atl_tag_id INT UNSIGNED NOT NULL,
	UNIQUE KEY(atl_page_id, atl_tag_id),
	KEY(atl_tag_id)
);

-- added the column at_translation on April 23, 2020
alter table articletag add column at_translation TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
-- drop this old column at some point after we know it's not used on intl -- done may 6 on all langs
alter table articletag drop column at_prob;
 */

class ArticleTag {

	private $tag,
		$row,
		$tagList,
		$isTranslationTag;

	public function __construct(string $tag, int $isTranslationTag = 0) {
		$this->tag = $tag;
		$this->row = null;
		$this->tagList = [];
		$this->isTranslationTag = $isTranslationTag; // used when creating a new row
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

	public function isTranslationTag() {
		$this->dbLoadTagRow();
		return $this->row ? $this->isTranslationTag : -1;
	}

	private function dbLoadTagRow() {
		if (!$this->row) {
			$dbw = wfGetDB(DB_MASTER);
			$row = $dbw->selectRow('articletag', ['at_id', 'at_translation'], ['at_tag' => $this->tag], __METHOD__);
			if ($row) {
				$this->row = (array)$row;
				$this->isTranslationTag = $row->at_translation;
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
		$row = ['at_tag' => $this->tag, 'at_translation' => $this->isTranslationTag];
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

	/**
	 * Get a list of tags which are translation tags. This is used in a maintenance script to
	 * refresh them nightly.
	 *
	 * NOTE: this method isn't considered efficient. Be careful where you use it.
	 */
	public static function listEnglishTranslationTags() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(WH_DATABASE_NAME_EN . '.articletag', ['at_tag'], ['at_translation' => 1], __METHOD__);
		$tags = [];
		foreach ($res as $row) {
			$tags[] = $row->at_tag;
		}
		return $tags;
	}

	// Hook called when config storage object is saved to db, if this
	// config storage message is an article list.
	public static function onConfigStorageStoreConfig($key, $pages, $isTranslationTag, &$error) {
		$tags = new ArticleTag($key, $isTranslationTag);
		$tags->modifyTagList($pages);
		return true;
	}

	public static function onBeforePageDisplayAddArticleTagJSVars( $out, $skin ) {
		$jsFastRender = 0;
		if ( Misc::isFastRenderTest() ) {
			$jsFastRender = 1;
		}
		$nativeBrowserLazyLoadingScript =  "window.WH.jsFastRender=$jsFastRender;";
		$out->addHeadItem( 'js_fast_render',  HTML::inlineScript( $nativeBrowserLazyLoadingScript ) );
	}

	// For automated testing
	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, [ __DIR__ . "/tests/" . get_class() . "Test.php" ] );
		return true;
	}

	// Load the pages associated with the English tag first join article_tag to translation_link in
	// the english database
	private static function dbLoadEnglishTagTranslations($langCode, $tag) {
		$dbr = wfGetDB(DB_REPLICA);

		// This query fetches the tag name from articletag table, joining to
		// articletaglinks to get the list of English page IDs, which is joined
		// to translation_link to get a list of the translation links in our current
		// language.
		$res = $dbr->select(
			[ WH_DATABASE_NAME_EN . '.articletag',
				WH_DATABASE_NAME_EN . '.articletaglinks',
				WH_DATABASE_NAME_EN . '.translation_link' ],
			[ 'atl_page_id', 'tl_to_aid' ],
			[   'at_tag' => $tag,
				'tl_from_lang' => 'en',
				'tl_to_lang' => $langCode,
				'at_id = atl_tag_id',
				'atl_page_id = tl_from_aid',
				'tl_translated' => 1,
				'at_translation' => 1 ],
			__METHOD__ );

		$pageids = [];
		foreach ($res as $row) {
			if ( (int)$row->tl_to_aid > 0 ) {
				$pageids[] = (int)$row->tl_to_aid;
			}
		}

		return $pageids;
	}

	// NOTE: this method doesn't check that the target pages in our language definitely exist. It
	// assumes the titus TL data is correct. (But it might not always be, since articles
	// can be deleted in the last 24h etc, as an example of a way the data might not be perfect.)
	public static function rewriteTranslationTag($tag) {
		$langCode = RequestContext::getMain()->getLanguage()->getCode();
		if ($langCode == 'en') {
			die('This method CANNOT be run English. Read comments in here to see why: ' . __METHOD__);
		}

		// pull all the translations that exist for our current language
		$pages = self::dbLoadEnglishTagTranslations($langCode, $tag);

		// generate the $config message from the array of page IDs
		sort($pages, SORT_NUMERIC);
		$config = implode("\n", $pages) . "\n";

		// refresh the list of articles for a given tag on current language
		$error = '';
		ConfigStorage::dbStoreConfig($tag,
			$config,
			true /* $isArticleList */,
			$error,
			true /* $allowArticleErrors */,
			1 /* $isTranslationTag */,
			ConfigStorage::LOG_IT_IF_CHANGED);
	}
}
