<?php
if ( !defined('MEDIAWIKI') ) die();

class GetArticles {

	const TABLE = 'category_article_votes';
	const ARTICLES_PER_REQUEST = 5;
	const SKIPS_ENABLED = true;

	var $user;
	var $skipper;
	var $skipCats;


	private static function getCatsToIgnore() {
		$cats = wfMessage('categories_to_ignore')->inContentLanguage()->text();
		$cats = explode("\n", $cats);
		$cats = str_replace("http://www.wikihow.com/Category:", "", $cats);
		return $cats;
	}

	function __construct() {
		global $wgUser;

		$this->user = $wgUser;
		$this->skipper = new ToolSkip('CategoryGuardian');
		// $this->skipper->clearSkipCache();
		$skipped = $this->skipper->getSkipped() ? $this->skipper->getSkipped() : [];
		$ignoreCats = self::getCatsToIgnore();
		$this->skipCats = array_unique( array_merge( $ignoreCats, $skipped ), SORT_REGULAR );
	}

	public function getCategoryWithArticles() {
		$cat = $this->getUnresolvedCategory();

		if ( !$cat ) {
			$cat = $this->getRandomCat();
		}

		$categoryKey = $cat->getDBKey();
		$articles = $this->articlesForCategory( $categoryKey );
		$this->skipper->skipItem( $categoryKey );

		return [
			'cat' => $cat,
			'articles' => $articles
		];
	}

	private static function getRandomCat() {
		$ignore = array_flip( self::getCatsToIgnore() );
		$cats = CategoryHelper::getAllCategories();
		$cat = null;
		while ( !$cat ) {
			$i = array_rand( $cats );
			$cat = str_replace( '*', '', $cats[$i] );
			if ( isset( $ignore[ $cat ] ) ) {
				$cat = null;
			}
		}
		$cat = Title::newFromText( $cat, NS_CATEGORY );
		return $cat;
	}

	private function articlesForCategory( $categoryKey ) {
		$dbr = wfGetDB(DB_REPLICA);
		$table = [ self::TABLE, 'page' ];
		$vars = '*';
		$cond = ['cat_slug' => $categoryKey, 'resolved = 0', 'page.page_id = category_article_votes.page_id'];
		$options = ['LIMIT' => self::ARTICLES_PER_REQUEST, 'GROUP BY' => 'category_article_votes.page_id'];

		$res = $dbr->select( $table, $vars, $cond, __METHOD__, $options );
		$articles = array();
		foreach ( $res as $row ) {
			$articles[] = $row;
		}

		if (count($articles) < self::ARTICLES_PER_REQUEST) {
			$extraArticlesInCategory = $this->getExtraArticlesInCategory( $articles, $categoryKey );
			$articles = array_merge($articles, $extraArticlesInCategory);
		}

		return $articles;
	}

	private function getExtraArticlesInCategory( $articles, $categoryKey ) {
		$dbr = wfGetDB( DB_REPLICA );
		$numNeeded = self::ARTICLES_PER_REQUEST - count($articles);
		$conditions = [
			'categorylinks.cl_to' => $categoryKey,
			'page.page_namespace' => NS_MAIN
		];

		if ( count( $articles ) ) {
			// need to make sure that we are not returning the same ones over and over...
			$pageIds = array();
			foreach ( $articles as $article ) {
				$pageIds[] = $dbr->addQuotes( $article->page_id );
			}
			$pageIds = implode( $pageIds, ',' );
			$conditions[] = "page.page_id NOT IN ($pageIds)";
		}
		$slug = $dbr->addQuotes( $categoryKey );
		$conditions[] = "page.page_id NOT IN( SELECT page_id from category_article_votes where cat_slug = $slug and resolved = 1)";

		$res = $dbr->select(
			['categorylinks', 'page'],
			'page.page_id',
			$conditions,
			__METHOD__,
			['LIMIT' => $numNeeded],
			['page' => ['INNER JOIN', ['page_id = cl_from' ]]]
		);
		$result = array();
		foreach ( $res as $row ) {
			$result[] = $row;
		}
		return $result;
	}

	protected function getUnresolvedCategory() {
		$dbr = wfGetDB( DB_REPLICA );
		$table = [ self::TABLE, 'page' ];

		$cond = ['resolved = 0', 'page.page_id = category_article_votes.page_id'];
		$ignore = array();
		foreach ( $this->skipCats as $cat ) {
			$ignore[] = $dbr->addQuotes( $cat );
		}
		$ignore = implode( ",", $ignore );
		$cond[] = "cat_slug NOT IN ($ignore)";
		$row = $dbr->selectRow( $table, 'cat_slug', $cond, __METHOD__ );
		$cat = Title::newFromText($row->cat_slug, NS_CATEGORY);
		return $cat;
	}

	public function getRemainingCount() {
		$dbr = wfGetDB( DB_REPLICA );
		$tables = [ self::TABLE, 'page' ];

		$cond = [
			'page.page_id = '.self::TABLE.'.page_id',
			'resolved' => 0
		];

		$ignore = [];
		foreach ( $this->skipCats as $cat ) {
			$ignore[] = $dbr->addQuotes( $cat );
		}
		$ignore = implode( ",", $ignore );
		if (!empty($ignore)) $cond[] = "cat_slug NOT IN ($ignore)";

		$count = $dbr->selectField( $tables, 'count(*)', $cond, __METHOD__ );
		return $count;
	}
}
