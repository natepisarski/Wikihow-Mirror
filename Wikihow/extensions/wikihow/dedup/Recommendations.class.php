<?php
require_once("$IP/extensions/wikihow/dedup/dedupQuery.php");
require_once("$IP/extensions/wikihow/dedup/SuccessfulEdit.class.php");

/**
 * Make recommendations about what articles a user should edit.
 *
 */
class Recommendations
{
	/**
	 * Stores info on how important an article is to a user
	 */
	private $_userArticles;

	/**
	 * Stores one of the aricles the user edited, which led to the suggestion
	 */
	private $_userArticleRelated;

	/**
	 * Array of ids of related articles to exclude from recommendation calcuations
	 */
	public function __construct() {
		$this->_userArticles = array();
		$this->_userArticleRelated = array();
		$this->_relatedExcludes = array();
	}
	/**
	 * Exclude contributions in articles where the most important contributions are hard to calculate
	*/
	public function excludeWorstRelated($n = 250) {
		$sql = "select page_id,page_title, count(r.rev_len),sum(gr.rev_len * r.rev_len) as bad from page join revision r on r.rev_page=page_id join good_revision on gr_page=page_id join revision gr on gr.rev_id=gr_rev where page_namespace=0 group by page_id order by bad desc limit " . intval($n);
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$ct = 0;
		foreach ($res as $row) {
			$this->_relatedExcludes[] = $row->page_id;
			$ct++;
			if ($ct >= $n) {
				return;
			}
		}
	}
	/**
	 * Get the titles of stub articles
	 */
	static function findStubs($limit = false) {
		$dbr = wfGetDB(DB_REPLICA);
		$options = array();
		if ($limit) {
			$options['LIMIT'] = $limit;
		}
		$res = $dbr->select('categorylinks',array('cl_from'),array('cl_to' => 'Stub'),__METHOD__, $options);
		$titles = array();
		foreach ($res as $row) {
			$t = Title::newFromId($row->cl_from);
			if ($t && $t->inNamespace(NS_MAIN) && $t->getText() && sizeof($t->getText()) > 0) {
				$titles[] = $t;
			}
		}
		return($titles);
	}
	/**
	 * Look at parent categories to see if
	 * we are just using Category:WikiHow
	 */
	private function isRealCategory($parents) {
		foreach ($parents as $cat => $nParents) {
			if ($cat == "Category:WikiHow") {
				return(false);
			}
			if ($nParents && !$this->isRealCategory($nParents)) {
				return(false);
			}
		}
		return(true);
	}

	function getRelatedCats($title) {
		$parentCats = $title->getParentCategoryTree();
		foreach ($parentCats as $cat => $parents) {
			if ($this->isRealCategory($parents)) {
				$relatedCats[] = $cat;
			}
		}
		return($relatedCats);
	}
	/**
	 * Get a list of users, who want to suggest to edit this article. '
	 * @param $title Title object for the article
	 * @param minUserScore The minimum bytes a user needs to have added to an article for us to consider them as having contributed to that article
	 */
	function getSuggestedUsers($title, $minUserScore = 200) {
		if (!$title || !$title->getText()) {
			return(array());
		}

		$relatedTitles = DedupQuery::getRelated($title, 3);
		$userScore = array();
		foreach ($relatedTitles as $t) {
			if ($t['title']->getArticleId() == $title->getArticleId()) {
				continue;
			}
			if (in_array($t['title']->getArticleId(),$this->_relatedExcludes)) {
				continue;
			}
			$se = SuccessfulEdit::getEdits($t['title']->getArticleId());

			$userScore2 = array();
			foreach ($se as $e) {
				if (!isset($userScore2[$e['username']])) {
					$userScore2[$e['username'] ] = 0;
				}
				$userScore2[$e['username']] += $e['added'];
			}
			foreach ($userScore2 as $username => $score) {
				if ($score > $minUserScore) {
					$userScore[$username] = $score * $t['ct'];
					$this->_userArticles[$username][$title->getArticleId()] += $score * $t['ct'];
					$this->_userArticleRelated[$username][$title->getArticleId()][$t['title']->getArticleId()] = 1;
				}
			}
		}
		return($userScore);
	}

	/**
	 * Get a list of username for which we have recommendations
	 */
	function getUsernames() {
		return(array_keys($this->_userArticles));
	}
	/**
	 * Get a list of articles suggested for a given user
	 */
	function getSuggestionsForUser($username) {
		$articles = $this->_userArticles[$username];
		arsort($articles);
		return($articles);
	}
	/**
	 * Get articles the user edited, which contributed to the suggestion. Note, we are only returning one of the articles,
	 * but more articles may have gone into this suggestion
	 */
	 function getSuggestionReason($username, $articleId) {
		return(array_keys($this->_userArticleRelated[$username][$articleId]));
	 }
	/**
	 * Check if user is available (i.e. they have edited within the last two weeks and aren't a bot).
	 * @param $username The username of the user
	 * @return If the user is available return the user id, otherwise return false
	 */
	public static function isAvailableUser($username) {
		global $wgMemc;
		$k = wfMemcKey('is_available:' . $username);
		$v = $wgMemc->get($k);
		if (is_array($v)) {
			return($v[0]);
		}
		$u = User::newFromName($username);
		if ($u && $u->getId() > 0 && !in_array('bot',$u->getGroups()) && !in_array('staff',$u->getGroups()) && !in_array('staff_widget',$u->getGroups()) && !in_array('editfish',$u->getGroups()) ) {
			$dbr = wfGetDB(DB_REPLICA);
			$sql = "select max(rev_timestamp) as lt from revision where rev_user=" . $dbr->addQuotes($u->getId());
			$res = $dbr->query($sql, __METHOD__);
			$lastTouched = false;
			foreach ($res as $row) {
				$lastTouched = wfTimestamp(TS_UNIX, $row->lt);
			}
			$twoMonths = wfTimestamp() - 60*(60*24*60);
			print("\nA" . $lastTouched . " " . $twoMonths . "\n");
			if ($lastTouched && $lastTouched > $twoMonths) {
				$wgMemc->set($k,array($lastTouched));
				return($u->getId());
			}
		}
		$wgMemc->set($k,array(false));
		return(false);
	}
}

