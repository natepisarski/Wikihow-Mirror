<?php

/**
 * A class to access the page_randomizer table, which selects and lists
 * which articles are displayed when users press the prominent "Random
 * Article" link on the website.
 */
class Randomizer extends SpecialPage {

	/**
	 * Special page constructor
	 */
	public function __construct() {
		parent::__construct('Randomizer');
	}

	/**
	 * Check whether the steps wikitext given has an intro image
	 */
	private static function hasAlternateMethods($steps) {
		return preg_match('@^\s*===@m', $steps) > 0;
	}

	/**
	 * Return the number of steps in the Steps section
	 */
	private static function getNumSteps($steps) {
		//$out = preg_replace('@^(([#*]|\s)*)[^#*\s].*$@m', '$1', $steps);
		//$num = preg_match_all('@(^[^*]*$)@m', $out, $m);

		// problem with this is that it counts bullet points too
		$num = preg_match_all('@^\s*#@m', $steps, $m);
		return $num;
	}

	/**
	 * Return the number of image tags in the Steps section
	 */
	protected static function getNumStepsImages($steps) {
		return preg_match_all('@\[\[Image:@i', $steps, $m);
	}

	/**
	 * Check if wikitext has a video tag / template
	 */
	private static function hasVideo($wikitext) {
		return preg_match('@\{\{video@i', $wikitext);
	}

	/**
	 * Check if the wikitext intro contains one of the templates listed.
	 */
	private static function excludeViaTemplates($intro) {
		// list was taken from: this google doc: "Template Categories
		// on articles on wikiHow"
		// https://docs.google.com/document/d/1y-CA80c2KYIXTYYz81nnhfbEAGY9IlfIMRHT2K5_9tE/edit#
		$templates = array(
			'attention', 'copyedit', 'nfd', 'stub', 'merge', 'format',
			'accuracy', 'cleanup', 'pictures', 'character', 'copyvio',
			'inuse', 'personal', 'title', 'speedy'
		);
		$regexp = '@\{\{(' . join('|', $templates) . ')\W@i';

		return preg_match($regexp, $intro) > 0;
	}

	/**
	 * Utility method to return the wikitext for an article
	 */
	protected static function getWikitext(&$dbr, $title) {
		$rev = Revision::loadFromTitle($dbr, $title);
		if (!$rev) {
			return false;
		}
		$wikitext = ContentHandler::getContentText( $rev->getContent() );
		return $wikitext;
	}

	/**
	 * Utility method to load a bunch of rows from the DB and use memcache
	 */
	protected static function loadRows(&$dbr, $sql, $cachekey, $fname) {
		global $wgMemc;

		$expires = 25*60*60; // keep these results for 25 hours
		$result = $cachekey ? $wgMemc->get($cachekey) : false;
		if (is_array($result)) return $result;

		$res = $dbr->query($sql, $fname);
		$rows = array();
		foreach ($res as $obj) {
			$rows[] = (array)$obj;
		}

		if ($cachekey) $wgMemc->set($cachekey, $rows, $expires);
		return $rows;
	}

	/**
	 * Load the list of all articles viewed more than 25k times.
	 * This query takes 0.7 seconds and returns 14000 results on May 19 2011.
	 */
	private static function loadHighViews(&$dbr) {
		$cachekey = wfMemcKey('rnd-hv');
		$sql = "SELECT page_id FROM page WHERE page_counter > 25000";
		$rows = self::loadRows($dbr, $sql, $cachekey, __METHOD__);
		$hv = array();
		foreach ($rows as $row) {
			$id = $row['page_id'];
			$hv[$id] = true;
		}
		return $hv;
	}

	/**
	 * Load this list of all articles that have been edited more than 50 times.
	 * This query takes 5 seconds and returns 9500 results on May 19 2011.
	 */
	private static function loadHighEdits(&$dbr) {
		$cachekey = wfMemcKey('rnd-he');
		$sql = "SELECT page_id, count(*) AS count FROM page, revision WHERE page_id=rev_page AND page_namespace=" . NS_MAIN . " GROUP BY page_id HAVING count > 50";
		$rows = self::loadRows($dbr, $sql, $cachekey, __METHOD__);
		$he = array();
		foreach ($rows as $row) {
			$id = $row['page_id'];
			$he[$id] = true;
		}
		return $he;
	}

	/**
	 * Load all the featured articles into an array indexed by article ID
	 */
	private static function loadFeaturedArticles(&$dbr) {
		$cachekey = wfMemcKey('rnd-fas');
		$sql = "SELECT tl_from from templatelinks  where tl_title='Fa'";
		$rows = self::loadRows($dbr, $sql, $cachekey, __METHOD__);
		$featured = array();
		foreach ($rows as $row) {
			$id = $row['tl_from'];
			$featured[$id] = true;
		}
		return $featured;
	}

