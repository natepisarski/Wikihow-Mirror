<?php

/*
TODO (Added by Alberto on Nov 9, 2016):

There are "index_info.ii_page" values which are missing from the "page" table:
select count(*) from index_info where ii_page not in (select page_id from page);

One reason for this has to do with pages being moved. We could create a hook that removes
expired entries from the "index_info" table.
 */

$wgHooks['BeforePageDisplay'][] = ['RobotPolicy::setRobotPolicy'];
// need to change to save complete so that new articles get proccessed correctly
$wgHooks['PageContentSaveComplete'][] = ['RobotPolicy::recalcArticlePolicy'];
// no need to do demote since an edit happens with that
$wgHooks['NABMarkPatrolled'][] = ['RobotPolicy::recalcArticlePolicyBasedOnId'];
$wgHooks['ArticleDelete'][] = ['RobotPolicy::onArticleDelete'];
$wgHooks['TitleMoveComplete'][] = ['RobotPolicy::onTitleMoveComplete'];
// Category pages
$wgHooks['CategoryAfterPageRemoved'][] = ['RobotPolicy::recalcCategoryPolicy'];

class RobotPolicy {

	const POLICY_INDEX_FOLLOW = 1;
	const POLICY_NOINDEX_FOLLOW = 2;
	const POLICY_NOINDEX_NOFOLLOW = 3;
	const POLICY_DONT_CHANGE = 4;

	const POLICY_INDEX_FOLLOW_STR = 'index,follow';
	const POLICY_NOINDEX_FOLLOW_STR = 'noindex,follow';
	const POLICY_NOINDEX_NOFOLLOW_STR = 'noindex,nofollow';

	const TABLE_NAME = "index_info";

	/**
	 * IMPORTANT: use lowercase when adding page names and language codes
	 * 'lang' can be: '*', 'intl', or 'en|fr|de|ko|...'
	 */
	private static $overrides = [
		NS_MAIN => [],
		NS_CATEGORY => [
			'wikihow'           => [ 'lang' => 'en', 'policy' => self::POLICY_NOINDEX_FOLLOW ],
			'featured articles' => [ 'lang' => 'en', 'policy' => self::POLICY_INDEX_FOLLOW ],
		],
		NS_SPECIAL => [
			'profilebadges'     => [ 'lang' => 'intl', 'policy' => self::POLICY_NOINDEX_NOFOLLOW ],
		]
	];

	var $title, $wikiPage, $request;

	private function __construct($title, $wikiPage, $request = null) {
		$this->title = $title;
		$this->wikiPage = $wikiPage;
		$this->request = $request;
	}

	public static function setRobotPolicy($out) {
		$context = $out ? $out->getContext() : null;
		if ($context) {
			$title = $context->getTitle();
			$robotPolicy = self::newFromTitle($title, $context);
			list($policy, $policyText) = $robotPolicy->genRobotPolicyLong();

			switch ($policy) {
			case self::POLICY_NOINDEX_FOLLOW:
				$out->setRobotPolicyCustom(self::POLICY_NOINDEX_FOLLOW_STR, $policyText);
				break;
			case self::POLICY_NOINDEX_NOFOLLOW:
				$out->setRobotPolicyCustom(self::POLICY_NOINDEX_NOFOLLOW_STR, $policyText);
				break;
			case self::POLICY_INDEX_FOLLOW:
				$out->setRobotPolicyCustom(self::POLICY_INDEX_FOLLOW_STR, $policyText);
				break;
			case self::POLICY_DONT_CHANGE:
				$oldPolicy = $out->getRobotPolicy();
				$out->setRobotPolicyCustom($oldPolicy, $policyText);
				break;
			}
		}
		return true;
	}

	private static function newFromTitle($title, $context = null) {
		if (!$title) {
			return null;
		} elseif ($context) {
			$wikiPage = $title->exists() ? $context->getWikiPage() : null;
			return new RobotPolicy($title, $wikiPage, $context->getRequest());
		} else {
			$wikiPage = $title->exists() ? WikiPage::factory($title) : null;
			return new RobotPolicy($title, $wikiPage);
		}
	}

	public static function isIndexablePolicy(int $policy): bool {
		$indexablePolicies = [self::POLICY_INDEX_FOLLOW, self::POLICY_DONT_CHANGE];
		return in_array($policy, $indexablePolicies);
	}

