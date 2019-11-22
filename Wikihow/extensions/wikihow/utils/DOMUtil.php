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
		$this->doHideLinks($articleLinks, $categoryLinks);
	}

	private function doHideLinks(array $articleLinks, array $categoryLinks) {

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
					}
				}
			}
		}
	}

	private function findLinks(string $query): array {
		/**
		 * Each of these arrays have the structure: [ HREF1 => [DOM_LINK1, DOM_LINK2], ... ]
		 * key   string        relative URL without the leading '/' (from href)
		 * value [DOMElement]  each of the links in the HTML sharing that href
		 */
		$articleLinks = [];
		$categoryLinks = [];

		$namespaces = RequestContext::getMain()->getLanguage()->getNamespaces();
		$categPrefix = preg_quote( $namespaces[NS_CATEGORY].':' ); // e.g. 'Categoría:'

		foreach ( pq($query) as $a ) {
			$isImageLink = preg_match('/\s*image\s*/', $a->getAttribute('class'));
			if ($isImageLink) {
				continue;
			}

			$title = $a->getAttribute('title');
			$href = $a->getAttribute('href');
			$include =
				$title &&
				$href &&
				( $href[0] == '/' ) &&						// only include local links
				(	strpos($href, '.php') === false || 		// ignore index.php links
					strpos($href, 'redlink=1') !== false	// unless they are redlinks
				);
			if ( !$include ) {
				continue;
			}

			$href = urldecode(substr($href, 1)); // Remove trailing '/'
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

	/**
	 * Replace an HTML link with its anchor text
	 */
	private function hideLink(DOMElement $link) {
		$pqObject = pq($link);
		$pqObject->replaceWith($pqObject->text());
	}
}
