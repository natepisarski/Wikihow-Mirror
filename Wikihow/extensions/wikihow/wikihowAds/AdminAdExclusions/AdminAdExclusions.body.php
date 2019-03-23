<?php

class AdminAdExclusions extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'AdminAdExclusions' );
	}

	public function execute($subPage) {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->mustache = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
		]);

		if ($subPage == 'search') {
			$this->executeSearchSubpage($out, $req);
		} else {
			$this->executeArticlesSubpage($out, $req);
		}
	}

	/**
	 * Special:AdminAdExclusions/articles (default sub-page)
	 */
	private function executeArticlesSubpage($out, $req) {
		$submitted = $req->getVal('submitted');
		$list = $req->getVal('list');
		$action = $req->getVal('action');
		$articleAdEx = new ArticleAdExclusions();

		if ($submitted == 'true') {
			$out->setArticleBodyOnly(true);
			$urlList = $req->getVal('urls');
			$urlArray = explode("\n", $urlList);
			if ($action == "add_urls") {
				list($articleIds, $errors) = $articleAdEx->addNewTitles($urlArray);
			} elseif ($action == "remove_urls") {
				list($articleIds, $errors) = $articleAdEx->deleteTitles($urlArray);
			}

			if (count($errors) > 0) {
				$result['success'] = false;
				$result['errors'] = $errors;
			}
			else {
				$result['success'] = true;
			}

			// Return the list of articles to purge
			foreach ($articleIds as $langCode => $ids) {
				$result['articleGroups'][] = [
					'langCode' => $langCode,
					'apiUrl' => UrlUtil::getBaseURL($langCode) . '/api.php',
					'articleIds' => $ids
				];
			}

			$out->addHTML( json_encode($result) );
		} elseif ($action == 'delete'){
			$out->setArticleBodyOnly(true);
			$articleAdEx->clearAllTitles();
		} elseif ($list == 'true') {
			$date = date('Y-m-d');
			$this->setCSVHeaders("adexclusions_{$date}.xls");
			$pages = $articleAdEx->getAllExclusions();
			foreach ($pages as $page) {
				print(Misc::getLangBaseURL($page['lang']) . '/' . $page['page_title'] . "\n");
			}
		} else {
			$out->setPageTitle('Ad Exclusions for articles');
			$out->addModules('ext.wikihow.ad_exclusions.articles');

			$vars = [
				'articlesLinkClass' => 'active',
				'searchLinkClass' => '',
			];
			$html = $this->mustache->render('articles_form.mustache', $vars);
			$out->addHTML($html);
		}
	}

	/**
	 * Special:AdminAdExclusions/search
	 */
	private function executeSearchSubpage($out, $req) {
		$action = $req->wasPosted() ? $req->getText('action') : 'render_page';

		if ($action == 'render_page') {
			$out->setPageTitle('Ad Exclusions for search');
			$out->addModules('ext.wikihow.ad_exclusions.search');
			$vars = [
				'articlesLinkClass' => '',
				'searchLinkClass' => 'active',
			];
			$html = $this->mustache->render('search_form.mustache', $vars);
			$out->addHTML($html);

		} elseif ($action == 'add') {
			list($queries, $errors) = SearchAdExclusions::parse($req->getText('text'));
			SearchAdExclusions::add($queries);
			$errCnt = count($errors);
			$html = $this->mustache->render('search_results.mustache', compact('errCnt', 'errors', 'queries'));
			Misc::jsonResponse(['html' => $html]);

		} elseif ($action == 'get') {
			$all = SearchAdExclusions::getAll();
			$date = date('Y-m-d');
			$this->setCSVHeaders("adexclusions_search_{$date}.xls");
			print( "lang\tquery\n" );
			foreach ($all as $lang => $queries) {
				foreach ($queries as $query => $foo) {
					print( "{$lang}\t{$query}\n" );
				}
			}
		} elseif ($action == 'del') {
			$count = SearchAdExclusions::deleteAll();
			$html = "Removed <b>$count</b> entries from the database.";
			Misc::jsonResponse(['html' => $html]);

		} else {
			Misc::exitWith404();
		}
	}

	private function setCSVHeaders(string $fname) {
		// NOTE: setArticleBodyOnly(true) doesn't work here because
		// we need to change Content-Type response header.
		$this->getOutput()->disable();
		header("Content-type: application/force-download");
		header("Content-disposition: attachment; filename=$fname");
	}

}

class ArticleAdExclusions {

	const TABLE = 'adexclusions';

	/**
	 * Outputs a CSV file with all urls in all languages that have ads excluded from them.
	 */
	public function getAllExclusions() {
		global $wgActiveLanguages;

		$dbr = wfGetDB(DB_REPLICA);
		$ids = array();
		$this->getPageIdsForLanguage($dbr, $ids, 'en');

		foreach ($wgActiveLanguages as $langCode) {
			$this->getPageIdsForLanguage($dbr, $ids, $langCode);
		}

		return Misc::getPagesFromLangIds($ids);
	}

