<?php

/*
* A utility class that assists in storing and retreiving category interests for users.
* See the CategorySearchUI for the accompanying interface that uses this class.
* DB Schema:
*
*	CREATE TABLE `category_interests` (
*	  `ci_user_id` mediumint(8) unsigned NOT NULL default '0',
*	  `ci_visitor_id` varbinary(20) NOT NULL default '',
*	  `ci_category` varchar(255) NOT NULL default '',
*	  `ci_timestamp` varchar(14) NOT NULL default '',
*	  PRIMARY KEY  (`ci_user_id`,`ci_visitor_id`,`ci_category`),
*	  KEY `ci_category` (`ci_category`)
*	) ENGINE=InnoDB DEFAULT CHARSET=latin1
*/
class CategoryInterests extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'CategoryInterests' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $out->getRequest();
		$user = $this->getUser();


		$out->setRobotPolicy( 'noindex,nofollow' );

		$retVal = false;
		$action = $request->getVal('a');

		if ($user->getId() == 0 && $action != 'suggnew' && $action != 'add') {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
		}

		$category = $request->getVal('cat');
		switch ($action) {
			case 'hier':
				$retVal = $this->getCatPath($category);
				break;
			case 'trunc':
				$retVal = array('related' => $this->getSubCategoriesTruncated($category));
				break;
			case 'sugg':
				$retVal = array('suggestions' => $this->suggestCategoryInterests());
				break;
			case 'suggnew':
				$retVal = array('suggestions' => $this->suggestNewCategories($category));
				break;
			case 'get':
				$retVal = array('interests' => $this->getUsedCat());
				break;
			case 'sub':
				$arr = array($category);
				$retVal = $this->getSubCategoryInterests($arr);
				break;
			case 'add':
				$retVal = $this->addCategoryInterest($category);
				break;
			case 'remove':
				$retVal = $this->removeCategoryInterest($category);
				break;
			default:
				// Oops. Didn't understand the action
				$retVal = false;
		}

		$out->setArticleBodyOnly(true);
		$out->addHtml(json_encode($retVal));
	}

	/*
	*	Returns a list of categories in the form of the title name. Return the top-level categories if no categories have been selected
	*/
	public static function getCategoryInterests() {
		global $wgCategoryNames;

		$user = RequestContext::getMain()->getUser();

		$catInterests = array();
		$cond = array();
		$cond['ci_user_id'] = $user->getId() ? $user->getId() : 0;

		if ($user->isAnon()) {
			$cond['ci_visitor_id'] = WikihowUser::getVisitorId();
			//can't be anonymous AND have no visitor id...
			if ($cond['ci_visitor_id'] == '') return array();
		}

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('category_interests', array('ci_category',), $cond,__METHOD__);
		foreach ($res as $row) {
			$catInterests[] = str_replace('-', ' ', stripslashes($row->ci_category));
		}

		$cond = array();
		$cond['ce_user_id'] = $user->getId() ? $user->getId() : 0;

		if ($user->isAnon()) {
			$cond['ce_visitor_id'] = WikihowUser::getVisitorId();
		}

		//let's now add the categories entered on sign-up
		$res = $dbr->select('category_expertise', array('ce_category',), $cond,__METHOD__);
		foreach ($res as $row) {
			$catInterests[] = str_replace('-', ' ', stripslashes($row->ce_category));
		}

		//only one of each...
		$catInterests = array_unique($catInterests);

		return $catInterests;
	}

	/*
	* given an array of category titles, return all the subcategories below these categories
	*/
	public static function getSubCategoryInterests(&$categories) {
		global $wgMemc;
		$cats = array();
		foreach ($categories as $category) {
			$key = wfMemcKey('catint_subcats', $category);
			$subcats = $wgMemc->get($key);
			if (!$subcats) {
				$t = Title::newFromText($category, NS_CATEGORY);
				$subcats = self::getSubCategories($t);
				$wgMemc->set($key, $subcats, 60 * 60 * 24);
			}
			$cats =	array_merge($cats,$subcats);
		}

		return array_values(array_unique($cats));
	}

	/*
	* Given a NS_CATEGORY-namespaced title object, return all the subcategories in a one dimensional array
	*/
	private function getSubCategories(&$t) {
		$flattened = array();
		if ($t && $t->exists()) {
			$tree = self::getSubCategoryTree($t);
			// only flatten a tree if there is a tree. Handles the case where the category is a leaf node
			if (is_array($tree)) {
				self::flattenTree($flattened, $tree);
				$flattened = str_replace(' ', '-', $flattened);
			}
		}
		return $flattened;
	}


	/*
	* Given a NS_CATEGORY-namespaced title object, return a subcategory associative array tree
	*/
	public static function getSubCategoryTree(&$t) {
		global $wgContLang;

		$flattened = array();
		$parentTree = $t->getParentCategoryTree();
		if (is_array($parentTree)) {
			self::flattenTree($flattened, $parentTree);
		}

		$flattened = array_reverse($flattened);
		// Don't forget to add the actual category
		$flattened[] = $t->getPartialURL();
		// Convert it to a format that matches the result of CategoryHelper::getCategoryTreeArray();
		$catNsText = $wgContLang->getNSText (NS_CATEGORY);
		$flattened = str_replace($catNsText . ':', '', $flattened);
		$flattened = str_replace('-', ' ', $flattened);

		$ch = new CategoryHelper();
		$tree = $ch->getCategoryTreeArray();
		foreach ($flattened as $cat) {
			$tree = $tree[$cat];
		}

		return $tree;
	}

	private function getPeerCategories(&$t) {
		global $wgCategoryNames;

		$flattened = array();
		if ($t && $t->exists()) {
			$cat = CatSearch::getParentCats($t);
			// Top level category
			if (empty($cat)) {
				$topCats = array();
				$catNames = array_values($wgCategoryNames);
				foreach ($catNames as $name) {
					$topCats[] = str_replace(" ", "-", $name);
				}
				return $topCats;
			}

			$parentTitle = Title::newFromText($cat[0], NS_CATEGORY);
			$tree = self::getSubCategoryTree($parentTitle);
			if (!is_array($tree)) {
				$tree = array($tree);
			}
			$flattened = array_keys($tree);

			// Remove category of the parameter from the array
			$pos = array_search($t->getText(), $flattened);
			if (false !== $pos) {
				unset($flattened[$pos]);
			}
		}
		return $flattened;
	}

	/*
	* Given a tree, return a flattened (one-dimensional) array of all the tree values.
	*/
	private function flattenTree(&$flattened, &$tree) {
		foreach (array_keys($tree) as $node) {
			if (is_array($tree[$node])) {
				array_push($flattened, $node);
				self::flattenTree($flattened, $tree[$node]);
			} else {
				array_push($flattened, $node);
			}
		}
	}

	/*
	* Truncate a tree structure below a certain depth.
	* Returns a 1 dimensional array of categories to the specified depth
	*/
	private function truncateTree(&$tree, $depth) {
		$trimmed = array();
		if (!is_array($tree)) {
			return $trimmed;
		}
		foreach (array_keys($tree) as $node) {
			if (is_array($tree[$node]) && $depth != 1) {
				array_push($trimmed, $node);
				$trimmed = array_merge($trimmed, self::truncateTree($tree[$node], $depth - 1));

			} else {
				array_push($trimmed, $node);
			}
		}
		return $trimmed;
	}


	/*
	* Insert a category into the category_interest table for the logged in user. Categories should be in the form of the category url title.
	* Ex: Arts-and-Entertainment or Actor-Appreciation
	*/
	public static function addCategoryInterest($category) {
		$user = RequestContext::getMain()->getUser();

		// Don't add a category if it isn't a valid category title
		$t = Title::newFromText($category, NS_CATEGORY);
		if (!$t->exists()) {
			return false;
		}

		$dbw = wfGetDB(DB_MASTER);
		$category = str_replace(' ','-', $dbw->strencode($t->getText()));
		$cond = array('ci_category' => $category, 'ci_timestamp' => wfTimestampNow());
		$cond['ci_user_id'] = $user->getId() ? $user->getId() : 0;

		if ($user->isAnon()) {
			$cond['ci_visitor_id'] = WikihowUser::getVisitorId();
			//can't be anon AND w/o a visitor id...
			if ($cond['ci_visitor_id'] == '') return false;
		}

		return $dbw->insert('category_interests', $cond, __METHOD__, array('IGNORE'));
	}

	/*
	* Removes a category from the category_interest table for the logged in user. Categories should be in the form of the category url title.
	* Ex: Arts-and-Entertainment or Actor-Appreciation
	*/
	public static function removeCategoryInterest($category) {
		$user = RequestContext::getMain()->getUser();

		$dbw = wfGetDB(DB_MASTER);
		$category = str_replace(' ','-', $dbw->strencode($category));
		$cond = array('ci_category' => $category);
		$cond['ci_user_id'] = $user->getId() ?  $user->getId() : 0;
		if ($user->isAnon()) {
			$cond['ci_visitor_id'] = WikihowUser::getVisitorId();
		}
		$res = $dbw->delete('category_interests', $cond, __METHOD__);

		$cond = array('ce_category' => $category);
		if ($user->isLoggedIn()) {
			$cond['ce_user_id'] = $user->getId();
		}
		else {
			$cond['ce_visitor_id'] = WikihowUser::getVisitorId();
		}
		$res = $dbw->delete('category_expertise', $cond, __METHOD__);
		return $res;
	}

	/*
	* Suggest user interests based on the last 10 articles they've edited
	*/
	public static function suggestCategoryInterests() {
		$user = RequestContext::getMain()->getUser();

		$interests = array();
		if ($user->getId() == 0) {
			return $interests;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$res = ProfileBox::fetchEditedData($user->getName(), 10);
		foreach ($res as $row) {
			$t = Title::newFromId($row->page_id);
			if ($t && $t->exists()) {
				$interests = array_merge($interests, CatSearch::getParentCats($t));
			}
		}

		$interests = array_unique($interests);
		foreach ($interests as $k => $interest) {
			if (CatSearch::ignoreCategory($interest)) {
				unset($interests[$k]);
			}
		}
		// Give them some random top-level categories if they haven't done any edits yet
		if (!sizeof($interests)) {
			$interests = self::getGenericSuggestions();
		}

		return array_slice(array_values($interests), 0, 3);
	}

	private static function suggestNewCategories($category) {
		$limit = 6; //we only need 3, but we remove used ones in javascipt so let's grab more

		if ($category) {
			$ary = array($category);
			$interests = self::getSubCategoryInterests($ary);

			if (count($interests) < $limit) {
				$t = Title::newFromText($category, NS_CATEGORY);
				$peer_interests = self::getPeerCategories($t);
				$interests = $interests ? array_merge($interests, $peer_interests) : $peer_interests;
			};
		}

		if (count($interests) < $limit) {
			$gen_interests = self::getGenericSuggestions();
			$interests = $interests ? array_merge($interests, $gen_interests) : $gen_interests;
		}

		//dedup
		$interests = array_unique($interests);

		return array_slice(array_values($interests), 0, $limit);
	}

	private static function getGenericSuggestions() {
		global $wgCategoryNames;
		$topCats = array_values($wgCategoryNames);
		$rnd = rand(1, 6);
		for ($i = 1; $i < 4; $i++) {
			$interests[] = str_replace(" ", "-", $topCats[$i * $rnd]);
		}
		return $interests;
	}


	public static function getSubcategoriesTruncated($category, $depth = 1) {
		$t = Title::newFromText($category, NS_CATEGORY);
		if ($t && $t->exists()) {
			$tree = self::getSubCategoryTree($t);
		}
		$result = array();
		if (is_array($tree)) {
			$trimmed = array();
			$result = self::truncateTree($tree, $depth);
		}

		if (!empty($result)) {
			$result = array_values($result);
		}
		return $result;
	}

	public static function getCategoriesArray() {
		$ch = new CategoryHelper();
		$tree = $ch->getCategoryTreeArray();
		$flattened = array();
		self::flattenTree($flattened, $tree);
		return $flattened;
	}

	public static function getCatPath($cat) {
		$t = Title::newFromText($cat, NS_CATEGORY);
		$catPath = array();
		if ($t && $t->exists()) {
			$flattened = array();
			$parentTree = $t->getParentCategoryTree();
			// Extract on category path in the case where multiple super-categories are present for a subcat
			while (!empty($parentTree) && is_array($parentTree)) {
				$keys = array_keys($parentTree);
				$values = array_values($parentTree);
				$flattened[] = $keys[0];
				$parentTree = is_array($values[0]) ? $values[0] : null;
			}

			$flattened = array_reverse($flattened);
			// Don't forget to add the actual category
			$flattened[] = $t->getPartialURL();
			// Convert it to a format that matches the result of CategoryHelper::getCategoryTreeArray();
			$flattened = str_replace('Category:', '', $flattened);
			$flattened = str_replace('-', ' ', $flattened);
			$catPath = $flattened;
		}
		return $catPath;
	}

	public static function getCategoryTreeArray() {
		$ch = new CategoryHelper();
		return $ch->getCategoryTreeArray();
	}

	public function getUsedCat($t = null) {
		$used_cat = '';

		if ($t) {
			$user_cats = CategoryInterests::getCategoryInterests();
			$parenttree = $t->getParentCategoryTree();
			$page_cats = CategoryHelper::cleanCurrentParentCategoryTree($parenttree);

			//find the match
			if ($user_cats && $page_cats) {
				$same_cats = array_intersect($user_cats, $page_cats);
				if ($same_cats) {
					$used_cat = array_shift($same_cats);
				}
			}
		}

		return $used_cat;
	}
}