	/**
	 * Load all the rising stars articles into an array indexed by article ID
	 */
	private static function loadRisingStars(&$dbr) {
		$cachekey = wfMemcKey('rnd-rs');
		$sql = "SELECT p2.page_id FROM categorylinks, page p1, page p2 WHERE cl_to = 'Rising-Stars' AND cl_from = p1.page_id AND p1.page_title = p2.page_title AND p2.page_namespace = " . NS_MAIN;
		//$sql = "SELECT cl_from from categorylinks where cl_to='Rising-Stars'";
		$rows = self::loadRows($dbr, $sql, $cachekey, __METHOD__);
		$rising = array();
		foreach ($rows as $row) {
			$id = $row['page_id'];
			$rising[$id] = true;
		}
		return $rising;
	}

	/**
	 * List the articles with at least 10 ratings, 90% accuracy.  This set
	 * contains about 4500 items and the query takes approx. 5 seconds.
	 * (This is as of May 19 2011.)
	 */
	private static function loadHighlyRated(&$dbr) {
		$cachekey = wfMemcKey('rnd-hr');
		$sql = "SELECT rat_page, AVG(rat_rating) AS avg, COUNT(*) AS count FROM rating GROUP BY rat_page HAVING count >= 10 AND avg >= 0.90";
		$rows = self::loadRows($dbr, $sql, $cachekey, __METHOD__);
		$hr = array();
		foreach ($rows as $row) {
			$id = $row['rat_page'];
			$hr[$id] = true;
		}
		return $hr;
	}

	/**
	 * Load all articles from a specific time onwards
	 */
	private static function loadArticles(&$dbr, $secsFromUpdateTime = 0) {
		$clause = '';
		if ($secsFromUpdateTime) {
			$timestamp = wfTimestamp(TS_MW, time() - $secsFromUpdateTime);
			$clause = ' AND page_touched >= ' . $dbr->addQuotes($timestamp);
		}
		$sql = 'SELECT page_id, page_title, page_catinfo
			FROM page
			WHERE page_is_redirect = 0 AND
			page_namespace = ' . NS_MAIN .
			$timeClause;
		$articles = self::loadRows($dbr, $sql, '', __METHOD__);
		return $articles;
	}

/*
 * database schema:
 *

CREATE TABLE page_randomizer (
	pr_id INT UNSIGNED NOT NULL,
	pr_namespace INT UNSIGNED NOT NULL DEFAULT 0,
	pr_title VARCHAR(255) NOT NULL,
	pr_random DOUBLE UNSIGNED NOT NULL,
	pr_catinfo INT UNSIGNED NOT NULL DEFAULT 0,
	pr_updated VARCHAR(14) NOT NULL DEFAULT '',
	PRIMARY KEY(pr_id),
	INDEX(pr_random)
) Engine=InnoDB;

 *
 */

	/**
	 * Save or remove a set of articles to the randomizer list.
	 *
	 * @param array $article should be an associative array with id, title and
	 *   catinfo members defined
	 * @param boolean $isInRandomizerSet should be true if and only if
	 *   the article should be added or removed from the Randomizer set
	 */
	private static function dbSaveRandom(&$dbw, $articles, $isInRandomizerSet) {
		if ($isInRandomizerSet) {
			$dbw->replace('page_randomizer', 'pr_id', $articles, __METHOD__);
		} else {
			foreach ($articles as $article) {
				$dbw->delete('page_randomizer', array('pr_id' => $article['pr_id']), __METHOD__);
			}
		}
	}

	/**
	 * Clear the whole randomizer table
	 */
	private static function dbClearRandomizer(&$dbw) {
		$sql = 'DELETE FROM page_randomizer';
		$dbw->query($sql, __METHOD__);
	}

	/**
	 * Process all articles.  Add or remove each main namespace article from
	 * the randomizer set.
	 */
	public static function processAllArticles() {
		self::processArticles(0);
	}

	/**
	 * Process only recent articles, from the last hour.  Add or remove
	 * each main namespace article that matches this criterion.
	 */
	public static function processRecentArticles() {
		$about_one_hour = 61*60;
		self::processArticles(time() - $about_one_hour);
	}

