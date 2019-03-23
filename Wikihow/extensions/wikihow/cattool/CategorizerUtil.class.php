<?php

/**
 * Encapsulates some of the logic to deal with article categories
 */
class CategorizerUtil {

	private static $stickyCategories = [ 'Articles-in-Quality-Review' ];

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
	 * Provides the base for a query to select uncategorized articles.
	 * The returned array is to be extended with custom fields and options.
	 */
	private static function getQueryBase(): array {
		$whitelist = array_merge(self::getStickyCats(), self::getFurtherEditingCats());
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
