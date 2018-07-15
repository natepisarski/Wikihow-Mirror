<?php

class CatSearch extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'CatSearch' );
	}

	function execute($par) {
		$fname = 'CatSearch::execute';
		wfProfileIn( $fname );

		$out = $this->getOutput();
		$out->setRobotpolicy( 'noindex,nofollow' );

		$out->setArticleBodyOnly(true);
		if ($q = $this->getRequest()->getVal('q')) {
			/*
			if ($t = $wgRequest->getVal('t', 0)) {
				echo json_encode(array("results" => $this->formatResults($this->catToolSearch($q))));
			} else {
				echo json_encode(array("results" => $this->formatResults($this->catSearch($q))));
			}
			*/
			echo json_encode(array("results" => $this->formatResults($this->catToolSearch($q))));
		}

		wfProfileOut( $fname );
	}

	function catSearch($q) {
		global $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$prefix = "Category ";
		$query = $dbr->strencode($prefix . $q);
		$suggestions = array();
		$count = 0;

		// Add an exact category match
		$t = Title::newFromText($q, NS_CATEGORY);
		if ($t && $t->exists() && !$this->ignoreCategory($t->getText())) {
			$suggestions[] = $t->getPartialUrl();
		}

		$l = new LSearch();
		$results = $l->externalSearchResultTitles($query, 0, 30, 0, LSearch::SEARCH_CATSEARCH);
		foreach ($results as $t) {
			if (!$this->ignoreCategory($t->getText())) {
				if ($t->getNamespace() == NS_CATEGORY) {
					$suggestions[] = $t->getPartialUrl();
				}
				elseif ($t->getNameSpace() == NS_MAIN && $count < 3) {
					$count++;
					$suggestions = array_merge($suggestions, $this->getParentCats($t));
				}
			}
		}

		$suggestions = array_values(array_unique($suggestions));
		// Return the top 15
		return array_slice($suggestions, 0, 15);
	}

	function formatResults(&$suggestions) {
		$results = array();
		foreach ($suggestions as $suggestion) {
			$results[] = self::formatResult($suggestion);
		}
		return $results;
	}

	function formatResult($partialUrl) {
		// urldecode hack for Cars & Other Vehicles category
		$partialUrl = urldecode($partialUrl);

		$label = str_replace("-", " ", $partialUrl);
		$ret = array('label' => $label, 'url' => $partialUrl);
		return $ret;
	}

	public static function getParentCats(&$t) {
		global $wgContLang;
		$catNsText = $wgContLang->getNSText (NS_CATEGORY);
		$cats = str_replace("$catNsText:", "", array_keys($t->getParentCategories()));
		foreach($cats as $key => $cat) {
			if (self::ignoreCategory($cat)) {
				unset($cats[$key]);
			}
		}
		return $cats;
	}

	public static function ignoreCategory($cat) {
		$cat = str_replace("-", " ", $cat);
		$ignoreCats = wfMsgForContent("categories_to_ignore");
		$ignoreCats = explode("\n", $ignoreCats);
		$ignoreCats = str_replace("http://www.wikihow.com/Category:", "", $ignoreCats);
		$ignoreCats = str_replace("-", " ", $ignoreCats);
		return array_search($cat, $ignoreCats) !== false ? true : false || $cat == 'WikiHow' || $cat == 'Wikihow' || $cat == 'Honors' || $cat == 'Answered Requests' || $cat == 'Patrolling';
	}

	function catToolSearch($q) {
		global $wgMemc;

		$key = wfMemcKey("cattoolsearch_" . strtolower($q));
		$results = $wgMemc->get($key);
		if (!is_string($results)) {
			$catSearchResults = $this->catSearch($q);
			if (sizeof($catSearchResults) > 5) {
				$catSearchResults = array_splice($catSearchResults, 0, 5);
			}

			$partialMatches = $this->getCategoryPartialMatches($q);
			$numPartial = 20 - sizeof($catSearchResults);
			if (sizeof($partialMatches) > $numPartial) {
				array_splice($partialMatches, 0, $numPartial);
			}

			$results = array_unique(array_merge($catSearchResults, $partialMatches));
			$wgMemc->set($key, $results);
		}
		return $results;
	}

	function getCategoryPartialMatches($q) {
		$cats = CategoryInterests::getCategoriesArray();
		// Only do partial matches when the query is more than 3 characters.  Returns too much gibberish otherwise
		$results = array();
		if (strlen($q) > 3) {
			$results = $this->substrArraySearch($q, $cats);
		}
		foreach ($results as $k => $result) {
			$results[$k] = str_replace(" ", "-", $result);
		}
		return $results;
	}

	function substrArraySearch($find, $in_array, $keys_found = array()) {
		if(is_array($in_array)) {
			foreach($in_array as $key => $val) {
				if(is_array($val)) {
					$this->substrArraySearch($find, $val, $keys_found);
				} else {
					if(false !== stripos($val, $find) && !$this->ignoreCategory($val)) {
						$keys_found[] = $val;
					}
				}
			}
			return $keys_found;
		}
		return false;
	}
}
