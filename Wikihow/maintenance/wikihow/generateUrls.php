<?php
//
// Generate a list of all URLs for the sitemap generator and for
// scripts that crawl the site (like to generate cache.wikihow.com)
//

require_once __DIR__ . '/../Maintenance.php';

class GenerateURLs extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generate a list of URLs, to be used for things like generating sitemaps';

		// addOption(long_form, description, required (bool), takes_args (bool), short_form)
		$this->addOption('titles-only', 'Produce only a list of title, no lastmod=date in output', false, false, 't');
		$this->addOption('categories', 'Produce output for all categories, instead of all articles', false, false, 'c');
		$this->addOption('since', 'Produce output for all articles touched after a certain date (articles only)', false, true, 's');
		$this->addOption('relative', 'Produce relative URLs rather than full ones (articles only)', false, false, 'r');
		$this->addOption('forsitemap', 'Produce format specific for sitemaps (articles only)', false, false, 'f');
		$this->addOption('random-percentage', 'Only output some random set of articles, as percentage of all articles (articles only)', false, true, 'p');
	}

	public function execute() {
		$titles_only = $this->getOption('titles-only');
		$categories = $this->getOption('categories');
		$since = $this->getOption('since');
		if ($since) {
			$since = wfTimestamp(TS_MW, 'since');
		}
		$relative = $this->getOption('relative'); // Output relative article URLs. E.g. '/Hug'
		// flag for if we are generating this list for the sitemap
		$forSitemap = $this->getOption('forsitemap');
		$randomPercentage = (int)$this->getOption('random-percentage', 0);

		if (!$categories) {
			self::listArticles($titles_only, $since, $relative, $forSitemap, $randomPercentage);
		} else {
			self::listCategories($titles_only, $relative, $forSitemap);
		}
	}

	private static function iso8601_date($time) {
		$date = substr($time, 0, 4)  . "-"
			  . substr($time, 4, 2)  . "-"
			  . substr($time, 6, 2)  . "T"
			  . substr($time, 8, 2)  . ":"
			  . substr($time, 10, 2) . ":"
			  . substr($time, 12, 2) . "Z" ;
		return $date;
	}

	private static function listArticles($titlesOnly, $touchedSince,
		$relativeURLs = false, $forSitemap = false, $randomPercentage = 0
	) {
		$PAGE_SIZE = 2000;
		$dbr = wfGetDB(DB_REPLICA);

		$domainIds = array();
		// keep track of any pages in test domain
		if ($forSitemap && class_exists('AlternateDomain') && AlternateDomain::isAltDomainLang()) {
			$domainIds = AlternateDomain::getAllPages();
		}

		if ($randomPercentage > 0) {
			// note: count includes all real articles, not just indexable ones
			$count = (int)$dbr->selectField('page',
				'count(*)',
				[ 'page_namespace' => NS_MAIN,
				  'page_is_redirect' => 1 ],
				__METHOD__);
			$numArticles = (int)ceil( ((float)$randomPercentage * $count) / 100.0 );
			$randStart = wfRandom();
		}

		$totalArticles = 0;
		$page = 0;
		while (true) {
			$offset = $PAGE_SIZE * $page;

			if ($touchedSince) {
				$res = $dbr->select( [ 'page', 'recentchanges' ],
					[ 'page_id', 'page_title', 'page_touched' ],
					[ 'page_id = rc_cur_id',
					  'page_namespace' => NS_MAIN,
					  'page_is_redirect' => 0,
					  'rc_timestamp >= ' . $dbr->addQuotes($touchedSince),
					  'rc_minor' => 0 ],
					__METHOD__,
					[ 'GROUP BY' => 'page_id',
					  'ORDER BY' => 'page_touched DESC',
					  'OFFSET' => $offset,
					  'LIMIT' => $PAGE_SIZE ] );
			} else {
				$conds = [ 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ];
				$options = [ 'OFFSET' => $offset, 'LIMIT' => $PAGE_SIZE ];
				if ($randomPercentage > 0) {
					$conds[] = 'page_random >= ' . $dbr->addQuotes($randStart);
				} else {
					$options[ 'ORDER BY' ] = 'page_touched DESC';
				}
				$res = $dbr->select( 'page',
					[ 'page_id', 'page_title', 'page_touched' ],
					$conds,
					__METHOD__,
					$options );
			}

			$numRows = $res->numRows();
			if (!$numRows) {
				if ($randomPercentage > 0) {
					// If we don't find enough articles in the last search, we need to
					// loop article the list and start at the beginning.
					$randStart = '0.0';
					$page = 0;
					continue;
				} else {
					break;
				}
			}

			foreach ($res as $row) {
				$title = Title::newFromDBKey($row->page_title);
				if (!$title) {
					continue;
				}

				// check if the page is part of the domain test and if so remove it
				if (isset($domainIds[$row->page_id])) {
					continue;
				}

				$indexed = RobotPolicy::isTitleIndexable($title);
				if (!$indexed) {
					continue;
				}

				if ($titlesOnly) {
					$line = $row->page_id . ' ' . $title->getDBkey();
				} else {
					$line = $relativeURLs ? $title->getLocalURL() : $title->getCanonicalURL();
					$line .= ' lastmod=' . self::iso8601_date($row->page_touched);
				}
				print "$line\n";

				if ($randomPercentage > 0) {
					$totalArticles += 1;
					// We only want about $numArticles
					if ($totalArticles >= $numArticles) {
						break 2; // breaks out of outer loop as well
					}
				}
			}

			$page += 1;
		}
	}

	private static function categoryTreeToList($node, &$list) {
		foreach ($node as $name => $subNode) {
			$list[] = $name;
			if ($subNode && is_array($subNode)) {
				self::categoryTreeToList($subNode, $list);
			}
		}
	}

	private static function listCategories($titlesOnly, $relativeURLs = false, $forSitemap = false) {
		$list = [];

		if ($forSitemap) {
			$tree = CategoryHelper::getIndexableCategoriesFromTree();
			$list = array_keys($tree);
		} else {
			$ch = new CategoryHelper();
			$tree = $ch->getCategoryTreeArray();
			unset($tree['WikiHow']);
			self::categoryTreeToList($tree, $list);
		}

		$uniqueList = [];

		foreach ($list as $cat) {
			$title = Title::makeTitle(NS_CATEGORY, $cat);
			if (!$title || $title->getArticleID() <= 0) {
				continue;
			}

			if ($title->isRedirect()) {
				continue;
			}

			$aid = $title->getArticleID();

			// don't list same category twice
			if (isset($uniqueList[$aid])) {
				continue;
			}
			$uniqueList[$aid] = true;

			// Only include categories that are visible to anons.  Getting the WikiHowCategoryViewer articles count is
			// unfortunately is the best way to get this data currently.
			if ($forSitemap) {
				$viewer = new WikihowCategoryViewer($title, RequestContext::getMain());
				// we still do this call even if we don't want FA section on this page b/c
				// it initializes the article viewer object
				$fas = $viewer->getFAs();
				if(count($viewer->articles) <= 0) { //nothing to show
					continue;
				}
			}


			if ($titlesOnly) {
				$line = $aid . ' ' . $title->getPrefixedDBkey();
			} else {
				$line = $relativeURLs ? $title->getLocalURL() : $title->getCanonicalURL();
				$line .= ' lastmod=' . self::iso8601_date($title->getTouched());
			}
			print "$line\n";
		}
	}

}

$maintClass = 'GenerateURLs';
require_once RUN_MAINTENANCE_IF_MAIN;