/*
Categories in which a user claims to be an expert

CREATE TABLE category_expertise (
	ce_user_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ce_visitor_id VARBINARY(20) NOT NULL DEFAULT '',
	ce_email  VARBINARY(255) NOT NULL DEFAULT '',
	ce_category VARCHAR(255) NOT NULL DEFAULT '',
	ce_creatdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
create index ce_user_index on category_expertise (ce_user_id);
*/

class CategoryExpertise extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'CategoryExpertise' );
	}

	function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();

		$out->setRobotPolicy( 'noindex,nofollow' );

		if ($request->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$cats = explode(',', $request->getVal('cats'));
			$email = $request->getVal('email');

			//add these cats to the category_expertise table
			foreach ($cats as $cat) {
				$this->addExpertiseCategory($cat, $email);
			}

			return;
		}
		else {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
		}
	}

	function addExpertiseCategory($cat, $email = '') {
		if (empty($cat)) return;

		$user = $this->getUser();
		if (!$user) return;

		$cond = array('ce_category' => $cat);
		if ($user->isLoggedIn()) {
			$cond['ce_user_id'] = $user->getId();
		}
		else {
			$cond['ce_visitor_id'] = WikihowUser::getVisitorId();
			$cond['ce_email'] = $email;
		}

		$dbw = wfGetDB(DB_MASTER);
		$count = $dbw->selectField('category_expertise', array('count(*)'), $cond, __METHOD__);
		if ($count > 0) return;

		$res = $dbw->insert('category_expertise',$cond, __METHOD__, array('IGNORE'));
	}
}
