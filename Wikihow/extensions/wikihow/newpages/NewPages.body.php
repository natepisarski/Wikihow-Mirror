<?php

class NewPages extends SpecialNewpages {
	const MAX_INTRO_LENGTH = 100;
	const UNCATEGORIZED = "ZZZUNCATEGORIZED"; //Adding ZZZ so it sorts to the end
	const MAX_ROWS = 50;

	//HOMEPAGE Stuff
	const MAX_HP_ARTICLES_NO_IMAGES = 3;
	const HP_CONFIG_LIST = "hp_newpages";


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
			$category = $this->getCategoryFromMask($row->page_catinfo);
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

	private function getCategoryFromMask($catMask) {
		global $wgCategoryNames;
		if ( $catMask ) {
			foreach ( $wgCategoryNames as $bit => $cat ) {
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

	private static function getNewPagesFromTableforHomepage(&$dbr) {
		$startDate = wfTimestamp(TS_MW, strtotime("5 days ago"));
		$endDate = wfTimestamp(TS_MW, strtotime("7 days ago"));
		$res = $dbr->select(
			['recentchanges', 'page', 'index_info', 'titus_copy'],
			'*',
			[
				'rc_new' => 1,
				'rc_namespace' => 0,
				'page_is_redirect' => 0,
				'ii_policy' => RobotPolicy::POLICY_DONT_CHANGE,
				'rc_user_text' => 'WRM',
				'rc_timestamp > ' . $endDate,
				'rc_timestamp < ' . $startDate,
				'ti_language_code' => "en"
			],
			__METHOD__,
			['ORDER BY' => 'ti_30day_views_unique DESC'],
			[
				'page' => ['INNER JOIN', 'page_id=rc_cur_id'],
				'index_info' => ['INNER JOIN', 'ii_page=page_id'],
				'titus_copy' => ['INNER JOIN', 'ti_page_id=rc_cur_id']
			]

		);

		return $res;
	}

	private static function getNewPagesFromTableForCategoryPage(&$dbr, $catName) {
		global $wgCategoryNames;
		$bitcat = 0;
		$topcats = array_flip($wgCategoryNames);

		foreach ($topcats as $keytop => $cattop) {
			$cat = str_replace('-',' ',$catName);
			if (strtolower($keytop) == $cat) {
				$bitcat |= $cattop;
				break;
			}
		}

		$res = $dbr->select(
			['recentchanges', 'page', 'index_info'],
			'*',
			[
				'rc_new' => 1,
				'rc_namespace' => 0,
				'page_is_redirect' => 0,
				'ii_policy' => RobotPolicy::POLICY_DONT_CHANGE,
				'page_catinfo & '.$bitcat.' <> 0'
			],
			__METHOD__,
			['ORDER BY' => 'rc_timestamp DESC'],
			[
				'page' => ['INNER JOIN', 'page_id=rc_cur_id'],
				'index_info' => ['INNER JOIN', 'ii_page=page_id']
			]

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

	public static function setHomepageArticles() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = self::getNewPagesFromTableforHomepage($dbr);

		$titlesWithoutImages = [];
		$titles = [];
		foreach($res as $row) {
			$title = Title::newFromID($row->page_id);
			if(!$title || !$title->exists()) continue;

			$wikitext = self::getWikitext($title, $dbr);

			if(Wikitext::countImages($wikitext) == 0) {
				$titlesWithoutImages[] = $title->getArticleID();
			} else {
				$titles[] = $title->getArticleID();
			}

			//store 2x as much as we need so we don't end up with too little
			if(count($titles) >= WikihowMobileHomepage::MAX_NEWPAGES) {
				break;
			}
		}

		//do we have enough with images?
		if(count($titles) < WikihowMobileHomepage::MAX_NEWPAGES) {
			array_merge($titles, array_slice($titlesWithoutImages, 0, WikihowMobileHomepage::MAX_NEWPAGES - count($titles)));
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
