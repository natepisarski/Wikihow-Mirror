<?php

/**
 * Encapsulates some of the logic to deal with article categories
 */
class CategorizerUtil {

	private static $stickyCategories = [ 'Articles-in-Quality-Review' ];
	private static $historicalCategories = [ 'Historical-Articles', 'Historical-Projects' ];

	/**
	 * Meta-categories that we want to preserve when an article is categorized
	 */
	public static function getStickyCats(): array {
		return self::$stickyCategories;
	}

	public static function isStickyCat(string $cat): bool {
		$cat = str_replace(" ", "-", $cat);
		return in_array($cat, self::$stickyCategories);
	}

	/**
	 * Meta-categories that can be removed when an article is categorized
	 */
	public static function getFurtherEditingCats(): array {
		return explode( "\n", wfMessage('templates_further_editing')->inContentLanguage()->text() );
	}

	/**
	 * Get categories to ignore. Articles that only have one of these categories
	 * should still be pulled into Categorizer for topic-based categorization
	 */
	private static function getCategoriesToIgnore(): array {
		global $wgContLang, $wgServer;
		$cats = wfMessage( 'categories_to_ignore' )->inContentLanguage()->text();
		$cats = explode( "\n", $cats );
		// Just get category name from the from the full category URL
		$cats = str_replace( $wgServer . "/" . $wgContLang->getNSText( NS_CATEGORY ) . ":", "", $cats );
		return $cats;
	}

	/**
	 * Get historical categories
	 * Articles in these categories should not be pulled into the Categorizer tool
	 */
	public static function getHistoricalCategories(): array {
		return self::$historicalCategories;
	}

	/**
	 * Provides the base for a query to select uncategorized articles.
	 * The returned array is to be extended with custom fields and options.
	 */
	private static function getQueryBase(): array {
		$whitelist = array_merge( self::getStickyCats(), self::getFurtherEditingCats(), self::getCategoriesToIgnore() );

		// getCategoriesToIgnore() returns categories from MediaWiki:Categories_to_ignore which also contains 'Historical-Articles' and 'Historical-Projects'
		// We don't wanna pull articles in these categories into the Categorizer tool. Hence, removing historical categories from the $whitelist array
		$whitelist = array_diff( $whitelist, self::getHistoricalCategories() );
		$whitelist = implode("','", $whitelist);
		return [
			'tables' => ['page', 'categorylinks'],
			'conds' => ['cl_from IS NULL',
					'page_id != 1548', // Sandbox article
					'page_namespace' => 0,
					'page_is_redirect' => 0
			],
			'join_conds' => ['categorylinks' => [
				'LEFT JOIN', "page_id = cl_from AND cl_to NOT IN ('$whitelist')"
			]],
		];
	}

	public static function getQueryInfoForUncategorizedPagesPage(array $namespace): array {
		$q = self::getQueryBase();
		$q['fields'] = [
			'namespace' => 'page_namespace',
			'title' => 'page_title',
			'value' => 'page_title'
		];
		$q['conds']['page_namespace'] = $namespace;
		return $q;
	}

	public static function getUncategorizedPagesIds(): array {
		$q = self::getQueryBase();
		$fields = ['page_id'];
		$opts = ['ORDER BY' => 'page_touched', 'LIMIT' => 500];

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select($q['tables'], $fields, $q['conds'], __METHOD__, $opts, $q['join_conds']);
		$pageIds = [];

		foreach ($res as $row) {
			$pageIds[] = $row->page_id;
		}
		return $pageIds;
	}

	public static function getUncategorizedPagesCount(): int {
		$q = self::getQueryBase();
		$fields = ['count' => 'count(*)'];
		$opts = [];

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select($q['tables'], $fields, $q['conds'], __METHOD__, $opts, $q['join_conds']);
		return $dbr->fetchObject($res)->count;
	}

}