	/**
	 * Determine whether current page view is indexable. This is based on the
	 * status of the article content (for example: whether it has a stub tag),
	 * and on the request parameters.
	 */
	public static function isIndexable($title, $context = null) {
		$policy = self::newFromTitle($title, $context);
		if ($policy && self::isIndexablePolicy($policy->genRobotPolicy())) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Find out if the title itself is indexable. This is different from the
	 * isIndexable method in that it does not consider factors related to the
	 * request itself, such as the printable or variant cgi params.
	 *
	 * Note: this method is a modified version of the isIndexable() method above.
	 */
	public static function isTitleIndexable($title, $context = null) {
		$robotPolicy = self::newFromTitle($title, $context);
		if ($robotPolicy) {
			list($policyNumber, $policyText) =  $robotPolicy->getRobotPolicyBasedOnTitle();
			if (self::isIndexablePolicy($policyNumber)) {
				return true;
			}
		}
		return false;
	}

	public static function getTitusPolicy(&$title) {
		$policyString = "";
		$timestamp = "";
		if ($title) {
			$dbr = wfGetDB(DB_REPLICA);
			$row = $dbr->selectRow(
				RobotPolicy::TABLE_NAME,
				['ii_policy', 'ii_timestamp'],
				['ii_namespace' => $title->getNamespace(), 'ii_page' => $title->getArticleID()]
			);

			if ($row) {
				$policy = $row->ii_policy;
				$timestamp = $row->ii_timestamp;
				switch ($policy) {
					case self::POLICY_NOINDEX_FOLLOW:
						$policyString = self::POLICY_NOINDEX_FOLLOW_STR;
						break;
					case self::POLICY_NOINDEX_NOFOLLOW:
						$policyString = self::POLICY_NOINDEX_NOFOLLOW_STR;
						break;
					case self::POLICY_INDEX_FOLLOW:
						$policyString = self::POLICY_INDEX_FOLLOW_STR;
						break;
					case self::POLICY_DONT_CHANGE:
						$policyString = self::POLICY_INDEX_FOLLOW_STR;
						break;
				}
			}
		}

		return array($policyString, $timestamp);
	}

	public function genRobotPolicyLong() {
		// First, we compute any indexation that isn't based based on
		// article ID but on request details or non-existence of article.
		// Note: these are generally "cheap" checks in terms of resources
		// and time to compute.
		if ($this->isPrintable()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'isPrintable';
		} elseif ($this->isVariant()) {
			$policy = self::POLICY_NOINDEX_FOLLOW;
			$policyText = 'isVariant';
		} elseif ($this->isNoRedirect()) {
			$policy = self::POLICY_NOINDEX_FOLLOW;
			$policyText = 'isNoRedirect';
		} elseif ($this->isOriginCDN()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'isOriginCDN';
		} elseif ($this->hasOldidParam()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'hasOldidParam';
		} elseif ($this->isNotViewAction()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'notViewAction';
		} elseif ($this->isNonExistentPage()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'isNonExistentPage';
		} else {
			// Note: we do not need to check if $this->title exists after this
			// point. The reason is that isNonExistentPage() has been called.
			list($policy, $policyText) = $this->getRobotPolicyBasedOnTitle();
		}

		wfDebugLog(
			'hypothesis',
			">> GET_ROBOT_POLICY " . var_export( [
				'policy' => $policy,
				'policyText' => $policyText
			], true ) . "\n"
		);

		return array($policy, $policyText);
	}

	public function getRobotPolicyBasedOnTitle() {
		$cache = wfGetCache(CACHE_MEMSTATIC);
		$cachekey = self::getCacheKey($this->title);
		$res = $cache->get($cachekey);

		if (is_array($res)) {
			$policy = $res['policy'];
			$policyText = $res['text'] . '_cached';
			return array($policy, $policyText);
		}

		$pageId = $this->title->getArticleID();
		if ( $pageId <= 0 || !$this->title->inNamespaces(NS_MAIN, NS_CATEGORY) ) {
			// Not an article or category page, so index info is not stored in the DB
			list($policy, $policyText) = $this->generateRobotPolicyBasedOnTitle();
		} else {
			$dbr = wfGetDB(DB_REPLICA);
			$row = $dbr->selectRow(
				RobotPolicy::TABLE_NAME,
				['ii_policy', 'ii_reason'],
				['ii_namespace' => $this->title->getNamespace(), 'ii_page' => $pageId]
			);
			if ($row) {
				$policy = $row->ii_policy;
				$policyText = $row->ii_reason;
			} else {
				// we shouldn't ever need this, but just in case
				list($policy, $policyText) = $this->generateRobotPolicyBasedOnTitle();
				// now let's save it
				self::savePolicyInDB($this->title, $policy, $policyText);
			}
		}

		$res = array('policy' => $policy, 'text' => $policyText);
		$cache->set($cachekey, $res);

		return array($policy, $policyText);
	}

	public function generateRobotPolicyBasedOnTitle() {
		$policyText = '';
		$policy = $this->getPolicyOverride();

		if ($policy >= 0) {
			$policyText = 'isOverride';
		} elseif ($this->isNonExistentPage()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'isNonExistentPage';
		} elseif ($this->inWhitelist()) {
			$policy = self::POLICY_INDEX_FOLLOW;
			$policyText = 'inWhitelist';
		} elseif ($this->hasUserPageRestrictions()) {
			$policy = self::POLICY_NOINDEX_FOLLOW;
			$policyText = 'hasUserPageRestrictions';
		} elseif ($this->hasBadTemplate()) {
			$policy = self::POLICY_NOINDEX_FOLLOW;
			$policyText = 'hasBadTemplate';
		} elseif ($this->isUnNABbedArticle()) {
			$policy = self::POLICY_NOINDEX_FOLLOW;
			$policyText = 'isUnNABbedArticle';
		} elseif ($this->isBlacklistPage()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'isBlacklistPage';
		} elseif ($this->isCategoryInTree()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'isNotInTree';
		} elseif ($this->isEmptyCategory()) {
			$policy = self::POLICY_NOINDEX_NOFOLLOW;
			$policyText = 'isEmptyCategory';
		}

		// Lastly, if indexation status is not already decided, we
		// use the default indexation based on namespace.
		if ($policy < 0) {
			$policy = $this->getDefaultNamespaceIndexation();
			if ($policy == self::POLICY_DONT_CHANGE) {
				$policyText = 'default';
			} else {
				$policyText = 'namespaceDefault';
			}
		}
		return array($policy, $policyText);
	}

	public function genRobotPolicy() {
		list($policy, $policyText) = $this->genRobotPolicyLong();
		return $policy;
	}

	/**
	 * Get (and cache) the database handle.
	 */
	private static function getDB() {
		static $dbr = null;
		if (!$dbr) $dbr = wfGetDB(DB_REPLICA);
		return $dbr;
	}

	/**
	 * Mediawiki has $wgNamespaceRobotPolicies to set indexation based on
	 * namespace, but we've found that we want more finer grained control and
	 * centralized code to reduce bugs.
	 */
	private function getDefaultNamespaceIndexation() {
		global $wgLanguageCode;

		// Make it so that most namespace pages aren't indexed by Google
		$noindexNamespaces = array(
			NS_TALK, NS_USER_TALK, NS_PROJECT, NS_PROJECT_TALK, NS_IMAGE_TALK,
			NS_MEDIAWIKI, NS_MEDIAWIKI_TALK, NS_TEMPLATE, NS_TEMPLATE_TALK,
			NS_CATEGORY_TALK, NS_ARTICLE_REQUEST, NS_ARTICLE_REQUEST_TALK,
			NS_USER_KUDOS, NS_USER_KUDOS_TALK,
			NS_VIDEO, NS_VIDEO_TALK, NS_VIDEO_COMMENTS, NS_VIDEO_COMMENTS_TALK,
			NS_SUMMARY, NS_SUMMARY_TALK, NS_HELP_TALK, NS_HELP
		);
		if (defined('NS_MODULE')) {
			$noindexNamespaces[] = NS_MODULE;
			$noindexNamespaces[] = NS_MODULE_TALK;
		}

		// We put this check in place after a disaster struck with a rollout of a
		// seemingly unrelated project. We found out that Mediawiki returns true
		// when doing this:
		//   Title::newFromText('Kiss')->inNamespace('RANDOMSTRING')
		// which is kind of crazy, but it's a Mediawiki bug that we have to work
		// around. It's really dangerous in particular since PHP turns undefined
		// defines (such as any undefined namespaces in the $noindexNamespaces
		// array above) into strings, which Title::inNamespaces() takes to be
		// the same as NS_MAIN (0). Crazy bug!
		foreach ($noindexNamespaces as $ns) {
			if (!is_int($ns)) {
				throw new MWException('RobotsPolicy bug detected! See comment in ' . __FILE__ . ' just above line ' . __LINE__);
			}
		}

		$inNoindexNamespace = $this->title &&
			$this->title->inNamespaces( $noindexNamespaces );

		$inImageNamespace = $this->title && $this->title->inNamespace(NS_IMAGE);

		if ($inImageNamespace) {
			return self::POLICY_NOINDEX_FOLLOW;
		} elseif ($inNoindexNamespace && $wgLanguageCode == 'en') {
			return self::POLICY_NOINDEX_FOLLOW;
		} elseif ($inNoindexNamespace && $wgLanguageCode != 'en') {
			return self::POLICY_NOINDEX_NOFOLLOW;
		} else {
			return self::POLICY_DONT_CHANGE;
		}
	}

	/**
	 * Test whether page is being displayed in "printable" form
	 */
	private function isPrintable() {
		$isPrintable = $this->request && $this->request->getVal('printable', '') == 'yes';
		return $isPrintable;
	}

	/**
	 * Test whether page is being displayed as a "variant" -- this is particularly
	 * relevant for ZH, but possibly for other languages that have display variant.
	 *
	 * Reuben note: we tried for months to make it so that Google wouldn't treat
	 * these variant pages as separate pages in the index, by setting a proper
	 * "meta canonical" tag in the <head> section to point back to the zh-hans
	 * article, but Google is stubborn, so making these noindex seems like our best
	 * option to get them out of the index. It feels like Google should be picking
	 * the best Chinese variant of the article to show based on what user is viewing
	 * the article, but we haven't seen evidence that this is happening.
	 */
	private function isVariant() {
		$isVariant = $this->request && $this->request->getVal('variant', '');
		return $isVariant;
	}

	/**
	 * Check whether the ?redirect=no url param is present
	 */
	private function isNoRedirect() {
		$isNoRedirect = $this->request && $this->request->getVal('redirect') == 'no';
		return $isNoRedirect;
	}

	/**
	 * Check whether the origin of the request is the CDN
	 */
	private function isOriginCDN() {
		global $wgIsDevServer;
		if ($wgIsDevServer) {
			$isCDNRequest = false;
		} else {
			$isCDNRequest = preg_match('@^https?://pad@', @$_SERVER['HTTP_X_INITIAL_URL']) > 0;
		}
		return $isCDNRequest;
	}

	/**
	 * Check whether the URL has an &oldid=... param
	 */
	private function hasOldidParam() {
		// return $this->request && (boolean)$this->request->getVal('oldid');
		return (
			// Has a request object
			$this->request &&
			// Oldid was passed
			$this->request->getCheck( 'oldid' ) &&
			// Not a Hypothesis response
			!$this->request->getCheck( 'hyp-opti-project' )
		);
	}

	/**
	 * Check whether this is not the default action ("view")
	 */
	private function isNotViewAction() {
		return $this->title && $this->request
			&& $this->title->exists()
			&& $this->request->getVal('action', 'view') != 'view';
	}

	/**
	 * Check whether page exists in DB or not
	 */
	private function isNonExistentPage() {
		if (!$this->title ||
			($this->title->getArticleID() == 0
			 && ! $this->title->inNamespace(NS_SPECIAL))
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if this is a user page that should not be indexed
	 */
	private function hasUserPageRestrictions() {
		global $wgLanguageCode;

		if ($this->title->inNamespace(NS_USER)) {

			// The vast majority of user pages are noindex, unless we whitelist them

			$aid = $this->title->getArticleID();
			$isWhitelisted = ArticleTagList::hasTag('UserPageWhitelist', $aid);
			if (!$isWhitelisted) {
				return true;
			}

			// These rules are older, but we keep them as a backstop

			if (($this->userNumEdits() < 500 && !$this->isGPlusAuthor())
				|| strpos($this->title->getText(), '/') !== false
				|| $wgLanguageCode != 'en'
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieve number of edits by a user
	 */
	private function userNumEdits() {
		$u = explode("/", $this->title->getText());
		return WikihowUser::getAuthorStats($u[0]);
	}

	/**
	 * Check for G+ authorship
	 */
	private function isGPlusAuthor() {
		$u = explode("/", $this->title->getText());
		return WikihowUser::isGPlusAuthor($u[0]);
	}


	/**
	 * Check to see whether certain templates are affixed to the article.
	 */
	private function hasBadTemplate() {
		$result = 0;
		$tables = 'templatelinks';
		$fields = 'tl_title';
		$where = [
			'tl_from' => $this->title->getArticleID(),
			'tl_title' => ['Speedy', 'Stub', 'Copyvio','Copyviobot','Copyedit','Cleanup','Notifiedcopyviobot','CopyvioNotified','Notifiedcopyvio','Format','Nfd','Inuse']
		];
		$res = self::getDB()->select($tables, $fields, $where);

		$templates = array();
		foreach ($res as $row) {
			$templates[ $row->tl_title ] = true;
		}
		// Checks to see if an article has the nfd template AND has less
		// than 10,000 page views. If so, it is de-indexed.
		if (@$templates['Nfd']) {
			if ($this->wikiPage->getCount() < 10000) return true;
			unset( $templates['Nfd'] );
		}
		// Checks to see if the article is "In use" AND has little or no content.
		// If so, it is de-indexed.
		if (@$templates['Inuse']) {
			if ($this->title->getLength() < 1500) return true;
			unset( $templates['Inuse'] );
		}
		return count($templates) > 0;
	}

	/**
	 * Check whether the article is yet to be nabbed and is short in length.
	 * Use byte size as a proxy for length for better performance.
	 */
	private function isUnNABbedArticle() {
		$ret = false;
		if ($this->wikiPage
			&& $this->title->inNamespace(NS_MAIN)
			&& class_exists('NewArticleBoost')
			&& !NewArticleBoost::isNABbed( self::getDB(), $this->title->getArticleID() )
		) {
			$ret = true;
		}
		return $ret;
	}

	/**
	 * Use a white list to include results that should be indexed regardless of
	 * their namespace.
	 */
	private function inWhitelist() {
		static $whitelist = null;
		if (!$whitelist) $whitelist = wfMessage('index-whitelist')->text();
		$urls = explode("\n", $whitelist);
		foreach ($urls as $url) {
			$url = trim($url);
			if ($url) {
				$whiteTitle = Title::newFromURL($url);
				if ($whiteTitle && $whiteTitle->getPrefixedURL() == $this->title->getPrefixedURL()) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * We want to noindex,nofollow the Spam-Blacklist.
	 */
	private function isBlacklistPage() {
		$blacklist = array(
			'Sandbox',
			'Spam-Blacklist'
		);
		$titleKey = $this->title->getDBkey();
		$blacklisted = in_array( $titleKey, $blacklist )
			|| strpos($titleKey, 'Sandbox/') === 0;
		return $blacklisted;
	}

	/**
	 * Whether a category is included in the category tree (/wikiHow:Categories)
	 */
	public function isCategoryInTree(): bool
	{
		if ( !$this->title->inNamespace(NS_CATEGORY) ) {
			return false;
		}

		$txt = $this->title->getText();
		$categs = CategoryHelper::getIndexableCategoriesFromTree();

		return !isset($categs[$txt]);
	}

	/**
	 * Returns TRUE if there are no indexable articles in the category
	 */
	private function isEmptyCategory(): bool {
		if (!$this->title || !$this->title->inNamespace(NS_CATEGORY)) {
			return false;
		}
		// Count indexable articles in the category
		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['page', 'categorylinks', 'index_info'];
		$fields = 'count(*)';
		$where = [
			'cl_from = page_id',
			'ii_page = page_id',
			'cl_to' => $this->title->getDBKey(),
			'page_namespace != 14',
			'ii_policy IN (1, 4)'
		];
		$count = (int) $dbr->selectField($tables, $fields, $where);
		return $count === 0;
	}

	private function getPolicyOverride(): int {
		global $wgLanguageCode;
		$policy = -1;

		$ns = $this->title->getNamespace();
		$txt = strtolower($this->title->getText());
		$cnf = self::$overrides[$ns][$txt] ?? null;
		if ($cnf) {
			$lang = $cnf['lang'];
			if ( $lang == '*' || $wgLanguageCode == $lang || ($lang == 'intl' && Misc::isIntl()) ) {
				$policy = $cnf['policy'];
			}
		}

		return $policy;
	}

	/**
	 * This function clears memcache for the given article each time
	 * the article is saved. Each time a new memc key is created for
	 * a new rule, it will need to be added to this function.
	 *
	 * It is used when a page cache is purged (url with action=purge)
	 * and in NAB. When the indexation status of a page changes
	 * without the page being edited (for example, when you add it to
	 * an indexation whitelist or blacklist), clearing memcache
	 * allows non-main namespace pages to have their indexation
	 * status recalculated.
	 */
	public static function clearArticleMemc(&$article) {
		if ($article) {
			$title = $article->getTitle();
			self::clearArticleMemcByTitle($title);
		}

		return true;
	}

	private static function clearArticleMemcByTitle($title) {
		if ($title) {
			$cache = wfGetCache(CACHE_MEMSTATIC);

			// Clear the relevant key
			$cachekey = self::getCacheKey($title);
			$cache->delete($cachekey);
		}
	}

	public static function recalcArticlePolicyBasedOnId($aid, bool $dry=false): int {
		$title = Title::newFromID($aid);

		return self::recalcArticlePolicyBasedOnTitle($title, $dry);
	}

	/**
	 * @param  int  $aid Article ID
	 * @param  bool $dry Dry run: calculate but don't write to DB nor cache
	 */
	public static function recalcArticlePolicyBasedOnTitle(&$title, bool $dry=false): int {
		$cache = wfGetCache(CACHE_MEMSTATIC);
		if (!$title || !$title->exists() || !$title->inNamespaces(NS_MAIN, NS_CATEGORY)) {
			// Not an article or category page, so index info is not stored in the DB
			return 0;
		}

		$cachekey = self::getCacheKey($title);

		$robotPolicy = RobotPolicy::newFromTitle($title);

		list($policy, $policyText) = $robotPolicy->generateRobotPolicyBasedOnTitle();

		if (!$dry) {
			self::savePolicyInDB($title, $policy, $policyText);
			$cache->set($cachekey, ['policy'=>$policy, 'text'=>$policyText]);
		}

		// Recalculate the policies of the categories to which the article belongs
		if ( !$dry && $title->exists() && $title->inNamespace(NS_MAIN) ) {
			$categories = WikiPage::newFromID($title->getArticleID())->getCategories();
			foreach ($categories as $category) {
				self::recalcArticlePolicyBasedOnTitle($category);
			}
		}

		return $policy;
	}

	// Used as hook on page save complete
	public static function recalcArticlePolicy(&$article) {
		if ($article) {
			$title = $article->getTitle();
			self::recalcArticlePolicyBasedOnTitle($title);
		}
	}

	// Used as hook when a page is added/removed to category
	public static function recalcCategoryPolicy($category, $wikiPage) {
		if ($category) {
			self::recalcArticlePolicyBasedOnTitle($category->getTitle());
		}
	}

	private static function savePolicyInDB($title, $policy, $policyText) {
		$dbw = wfGetDB(DB_MASTER);
		$values = [
			'ii_page' => $title->getArticleID(),
			'ii_policy' => $policy,
			'ii_reason' => $policyText,
			'ii_timestamp' => wfTimestamp(TS_MW),
			'ii_revision' => $title->getLatestRevID(),
			'ii_namespace' => $title->getNamespace()
		];
		$dbw->upsert(RobotPolicy::TABLE_NAME, $values, [], $values);
	}

	/**
	 * Generate memcache key consistently
	 */
	private static function getCacheKey($title) {
		return wfMemckey('indexstatus', md5($title->getPrefixedDBkey()) );
	}

	public static function onTitleMoveComplete($oldTitle, $newTitle) {
		self::clearArticleMemcByTitle($newTitle);
		self::clearArticleMemcByTitle($oldTitle);
		return true;
	}

	public static function onArticleDelete($wikiPage) {
		if ($wikiPage) {
			$title = $wikiPage->getTitle();
			if ($title) {
				self::clearArticleMemcByTitle($title);
			}
		}
		return true;
	}

}

/******
 *
CREATE TABLE `index_info` (
`ii_page` int(10) unsigned NOT NULL,
`ii_policy` tinyint(3) unsigned NOT NULL default 0,
`ii_reason` varbinary(32) NOT NULL,
`ii_timestamp` varchar(14) NOT NULL DEFAULT '',
`ii_revision` int(10) unsigned NOT NULL default 0,
PRIMARY KEY (`ii_page`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

 ***********/

