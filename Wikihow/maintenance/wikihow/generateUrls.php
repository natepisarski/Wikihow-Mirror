<?php
//
// Generate a list of all URLs for the sitemap generator and for
// scripts that crawl the site (like to generate cache.wikihow.com)
//

require_once __DIR__ . '/../commandLine.inc';

class GenerateURLsMaintenance {

	static function iso8601_date($time) {
		$date = substr($time, 0, 4)  . "-"
			  . substr($time, 4, 2)  . "-"
			  . substr($time, 6, 2)  . "T"
			  . substr($time, 8, 2)  . ":"
			  . substr($time, 10, 2) . ":"
			  . substr($time, 12, 2) . "Z" ;
		return $date;
	}

	static function listArticles($titlesOnly, $touchedSince, $relativeURLs=false, $forSitemap=false, $randomPercentage=0) {
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

	static function categoryTreeToList($node, &$list) {
		foreach ($node as $name => $subNode) {
			$list[] = $name;
			if ($subNode && is_array($subNode)) {
				self::categoryTreeToList($subNode, $list);
			}
		}
	}

	static function listCategories($titlesOnly) {
		$epoch = wfTimestamp( TS_MW, strtotime('January 1, 2010') );

		$ch = new CategoryHelper();
		$tree = $ch->getCategoryTreeArray();
		unset($tree['WikiHow']);
		$list = [];
		self::categoryTreeToList($tree, $list);
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

			// only include categories that are indexable
			// Note: this is quite slow and apparently implied with how the list
			// is generated.
			//$indexed = RobotPolicy::isTitleIndexable($title);
			//if (!$indexed) {
			//	continue;
			//}

			if ($titlesOnly) {
				$line = $aid . ' ' . $title->getPrefixedDBkey();
			} else {
				$line = $title->getCanonicalURL() . ' lastmod=' .  self::iso8601_date($epoch);
			}
			print "$line\n";
		}
	}

	static function main() {
		$opts = getopt('', array('titles-only', 'categories', 'since:', 'relative', 'forsitemap', 'random-percentage:'));
		$titles_only = isset($opts['titles-only']);
		$categories = isset($opts['categories']);
		$since = isset($opts['since']) ? wfTimestamp(TS_MW, $opts['since']) : '';
		$relative = isset($opts['relative']); // Output relative article URLs. E.g. '/Hug'
		// flag for if we are generating this list for the sitemap
		$forSitemap = isset($opts['forsitemap']);
		$randomPercentage = isset($opts['random-percentage']) ? (int)$opts['random-percentage'] : 0;

		if (!$categories) {
			self::listArticles($titles_only, $since, $relative, $forSitemap, $randomPercentage);
		} else {
			self::listCategories($titles_only);
		}
	}

}

GenerateURLsMaintenance::main();