	/**
	 * Take a list of article URLs and add them to the table of excluded articles.
	 *
	 * For URLs on www.wikihow.com, it checks Titus for any translations and adds those to
	 * the corresponding INTL DB.
	 *
	 * For URLs on intl domains, it only adds that article to that DB.
	 *
	 * @param array $articles  Full article URLs, on any wH domain
	 */
	public function addNewTitles(array $articles): array {
		global $wgDBname;

		$dbw = wfGetDB(DB_MASTER);

		$articles = array_map('urldecode', $articles); // Article URLs submitted by the user
		$pages = Misc::getPagesFromURLs($articles);
		$artIDs = []; // All article IDs including translations, grouped by language code

		foreach ($pages as $page) {

			$langCode = $page['lang'];
			$pageId = $page['page_id'];
			$artIDs[$langCode][] = $pageId;

			// Don't show ads on this article in the current language
			self::addIntlArticle($dbw, $langCode, $pageId);

			if ($langCode == 'en') {
				// Don't show ads on any translations of this article
				$artIDs = array_merge_recursive($artIDs, self::processTranslations($dbw, $pageId));
			}
		}

		// Find the ones that didn't work and tell user about them
		$errors = [];
		foreach ($articles as $article) {
			if (!array_key_exists($article, $pages)){
				$errors[] = $article;
			}
		}
		$dbw->selectDB($wgDBname);

		//reset memcache since we just changed a lot of values
		wikihowAds::resetAllAdExclusionCaches();

		return [ $artIDs, $errors ];
	}

	/**
	 * Take a list of article URLs and remove them to the table of excluded articles.
	 *
	 * For URLs on www.wikihow.com, it checks Titus for any translations and removes those from
	 * the corresponding INTL DB.
	 *
	 * For URLs on intl domains, it only removes that article to that DB.
	 *
	 * @param array $articles  Full article URLs, on any wH domain
	 */
	public function deleteTitles(array $articles): array {
		global $wgDBname;

		$dbw = wfGetDB(DB_MASTER);

		$articles = array_map('urldecode', $articles); // Article URLs submitted by the user
		$pages = Misc::getPagesFromURLs($articles);
		$artIDs = []; // All article IDs including translations, grouped by language code

		foreach ($pages as $page) {

			$langCode = $page['lang'];
			$pageId = $page['page_id'];
			$artIDs[$langCode][] = $pageId;

			// Now show ads on this article in the current language HERE!!!
			self::removeIntlArticle($dbw, $langCode, $pageId);

			if ($langCode == 'en') {
				// Don't show ads on any translations of this article
				$artIDs = array_merge_recursive($artIDs, self::processTranslations($dbw, $pageId, 'remove_urls'));
			}
		}

		// Find the ones that didn't work and tell user about them
		$errors = [];
		foreach ($articles as $article) {
			if (!array_key_exists($article, $pages)){
				$errors[] = $article;
			}
		}
		$dbw->selectDB($wgDBname);

		//reset memcache since we just changed a lot of values
		wikihowAds::resetAllAdExclusionCaches();

		return [ $artIDs, $errors ];
	}

	public function clearAllTitles() {
		global $wgDBname, $wgActiveLanguages;

		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(self::TABLE, '*', [], __METHOD__); //en goes first
		foreach ($wgActiveLanguages as $langCode) {
			$dbw->selectDB('wikidb_' . $langCode);
			$dbw->delete(self::TABLE, '*', [], __METHOD__);
		}

		//reset memcache since we just changed a lot of values
		wikihowAds::resetAllAdExclusionCaches();
	}

	/**
	 * Updates all ad exclusion translations based on the article IDs from the English DB
	 */
	public static function updateEnglishArticles() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(self::TABLE, array('ae_page'));

