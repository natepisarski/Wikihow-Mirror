<?php

class NewPages extends SpecialNewpages {
	const MAX_INTRO_LENGTH = 100;
	const UNCATEGORIZED = "ZZZUNCATEGORIZED"; //Adding ZZZ so it sorts to the end
	const MAX_ROWS = 50;

	//HOMEPAGE Stuff
	const MAX_HP_ARTICLES_NO_IMAGES = 3;
	const HP_CONFIG_LIST = "hp_newpages";

	//CATEGORIES Stuff
	const CAT_TABLE = "newpagescategories";

	// These templates will be removed when we translate
	function __construct() {
		parent::__construct('NewPages');
	}

	public function execute ($par) {
		global $wgHooks;

		$user = $this->getUser();
		$out = $this->getOutput();

		if($user->isLoggedIn()) {
			parent::execute($par);
			return;
		}
		$wgHooks['UseMobileRightRail'][] = ['NewPages::removeSideBarCallback'];

		$out->setPageTitle(wfMessage("np_newpages")->text());
		$data = $this->getData();
		$html = $this->displayNewPages($data);
		$out->addHtml($html);
		$out->addModuleStyles(['wikihow.newpages.styles']);
	}

	private function getData() {
		$dbr = wfGetDB(DB_REPLICA);
		$req = $this->getRequest();
		$page = $req->getVal('page', 0);

		$res = $this->getNewPagesFromTable($page);

		//now sort by category
		$titles = [];
		$vars = [
			'categories' => [],
			'howto' => wfMessage('howto_prefix')->text(),
			'seemore' => wfMessage('seemore')->text(),
			'introText' => wfMessage('np_intro')->parse(),
			'categorypicker' => []
		];
		if($res->numRows() > self::MAX_ROWS) {
			$vars['next'] = true;
			$vars['offsetNext'] = $page + 1;
			$vars['offsetTextNext'] = ucfirst(wfMessage('next')->text()) . " "  . self::MAX_ROWS . " >>>";
		}
		if($page >= 1) {
			$vars['prev'] = true;
			$vars['offsetTextPrev'] = " <<< " . ucfirst(wfMessage("previous")->text()) . " " . self::MAX_ROWS;
			if($page > 1) {
				$vars['offsetPrev'] = $page - 1;
			}
		}
		$count = 0;
		foreach($res as $row) {
			if($count >= self::MAX_ROWS) break;
			$title = Title::newFromID($row->page_id);
			$category = $this->getCategoryFromMask($row->page_catinfo, RequestContext::getMain()->getLanguage()->getCode(), false);
			if(!array_key_exists($category, $titles)) {
				$catName = $category == self::UNCATEGORIZED ? "Uncategorized" : $category;
				$titles[$category] = [
					'category' => $catName,
					'pages' => [],
					'catanchor' => self::getCategoryAnchor($category)
				];
			}

			$wikitext = self::getWikitext($title, $dbr);
			$intro = $this->getShortenedIntro($wikitext);

			$titles[$category]['pages'][] = [
				'url' => $title->getLocalURL(),
				'title' => $title->getText(),
				'introText' => $intro,
				'date' => date('M d, Y', strtotime($row->fe_timestamp ." UTC"))
			];
			$count++;
		}

		//now sort them alphabetically
		ksort($titles);

		$catVars = WikihowMobileHomepage::categoryWidget(true);
		foreach($catVars['categories'] as $catInfo) {
			if(array_key_exists($catInfo['name'], $titles)) {
				$catInfo['link'] = "#np_" . self::getCategoryAnchor($catInfo['name']);
				$vars['categorypicker'][] = $catInfo;
			}
		}


		foreach($titles as $titlesInCategory) {
			$vars['categories'][] = $titlesInCategory;
		}

		return $vars;
	}

	private static function getCategoryFromMask($catMask, $languageCode, $forceEnglish = true) {
		global $wgCategoryNamesEn, $wgCategoryNames;

		if($languageCode == "en") {
			$categories = $wgCategoryNames;
		} else {
			if($forceEnglish) {
				$categories = $wgCategoryNamesEn;
			} else {
				$categories = $wgCategoryNames;
			}
		}

		if ( $catMask ) {
			foreach ( $categories as $bit => $cat ) {
				if ( $bit & $catMask ) {
					return $cat;
				}
			}
		}
		return self::UNCATEGORIZED;
	}

