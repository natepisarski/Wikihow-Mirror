<?php

/** db schema:
CREATE TABLE good_revision (
  gr_page INT(8) UNSIGNED NOT NULL,
  gr_rev INT(8) UNSIGNED NOT NULL,
  gr_updated TIMESTAMP default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY(gr_page)
);
*/

// callbacks that need to be set up for this feature to work
$wgHooks['ArticleFromTitle'][] = array('GoodRevision::onArticleFromTitle');
$wgHooks['MarkPatrolledDB'][] = array('GoodRevision::onMarkPatrolled');
$wgHooks['EditURLOptions'][] = array('GoodRevision::onEditURLOptions');
$wgHooks['Unpatrol'][] = array('GoodRevision::onUnpatrol');
$wgHooks['TitleMoveComplete'][] = array('GoodRevision::onMovePage');
$wgHooks['UnitTestsList'][] = array('GoodRevision::onUnitTestsList');

class GoodRevision {

	var $title,
		$cachekey,
		$articleID;

	static $usedRev = array();

	/**
	 * Factory method to instantiate an object that determines the latest
	 * good revision for an article.
	 *
	 * Note: uses $articleID if it's provided, for efficiency.
	 */
	public static function newFromTitle(&$title, $articleID = 0) {

		if ( !$title
			|| !$title->exists()
			|| !$title->inNamespace(NS_MAIN))
		{
			return null;
		}

		return new GoodRevision($title, $articleID);
	}

	private function __construct(&$title, $articleID) {
		$this->title = $title;
		$this->articleID = $articleID ? $articleID : $title->getArticleID();
		$this->cachekey = wfMemcKey('goodrev', $this->articleID);
	}

	/**
	 * Look up the latest good revision for an article.
	 */
	public function latestGood() {
		global $wgMemc;

		$res = $wgMemc->get($this->cachekey);
		if (!$res) {
			$dbr = self::getDB();
			$res = $dbr->selectField('good_revision', 'gr_rev',
				array('gr_page' => $this->articleID),
				__METHOD__);
			if ($res) {
				$wgMemc->set($this->cachekey, $res);
			}
		}
		return (int)$res;
	}

	/**
	 * Event called when a recent change is patrolled in Special:Recentchanges,
	 * Special:RCPatrol or auto-patrolled
	 */
	public static function onMarkPatrolled($rcid, &$article) {
		$title = null;
		if ($article) {
			$title = $article->getTitle();
		}
		if ($title && $rcid) {
			$goodRev = self::newFromTitle($title);
			if ($goodRev) {
				$rev = self::getRevFromRC($goodRev->articleID, $rcid);
				$goodRev->updateRev($rev);
				// Refresh the article meta info once a good revision is updated
				ArticleMetaInfo::refreshMetaDataCallback($article);
				Hooks::run( 'AfterGoodRevisionUpdated', array( $title, $goodRev ) );
				$title->purgeSquid();
			}
		}
		return true;
	}

	/**
	 * Update good revision for a page ID.
	 *
	 * Returns true if an update occurred, false otherwise
	 *
	 * Note: sometimes the latest revision patrolled isn't the
	 *   numerically highest revision patrolled. In this case,
	 *   set $forceUpdate to true.
	 */
	public function updateRev($rev, $forceUpdate = false) {
		global $wgMemc;
		if ($rev) {
			$latest = $this->latestGood();
			if ($forceUpdate && $rev != $latest
				|| !$forceUpdate && $rev > $latest)
			{
				$dbw = self::getDB('write');
				$sql = 'REPLACE INTO good_revision
						SET gr_page=' . $dbw->addQuotes($this->articleID) . ',
							gr_rev=' . $dbw->addQuotes($rev);

				$dbw->query($sql, __METHOD__);
				$wgMemc->set($this->cachekey, $rev);

				Hooks::run( 'GoodRevisionUpdated', array( $this->articleID, $rev ) );

				return true;
			}
		}
		return false;
	}

	/**
	 * Turn a RecentChange id into a revision ID.
	 * Note: if the requested revision is a rolled back revision, don't return it.
	 */
	public static function getRevFromRC($pageid, $rcid) {
		$rc = RecentChange::newFromId($rcid, true);
		if ($rc) {
			// Check if there was a rollback on any of the more
			// recent changes to the article. If there was a
			// rollback, just return 0 so that it looks to any
			// calling function like there is no associated
			// revision ID to assign.
			$rollbackCommentPrefix = wfMessage('rollback_comment_prefix')->inContentLanguage()->text();
			$dbr = self::getDB('read');
			$res = $dbr->select('recentchanges', array('rc_comment'),
				array('rc_cur_id' => $pageid, 'rc_id > ' . $rcid),
				__METHOD__);
			foreach ($res as $row) {
				$isRollback = strpos($row->rc_comment, $rollbackCommentPrefix) === 0;
				if ($isRollback) return 0;
			}
			return $rc->getAttribute('rc_this_oldid');
		} else {
			return 0;
		}
	}