		$dbw = wfGetDB(DB_MASTER);
		foreach ($res as $row) {
			self::processTranslations($dbw, $row->ae_page);
		}
	}

	/**
	 * Ads all page ids for the given language to the '$ids' array that
	 * have ads excluded from them based on the table.
	 */
	private function getPageIdsForLanguage(&$dbr, &$ids, $langCode) {
		global $wgDBname;

		if ($langCode == 'en')
			$dbr->selectDB($wgDBname);
		else
			$dbr->selectDB('wikidb_' . $langCode);

		$res = $dbr->select(self::TABLE, 'ae_page', array(), __METHOD__);
		foreach ($res as $row) {
			$ids[] = array('lang' => $langCode, 'id' => $row->ae_page);
		}
	}

	/**
	 * Given an article ID for a title on www.wikihow.com, grabs the Titus data
	 * for that article and adds all translations to corresponding list of excluded
	 * articles for those languages.
	 */
	private static function processTranslations(&$dbw, $englishId, $action = 'add_urls'): array {
		global $wgActiveLanguages;

		$titusData = PageStats::getTitusData($englishId);
		$articleIds = [];
		if ($titusData) {
			foreach ($wgActiveLanguages as $langCode) {
				$intl_id = "ti_tl_{$langCode}_id";
				//titus should return fields for all active languages
				if (intval($titusData->$intl_id) > 0) {
					$articleIds[$langCode][] = $titusData->$intl_id;
					if ($action == 'add_urls') {
						self::addIntlArticle($dbw, $langCode, $titusData->$intl_id);
					} elseif ($action == 'remove_urls') {
						self::removeIntlArticle($dbw, $langCode, $titusData->$intl_id);
					}
				}
			}
		}
		return $articleIds;
	}

	/**
	 * Given an article ID and a language code, adds the given article to the
	 * associated excluded article table in the correct language DB.
	 */
	private static function addIntlArticle(&$dbw, $langCode, $articleId) {
		global $wgDBname;

		if ($langCode == 'en')
			$dbw->selectDB($wgDBname);
		else
			$dbw->selectDB('wikidb_'.$langCode);

		$sql = 'INSERT IGNORE into ' . self::TABLE . " VALUES ({$articleId})";
		$dbw->query($sql, __METHOD__);
	}

	/**
	 * Given an article ID and a language code, removes the given article from the
	 * associated excluded article table in the correct language DB.
	 */
	private static function removeIntlArticle(&$dbw, $langCode, $articleId) {
		global $wgDBname;

		if ($langCode == 'en')
			$dbw->selectDB($wgDBname);
		else
			$dbw->selectDB('wikidb_'.$langCode);

		$dbw->delete(self::TABLE, ['ae_page' => $articleId], __METHOD__);
	}

}

class SearchAdExclusions {

	private static $queries = null;

	public static function getAll(): array {
		global $wgMemc;

		// Check if already loaded
		if (is_array(self::$queries)) {
			return self::$queries;
		}

		// Check if cached
		$key = self::getMemcacheKey();
		self::$queries = $wgMemc->get($key);
		if (is_array(self::$queries)) {
			return self::$queries;
		}

		// Load from DB
		$fields = ['aes_lang', 'aes_query'];
		$opts = ['ORDER BY' => 'aes_lang, aes_query'];
		$res = wfGetDB(DB_REPLICA)->select(self::getTable(), $fields, [], __METHOD__, $opts);
		$queries = [];
		foreach ($res as $row) {
			$queries[$row->aes_lang][$row->aes_query] = 1;
		}

		self::$queries = $queries;
		$wgMemc->set($key, $queries);
		return self::$queries;
	}

	public static function isExcluded(string $query, $lang = ''): bool {
		global $wgLanguageCode;

		$query = mb_strtolower(trim($query));
		if (!$query) {
			return false;
		}

		$queries = self::getAll();
		$lang = $lang ? $lang : $wgLanguageCode;
		return isset($queries[$lang][$query]);
	}

	public static function parse(string $urls): array {
		$queries = [];
		$errors = [];
		$urls = explode("\n", $urls);

		foreach ($urls as $url) {
			$url = trim(urldecode($url));

			// Add protocol if missing
			if (strpos($url, 'http') !== 0) {
				$url = "https://$url";
			}

			// Make sure it's a search URL and then get the language code
			$pos = strpos($url, '/wikiHowTo?search=');
			if ( ($pos === false) || empty($lang = Misc::getLangFromURL($url)) ) {
				$errors[] = "Invalid URL: $url";
				continue;
			}

			// Extract the search query from the URL
			$start = $pos + 18; // length of '/wikiHowTo?search='
			$end = strpos($url, '&'); // additional query string parameters
			$query = ($end === false) ? substr($url, $start) : substr($url, $start, $end-$start);

			$queries[] = [
				'aes_lang' => $lang,
				'aes_query' => mb_strtolower(trim($query))
			];
		}
		$uniqueQs = array_unique($queries, SORT_REGULAR);
		$uniqueQs = array_values($uniqueQs); // renumber the keys ("gaps" break Mustache list iteration)
		return [ $uniqueQs, $errors ];
	}

	public static function deleteAll(): int {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(self::getTable(), '*');
		self::purgeMemcache();
		return $dbw->affectedRows();
	}

	public static function add(array $queries): int {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(self::getTable(), $queries, __METHOD__, ['IGNORE']);
		self::purgeMemcache();
		self::purgeFastly($queries);
		return $dbw->affectedRows();
	}

	private static function purgeMemcache() {
		global $wgMemc;
		$key = self::getMemcacheKey();
		$wgMemc->delete($key);
	}

	/**
	 * We only hide ads on desktop (right rail), so we don't bother purging the mobile URL.
	 */
	private static function purgeFastly(array $queries) {
		foreach ($queries as $query) {
			$domain = Misc::getCanonicalDomain($query['aes_lang']);
			$searchUrl = PROTO_HTTPS . $domain . '/wikiHowTo?search=' . urlencode($query['aes_query']);
			$result = FastlyAction::purgeURL($searchUrl);
		}
	}

	private static function getTable() {
		return Misc::getLangDB('en') . '.adexclusions_search';
	}

	private static function getMemcacheKey(): string {
		global $wgCachePrefix;
		return wfForeignMemcKey(WH_DATABASE_NAME_EN, $wgCachePrefix, 'ae_search', 'all');
	}

}
