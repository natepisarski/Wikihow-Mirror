<?php

/**
 * A class that helps determine whether a Title should be filtered based on its category tree
 */
class CategoryFilter {

	static $filter = null;

	private function __construct() {}

	/**
	 * @return CategoryFilter;
	 */
	public static function newInstance() {
		$filter = null;
		if (is_null(self::$filter)) {
			self::$filter = new CategoryFilter();
		}

		return self::$filter;
	}

	/**
	 *
	 * Check if a given title has a category within its category tree that matches a blacklisted
	 * category. Note: Case sensitivity is ignored in this function
	 *
	 * @param $t Title - A NS_MAIN-namespaced Title
	 * @param $categoryBlacklist array - Array of category names resulting from $t->getText(), not urls.
	 *
	 * @return bool Value is true if a category matches blacklist, false otherwise
	 *
	 * Example:
	 *
	 * $categoryBlacklist = ["Relationships", "Youth"]; // Input isn't case-sensitive
	 * $t = Title::newFromText('Love', NS_MAIN);
	 *
	 * returns true as the Title Love has 'Relationships' in its parent category tree
	 */
	public function isTitleFiltered($t, $categoryBlacklist) {
		$isFiltered = false;

		$cats = [];
		if ($t && $t->exists() && $t->inNamespace(NS_MAIN)) {
			$cats = $this->getTitleCategories($t);
		}

		$categoryBlacklist = array_map('strtolower', $categoryBlacklist);
		if (count(array_intersect($cats, $categoryBlacklist)) > 0) {
			$isFiltered = true;
		}

		return $isFiltered;
	}

	/**
	 * @param $t Title
	 * @return array
	 */
	protected function getTitleCategories($t) {
		$parenttrees = Categoryhelper::getCurrentParentCategoryTree($t);
		$cats = [];
		if (is_array($parenttrees)) {
			$cats = array_map('strtolower', Categoryhelper::cleanCurrentParentCategoryTree($parenttrees));
		}

		return $cats;
	}

}