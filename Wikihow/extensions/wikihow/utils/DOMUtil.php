<?php

class DOMUtil
{
	/**
	 * Hide links to de-indexed articles and categories so web crawlers
	 * don't see them, which can hurt SEO.
	 */
	public static function hideLinksInArticle()
	{
		$query = Misc::isMobileMode() ? 'a' : '#bodycontents a';
		$domHelper = new DOMHelper();
		$domHelper->hideLinks($query);
	}

	/* Unused for now - Alberto, 2018-04-01
	public static function hideLinksInComments()
	{
		$query = '.de_comment a';
		$domHelper = new DOMHelper();
		$domHelper->hideLinks($query);
	}
	*/
}

class DOMHelper {

	public function hideLinks(string $query) {
		if (!$this->shouldHideLinks()) {
			return;
		}
		list($articleLinks, $categoryLinks) = $this->findLinks($query);
		$this->processLinks($articleLinks, $categoryLinks);
		// if (Debugger::$debug) { Debugger::dumpResults(); }
	}

	private function shouldHideLinks(): bool {
		$title = RequestContext::getMain()->getTitle();
		$user = RequestContext::getMain()->getUser();

		return
			// Replace links only for anons...
			$user->isAnon() &&
			// in indexable pages...
			RobotPolicy::isIndexable($title) &&
			// that haven't been whitelisted
			!ArticleTagList::hasTag('deindexed_link_removal_whitelist', $title->getArticleID());
	}

	private function findLinks(string $query): array {
		/**
		 * Each of these arrays have the structure: [ HREF1 => [DOM_LINK1, DOM_LINK2], ... ]
		 * key   string        relative URL without the leading '/' (from href)
		 * value [DOMElement]  each of the links in the HTML sharing that href
		 */
		$articleLinks = [];
		$categoryLinks = [];

		$categPrefix = preg_quote( Misc::getLocalizedNamespace(NS_CATEGORY) . ':' ); // e.g. 'Categoría\:'

		foreach ( pq($query) as $a ) {

			// if (Debugger::$debug) { Debugger::trackLink($a); }

			$isImageLink = preg_match('/\s*image\s*/', $a->getAttribute('class'));
			if ($isImageLink) {
				continue;
			}

			$title = $a->getAttribute('title');
			$href = $a->getAttribute('href');
			$considerLink = ( $title && $href && $href[0] == '/' ); // only include local links
			if ( !$considerLink ) {
				continue;
			}

			$href = urldecode(substr($href, 1)); // Remove trailing '/'
			$href = mb_split('#', $href)[0];
			$chunks = mb_split("$categPrefix", $href);
			if ( count($chunks) == 1 ) {
				$articleLinks[$href][] = $a;
			}
			elseif ( count($chunks) == 2 ) {
				$href = $chunks[1]; // if href is 'Categoría:Otros', $chunks is [ 0=>'', 1=>'Otros' ]
				$categoryLinks[$href][] = $a;
			}
			else { // this should never happen (defensive programming)
				continue;
			}
		}

		return [$articleLinks, $categoryLinks];
	}

	private function processLinks(array $articleLinks, array $categoryLinks) {

		// Find out which pages are indexed

		$dbr = wfGetDB(DB_REPLICA);
		$pageConds = [];
		if ($articleLinks) {
			$pageConds[] = $dbr->makeList( [ 'page_namespace' => NS_MAIN,'page_title' => array_keys($articleLinks) ] , LIST_AND );
		}
		if ($categoryLinks) {
			$pageConds[] = $dbr->makeList( [ 'page_namespace' => NS_CATEGORY, 'page_title' => array_keys($categoryLinks) ] , LIST_AND );
		}
		if (!$pageConds) {
			return;
		}

		$rows = $dbr->select(
			['index_info', 'page'],
			['page_namespace', 'page_title'],
			[
				'ii_page = page_id',
				'ii_namespace = page_namespace',
				'ii_policy IN (1, 4)',
				$dbr->makeList($pageConds, LIST_OR)
			]
		);

		$isIndexed = [];
		foreach ($rows as $row) {
			$ns = (int)$row->page_namespace;
			$title = $row->page_title;
			$isIndexed[$ns][$title] = true;
		}

		// Replace all other links with their anchor text

		$linkGroups = [ NS_MAIN => $articleLinks, NS_CATEGORY => $categoryLinks ];
		foreach ( $linkGroups as $ns => $links ) {
			foreach ( $links as $href => $domLinks ) { // [ HREF1 => [DOM_LINK1, DOM_LINK2], ... ]
				if ( !isset($isIndexed[$ns][$href]) ) {
					foreach ( $domLinks as $domLink ) {
						$this->hideLink($domLink);
						// if (Debugger::$debug) { Debugger::flagHidden($domLink); }
					}
				}
			}
		}
	}

	/**
	 * Replace an HTML link with its anchor text
	 */
	private function hideLink(DOMElement $link) {
		$pqObject = pq($link);
		$pqObject->replaceWith($pqObject->text());
	}

}

/**
 * Dev-only
 */
// class Debugger
// {
// 	public static $debug = false; // IMPORTANT: make sure this flag 'false' in production
// 	private static $links = [];

// 	public static function trackLink(DOMElement $a): void {
// 		$namespaces = RequestContext::getMain()->getLanguage()->getNamespaces();
// 		$categPrefix = preg_quote( $namespaces[NS_CATEGORY].':' ); // e.g. 'Categoría:'
// 		$pqObject = pq($a);

// 		$href = trim( urldecode($a->getAttribute('href')) );
// 		$title = trim( $a->getAttribute('title') );
// 		$anchor = trim( $pqObject->text() );

// 		$isCateg = 2 === count( mb_split("$categPrefix", $href) );
// 		$isImageLink = preg_match('/\s*image\s*/', $a->getAttribute('class'));

// 		$hasTitle = (int) ( !empty($title) );
// 		$hasHref = (int) ( !empty($href) );
// 		$isLocal = (int) ( $href[0] === '/' );
// 		$isDotPhp = (int) ( strpos($href, '.php') !== false );
// 		$isRedLink = (int) ( strpos($href, 'redlink=1') !== false );
// 		$isHidden = 0;

// 		$key = spl_object_id($a);
// 		self::$links[$key] = compact('href', 'title', 'anchor', 'isCateg', 'isImageLink',
// 				'hasTitle', 'hasHref', 'isLocal', 'isDotPhp', 'isRedLink', 'isHidden');
// 	}

// 	public static function flagHidden(DOMElement $a): void {
// 		$key = spl_object_id($a);
// 		self::$links[$key]['isHidden'] = 1;
// 	}

// 	public static function dumpResults(): void {
// 		global $wgTitle;
// 		$dbKey = $wgTitle->getDBkey();

// 		$f = fopen("/tmp/link_hiding.$dbKey.csv", 'w');
// 		$csvHeaders = array_keys( reset(self::$links) );
// 		fputcsv($f, $csvHeaders, "\t", '"');
// 		foreach (self::$links as $info) {
// 			if ( !$info['href'] && !$info['anchor'] && !$info['title'] ) {
// 				continue;
// 			}
// 			$csvRow = array_map(function ($x) { return trim($x); }, $info);
// 			fputcsv($f, $csvRow, "\t", '"');
// 		}
// 		fclose($f);
// 	}
// }