	/**
	 * Grab the last good patrol
	 * - return true if the last edit on the article was patrolled
	 */
	public static function patrolledGood($title) {
		// start with basic check to make sure we're dealing
		// with a real article
		if (!$title) return false;

		// get the last revision
		$a = new Article($title);
		$last_rev_id = $a->getRevIdFetched();

		// get the last good revision
		$goodRev = self::newFromTitle($title);
		if (!$goodRev) {
			$last_good_rev = 0;
		} else {
			$last_good_rev = $goodRev->latestGood();
		}

		return $last_rev_id == $last_good_rev;
	}

	/**
	 * Create a new article object specifically from the last good
	 * revision (instead of the latest revision).
	 */
	public static function newArticleFromLatest($title) {
		$goodRev = self::newFromTitle($title);
		$last_good_rev = $goodRev ? $goodRev->latestGood() : 0;
		$article = null;
		if ($last_good_rev) {
			$article = new Article($title, $last_good_rev);
		}
		if (!$article) {
			$article = new Article($title);
		}
		return $article;
	}

	/**
	 * Check if there are any cookies that start with "wiki_shared".  If
	 * there are, we don't consider the user anonymous.
	 */
	private static function isAnonymous() {
		global $wgCookiePrefix, $wgRequest;
		// for testing
		if ($wgRequest->getVal('anon', 'no') != 'no') {
			return true;
		}
		foreach ($_COOKIE as $name => $val) {
			if (strpos($name, $wgCookiePrefix) === 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Event called when an article is about to be fetched from the database
	 */
	public static function onArticleFromTitle(&$title, &$article) {
		global $wgRequest;

		// check if user is anonymous / uncookied, if not, show current rev
		$isAnon = self::isAnonymous();
		if (!$isAnon) {
			return true;
		}

		// if "oldid" is a URL param, we don't want to override the displayed
		// revision.
		if (@$wgRequest) {
			if ($wgRequest->getVal('oldid')) {
				return true;
			}
			$action = $wgRequest->getVal('action');
			if ($action != '' && $action != 'view')
			{
				return true;
			}
		}

		// fetch correct revision, if there is one
		$goodRev = self::newFromTitle($title);
		if ($goodRev) {
			$revid = $goodRev->latestGood();
		} else {
			$revid = 0;
		}

		// if there's a last good revision for the article, use it
		if ($revid) {
			$pageid = $goodRev->articleID;
			self::$usedRev[$pageid] = $revid;
			$article = new Article($title, $revid);
		}
		return true;
	}

	/**
	 * Grab info on which older revisions have been used
	 */
	public static function getUsedRev($pageid) {
		return @self::$usedRev[$pageid];
	}

	/**
	 * Callback for edit URL options
	 */
	public static function onEditURLOptions($context, &$useDefault) {
		if ($context) {
			$title = $context->getTitle();
			if ($title && $title->exists()) {
				$usedRev = self::getUsedRev( $title->getArticleId() );
				$useDefault = !empty($usedRev);
			}
		}
		return true;
	}

	/**
	 * Callback when a list of revisions are unpatrolled
	 */
	public static function onUnpatrol(&$oldids) {
		if ($oldids) {
			self::dbDeleteIDs($oldids);
		}
		return true;
	}

	/**
	 * Callback when a page title is moved (changed)
	 */
	public static function onMovePage(&$oldTitle, &$newTitle) {
		$oldids = array();
		if ($oldTitle && $oldTitle->inNamespace(NS_MAIN)) $oldids[] = $oldTitle->getArticleID();
		if ($newTitle && $newTitle->inNamespace(NS_MAIN)) $oldids[] = $newTitle->getArticleID();
		if ($oldids) {
			self::dbDeleteIDs($oldids);
		}
		return true;
	}

	// delete article IDs from the good_revision table, which resets
	// the good_rev info to the head revision
	private static function dbDeleteIDs($ids) {
		$dbw = self::getDB('write');
		$sql = 'DELETE FROM good_revision
				WHERE gr_rev IN (' . $dbw->makeList($ids) . ')';
		$dbw->query($sql, __METHOD__);
	}

	/**
	 * Get a DB handle
	 */
	private static function getDB($type = 'read') {
		static $dbr = null, $dbw = null;
		if ($type != 'read') {
			if (!$dbw) $dbw = wfGetDB(DB_MASTER);
			return $dbw;
		} else {
			if (!$dbr) $dbr = wfGetDB(DB_REPLICA);
			return $dbr;
		}
	}

	public static function onUnitTestsList( &$files ) {
		global $IP;
		$files = array_merge( $files, glob( __DIR__ . '/tests/*Test.php' ) );
		return true;
	}
}