	/**
	 * The process of adding and removing all articles from the randomizer
	 * set.
	 *
	 * @param int $from unix timestamp indicates from when to process.  0
	 *   means the epoch.
	 */
	private static function processArticles($from) {
		$dbr = wfGetDB(DB_REPLICA);

		$articles = self::loadArticles($dbr, $from);
		foreach ($articles as &$article) {
			$pr = array(
				'pr_id' => $article['page_id'],
				'pr_namespace' => NS_MAIN,
				'pr_title' => $article['page_title'],
				'pr_random' => wfRandom(),
				'pr_catinfo' => $article['page_catinfo'],
				'pr_updated' => wfTimestampNow(),
			);
			$article = $pr;
		}

		$featured = self::loadFeaturedArticles($dbr);
		$rising = self::loadRisingStars($dbr);
		$views = self::loadHighViews($dbr);
		$edits = self::loadHighEdits($dbr);
		$rated = self::loadHighlyRated($dbr);

		$add = array();
		$remove = array();
		$reasons = array();
		foreach ($articles as $i => $article) {
			//print "{$article['pr_title']}\n";
			$reason = array();
			$toadd = true;
			$id = $article['pr_id'];

			$title = Title::newFromDBkey($article['pr_title']);
			if (!$title) {
				$toadd = false;
				$reason[] = 'does-not-exist';
				$wikitext = '';
			} else {
				$wikitext = self::getWikitext($dbr, $title);
				if (!$wikitext) {
					$toadd = false;
					$reason[] = 'does-not-exist';
				}
			}

			if ($wikitext) {
				$intro = Wikitext::getIntro($wikitext);
				list($steps, ) = Wikitext::getStepsSection($wikitext);

				if (self::excludeViaTemplates($intro)) {
					$reason[] = 'excluded-via-template';
					$toadd = false;
				} else {
					$images = self::getNumStepsImages($steps);
					if (isset($featured[$id])) {
						$reason[] = 'featured';
					}
					if ($images && isset($rising[$id])) {
						$reason[] = 'rising';
					}
					if ($images && isset($rated[$id])) {
						$reason[] = 'highly-rated';
					}
					if ($images && isset($views[$id]) && isset($edits[$id])) {
						$reason[] = 'views-and-edits';
					}
					if ($images && self::hasAlternateMethods($steps)) {
						$reason[] = 'views-and-alternate-methods';
					}
					if ($images && isset($views[$id]) && self::getNumSteps($steps) >= 9) {
						$reason[] = 'views-and-nine-steps';
					}
					if (self::getNumStepsImages($steps) >= 3) {
						$reason[] = 'three-steps-images';
					}
					if ($images && isset($views[$id]) && self::hasVideo($wikitext)) {
						$reason[] = 'views-and-video';
					}
					if (empty($reason)) {
						$reason[] = 'no-match';
						$toadd = false;
					}
				}
			}

			if ($toadd) {
				$add[] = $article;
			} else {
				$remove[] = $article;
			}

			$reasons[] = array(
				'dprr_id' => $id,
				'dprr_namespace' => NS_MAIN,
				'dprr_title' => substr($article['pr_title'], 0, 255),
				'dprr_reasons' => substr(join(',', $reason), 0, 255),
			);
		}

		$dbw = wfGetDB(DB_MASTER);
		if (!$from) {
			// do this right before we insert a bunch of new rows
			self::dbClearRandomizer($dbw);
		} else {
			self::dbSaveRandom($dbw, $remove, false);
		}
		self::dbSaveRandom($dbw, $add, true);
	}

	/**
	 * Selects a random title.  Observes user's category filter.
	 * @return Title the title object result
	 */
	public static function getRandomTitle() {
		$NUM_TRIES = 5;

		$dbr = wfGetDB(DB_REPLICA);
		for ($i = 0; $i < $NUM_TRIES; $i++) {
			// $randstr = wfRandom(); // Alberto - 2018-06-27
			$randstr = random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX;
			$sql = "SELECT pr_title
				FROM page_randomizer
				WHERE pr_random >= $randstr
				ORDER BY pr_random";
			$sql = $dbr->limitResult($sql, 1, 0);

			$res = $dbr->query($sql, __METHOD__);
			$row = $dbr->fetchObject($res);
			$title = Title::newFromDBkey($row->pr_title);
			if ($title && $title->exists()) break;
		}

		Hooks::run( 'RandomizerGetRandomTitle', array( &$title ) );

		return $title;
	}

	/**
	 * Special:Randomizer redirects to a random URL in the set of URLs
	 * we've defined.
	 */
	public function execute($par) {

		if ($this->getLanguage()->getCode() != 'en') {
			$rp = new RandomPage();
			$title = $rp->getRandomTitle();
		} else {
			$title = self::getRandomTitle();
		}

		if (!$title) {
			// try to recover from error
			$title = Title::newFromText( wfMessage('mainpage')->text() );
		}
		$url = $title->getFullUrl();

		$this->getOutput()->redirect($url);
	}

	public function isMobileCapable() {
		return true;
	}
}