	private function getNewPagesFromTable($offset = 0) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			['recentchanges', 'page', 'firstedit', 'index_info'],
			'*',
			['rc_new' => 1, 'rc_namespace' => 0, 'page_is_redirect' => 0, 'ii_policy' => RobotPolicy::POLICY_DONT_CHANGE],
			__METHOD__,
			['ORDER BY' => 'rc_timestamp DESC', 'LIMIT' => self::MAX_ROWS+1, 'OFFSET' => $offset*self::MAX_ROWS],
			[
				'page' => ['INNER JOIN', 'page_id=rc_cur_id'],
				'index_info' => ['INNER JOIN', 'ii_page=page_id'],
				'firstedit' => ['INNER JOIN', 'fe_page=rc_cur_id']
			]

		);

		return $res;
	}

	private static function getNewPagesFromTableforHomepage(&$dbr, $languageCode, $startDays, $endDays) {
		$startDate = wfTimestamp(TS_MW, strtotime("$startDays days ago"));
		$endDate = wfTimestamp(TS_MW, strtotime("$endDays days ago"));
		if($languageCode == "en") {
			$author = "WRM";
		} else {
			$author = wfMessage('translator_account')->text();
		}
		$res = $dbr->select(
			['recentchanges', 'page', 'index_info', WH_DATABASE_NAME_EN.'.titus_copy'],
			'*',
			[
				'rc_new' => 1,
				'rc_namespace' => 0,
				'page_is_redirect' => 0,
				'ii_policy' => RobotPolicy::POLICY_DONT_CHANGE,
				'rc_user_text' => $author,
				'rc_timestamp > ' . $endDate,
				'rc_timestamp < ' . $startDate,
				'ti_language_code' => $languageCode
			],
			__METHOD__,
			['ORDER BY' => 'ti_30day_views_unique DESC'],
			[
				'page' => ['INNER JOIN', 'page_id=rc_cur_id'],
				'index_info' => ['INNER JOIN', 'ii_page=page_id'],
				WH_DATABASE_NAME_EN.'.titus_copy' => ['INNER JOIN', 'ti_page_id=rc_cur_id']
			]

		);

		return $res;
	}

	private static function getNewPagesFromTableForCategoryPage(&$dbr, $catName, $languageCode) {
		global $wgCategoryNames;
		$bitcat = 0;
		$topcats = array_flip($wgCategoryNames);

		foreach ($topcats as $keytop => $cattop) {
			$cat = str_replace('-',' ',$catName);
			if (strtolower($keytop) == strtolower($cat)) {
				$bitcat |= $cattop;
				break;
			}
		}

		$startDate = wfTimestamp(TS_MW, strtotime("5 days ago"));

		if($languageCode == "en") {
			$author = "rc_user_text IN ('WRM', 'MissLunaRose')";
			$tables = ['recentchanges', 'page', 'index_info', WH_DATABASE_NAME_EN.'.titus_copy'];
			$where = [
				'rc_new' => 1,
				'rc_namespace' => 0,
				'page_is_redirect' => 0,
				$author,
				'ii_policy' => RobotPolicy::POLICY_DONT_CHANGE,
				'page_catinfo & '.$bitcat.' <> 0',
				'rc_timestamp < ' . $startDate,
				'ti_last_fellow_edit != ""'
			];
			$join = [
				'page' => ['INNER JOIN', 'page_id=rc_cur_id'],
				'index_info' => ['INNER JOIN', 'ii_page=page_id'],
				WH_DATABASE_NAME_EN.'.titus_copy' => ['INNER JOIN', 'ti_page_id=rc_cur_id']
			];
		} else {
			$author = 'rc_user_text = "' . wfMessage('translator_account')->text() . '"';
			$tables = ['recentchanges', 'page', 'index_info'];
			$where = [
				'rc_new' => 1,
				'rc_namespace' => 0,
				'page_is_redirect' => 0,
				$author,
				'ii_policy' => RobotPolicy::POLICY_DONT_CHANGE,
				'page_catinfo & '.$bitcat.' <> 0',
				'rc_timestamp < ' . $startDate
			];
			$join = [
				'page' => ['INNER JOIN', 'page_id=rc_cur_id'],
				'index_info' => ['INNER JOIN', 'ii_page=page_id']
			];
		}

		$res = $dbr->select(
			$tables,
			'*',
			$where,
			__METHOD__,
			['ORDER BY' => 'rc_timestamp DESC'],
			$join
		);

		return $res;
	}

	public function getShortenedIntro($wikitext) {
		$intro = Wikitext::getIntro($wikitext);
		$intro = preg_replace( "@\[\[[^|\]]+\|([^\]]+)\]\]@", "$1", $intro);
		$intro = preg_replace( "@\[[^\]]+\]+@", "", $intro);
		$intro = preg_replace( "@<[^>]+>@", "", $intro);
		$intro = preg_replace( "@{{[^}]*}}@", "", $intro);
		if(strlen($intro) > self::MAX_INTRO_LENGTH) {
			$intro = substr($intro, 0, self::MAX_INTRO_LENGTH) . "...";
		}

		return $intro;
	}

	public static function getWikitext($title, $dbr) {
		$good = GoodRevision::newFromTitle($title, $title->getArticleID());
		$revid = $good ? $good->latestGood() : 0;

		$rev = Revision::loadFromTitle($dbr, $title, $revid);
		if (!$rev) {
			//throw new Exception('ArticleMetaInfo: could not load revision');
			return '';
		}

		//taken from Titus code
		$wikitext = ContentHandler::getContentText( $rev->getContent() );

		return $wikitext;
	}

	public static function setCategorypageArticles(String $languageCode) {
		global $wgCategoryNames;
		$goodTitles = [];


		$dbr = wfGetDB(DB_REPLICA);
		foreach ($wgCategoryNames as $categoryName) {
			$catTitles = [];
			$res = self::getNewPagesFromTableForCategoryPage($dbr, $categoryName, $languageCode);
			foreach ($res as $row) {
				$title = Title::newFromID($row->page_id);
				if (!$title || !$title->exists()) continue;

				$catTitles[] = ['npc_page_id' => $title->getArticleID(), 'npc_category' => $categoryName];
				if(count($catTitles) > MobileWikihowCategoryPage::MAX_NEW_PAGES) break;
			}


			$goodTitles = array_merge($goodTitles, $catTitles);
		}

		$dbw = wfGetDB(DB_MASTER);
		//grab all the old ids
		$ids = $dbr->selectFieldValues(self::CAT_TABLE, 'npc_id', [], __METHOD__);

		//insert the new rows
		if(count($goodTitles) > 0) {
			$dbw->insert(self::CAT_TABLE, $goodTitles, __METHOD__);
		}

		//delete the old ids
		if(count($ids) > 0) {
			$dbw->delete(self::CAT_TABLE, 'npc_id IN (' . $dbw->makeList($ids) . ')', __METHOD__);
		}

	}

	public static function getCategoryPageArticles($category) {
		$dbr = wfGetDB(DB_REPLICA);
		$ids = $dbr->selectFieldValues(self::CAT_TABLE, 'npc_page_id', ['npc_category' => $category], __METHOD__);
		return $ids;
	}

	public static function getAllNewPagesOnCategoryPages() {
		$dbr = wfGetDB(DB_REPLICA);
		$ids = $dbr->selectFieldValues(self::CAT_TABLE, 'npc_page_id', [], __METHOD__);
		return $ids;
	}

	public static function setHomepageArticles(String $languageCode) {
		$dbr = wfGetDB(DB_REPLICA);

		$allCategories = ['Guns and Shooting', 'Health', 'Relationships'];
		$arabicCagories = ['Philosophy and Religion', 'Food and Entertaining'];

		$startDays = 5;
		$endDays = 7;

		$titles = [];
		$titlesWithoutImages = [];
		while(count($titles) < WikihowMobileHomepage::MAX_NEWPAGES && $endDays < 90) {

			$res = self::getNewPagesFromTableforHomepage($dbr, $languageCode, $startDays, $endDays);

			foreach ($res as $row) {
				$title = Title::newFromID($row->page_id);
				if (!$title || !$title->exists()) continue;

				//for intl, check the categories
				if ($languageCode != "en") {
					$tls = TranslationLink::getLinksTo($languageCode, $row->page_id, false);
					if (count($tls) == 0) continue;

					$tl = $tls[0];
					$category = $dbr->selectField(
						WH_DATABASE_NAME_EN . ".categorylinks",
						'cl_to',
						['cl_from' => $tl->fromAID],
						__METHOD__
					);

					if (in_array($category, $allCategories)) continue;
					if ($languageCode == "ar" && in_array($category, $arabicCagories)) continue;

					//now check the top level category
					$catinfo = $dbr->selectField(
						WH_DATABASE_NAME_EN . ".page",
						'page_catinfo',
						['page_id' => $tl->fromAID],
						__METHOD__
					);

					$category = self::getCategoryFromMask($catinfo, $languageCode, true);
					if (in_array($category, $allCategories)) continue;
					if ($languageCode == "ar" && in_array($category, $arabicCagories)) continue;
				}

				$wikitext = self::getWikitext($title, $dbr);

				if (Wikitext::countImages($wikitext) == 0) {
					$titlesWithoutImages[] = $title->getArticleID();
				} else {
					$titles[] = $title->getArticleID();
				}

				if(count($titles) >= WikihowMobileHomepage::MAX_NEWPAGES) {
					break;
				}

			}

			//do we have enough with images?
			if (count($titles) < WikihowMobileHomepage::MAX_NEWPAGES) {
				array_merge($titles, array_slice($titlesWithoutImages, 0, WikihowMobileHomepage::MAX_NEWPAGES - count($titles)));
			}

			$startDays = $endDays;
			$endDays += 2;
		}

		ConfigStorage::dbStoreConfig(self::HP_CONFIG_LIST, implode("\n", $titles), true,$err);
	}

	public static function getHomepageArticles() {
		$titles = [];

		$articleList =  ConfigStorage::dbGetConfig(self::HP_CONFIG_LIST);

		if($articleList !== false && $articleList != "") {
			$articleIds = explode("\n", $articleList);
			foreach ($articleIds as $id) {
				$title = Title::newFromID($id);
				$titles[] = $title;
			}
		}

		return $titles;
	}

	private function displayNewPages($vars) {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);
		return $m->render('newpages.mustache', $vars);
	}

	private function getCategoryAnchor($category) {
		return str_replace(" ", "", $category);
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public function isMobileCapable() {
		return true;
	}

}

/************
CREATE TABLE `newpagescategories` (
`npc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`npc_category` varbinary(255) NOT NULL DEFAULT '',
`npc_page_id` int(10) unsigned NOT NULL,
PRIMARY KEY (`npc_id`)
) ENGINE=InnoDB;
 */
