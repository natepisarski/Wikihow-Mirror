<?php

if (!defined('MEDIAWIKI')) die();

/**
 * ArticleTagList class keeps track of the tags associated with an article. To
 * create an object, you specify an article and the list of tags are loaded.
 */

class ArticleTagList {

	private $pageid = 0;

	private static $cache = [];

	/**
	 * Initialize a list of tags using either a title object or a pageid. Defaults
	 * to $wgTitle if input is not specified.
	 */
	public function __construct($title = null) {
		// deal with page ID's passed in
		if ($title && is_int($title)) {
			$this->pageid = (int)$title;
			return $this;
		}

		// default to wgTitle
		if (!$title) {
			global $wgTitle;
			$title = $wgTitle;
		}

		if ($title && $title->exists()) {
			$this->pageid = $title->getArticleId();
		}
		if (!$this->pageid) {
			return null;
		}
	}

	/**
	 * Get the list of tags associated with an article. Caches the list
	 * locally (in the class object) and memcache.
	 */
	public function getTags() {
		global $wgMemc;
		$aid = $this->pageid;

		// class-level static caching so that we don't make multiple trips to memcache
		if (isset(self::$cache[$aid])) {
			return self::$cache[$aid];
		}

		$cachekey = self::getCacheKey($aid);
		$res = $wgMemc->get($cachekey);
		if (is_array($res)) {
			self::$cache[$aid] = $res;
			return $res;
		}

		$tags = [];
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			['articletaglinks', 'articletag'],
			['at_tag', 'at_prob'],
			['atl_tag_id = at_id', 'atl_page_id' => $aid],
			__METHOD__);
		foreach ($res as $row) {
			$prob = $row->at_prob;
			$tags[ $row->at_tag ] = $prob ? ['prob' => $prob] : true;
		}

		$wgMemc->set($cachekey, $tags);
		self::$cache[$aid] = $tags;

		return $tags;
	}

	/**
	 * A static helper method that uses local class cache. This method
	 * checks whether a particular tag is associated with a particular
	 * article ID.
	 */
	public static function hasTag(string $tag, int $aid) {
		$tags = self::getTitleTagsByID($aid);
		return $tags !== false && isset($tags[$tag]);
	}

	// Static method to get list of tags associated with an article ID.
	private static function getTitleTagsByID(int $aid) {
		if ($aid <= 0) {
			return false;
		}
		$list = new self($aid);
		$tags = $list->getTags();
		return $tags;
	}

	// Generate the memcache key for the tag links of an article ID.
	private static function getCacheKey(int $aid) {
		$key = wfMemcKey('atlinks', $aid);
		return $key;
	}

	/**
	 * Clears the memcache of tags for a particular article.
	 */
	public static function clearCache(int $aid) {
		global $wgMemc;
		$cachekey = self::getCacheKey($aid);
		$wgMemc->delete($cachekey);
		unset( self::$cache[$aid] );
	}

/* This method hasn't been tested yet, so I'm commenting it out until someone needs it -Reuben
	public static function getTitleTags(Title $title = null) {
		$aid = 0;
		if (!$title) {
			global $wgTitle;
			$title = $wgTitle;
		}
		if ($title && $title->exists()) {
			$aid = $title->getArticleId();
		}

		return $aid > 0 ? self::getTitleTagsByID($aid) : false;
	}
*/

	// For automated testing
	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, [ __DIR__ . "/tests/" . get_class() . "Test.php" ] );
		return true;
	}
}
