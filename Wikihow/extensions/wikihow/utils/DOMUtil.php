<?php

class DOMUtil
{
	/**
	 * Hide links to de-indexed articles and categories so web crawlers
	 * don't see them, which can hurt SEO.
	 */
	public static function hideLinksForAnons()
	{
		$user = RequestContext::getMain()->getUser();
		if ( $user->isLoggedIn() ) {
			return;
		}

		$title = RequestContext::getMain()->getTitle();
		if ( $title->inNamespace(NS_USER) ) {
			DomHelper::hideLinksInUserPage();
			return;
		}

		$aid = $title->getArticleID();
		$whitelist = 'deindexed_link_removal_whitelist';
		if ( RobotPolicy::isIndexable($title) && !ArticleTagList::hasTag($whitelist, $aid) ) {
			$query = Misc::isMobileMode() ? 'a' : '#bodycontents a';
			DomHelper::hideLinks($query);
		}
	}

}

class DOMHelper {

	public static function hideLinksInUserPage() {
		foreach ( pq('div.mw-parser-output a') as $a ) {
			if ( self::isImageLink($a) ) {
				continue;
			}
			self::hideLink($a);
		}
	}

	public static function hideLinks(string $query) {
		list($articleLinks, $categoryLinks) = self::findLinks($query);
		self::processLinks($articleLinks, $categoryLinks);
	}

	private static function isImageLink(DOMElement $link): bool {
		return preg_match('/\s*image\s*/', $link->getAttribute('class'));
	}

	/**
	 * Replace an HTML link with its anchor text
	 */
	private function hideLink(DOMElement $link) {
		$pqObject = pq($link);
		$pqObject->replaceWith($pqObject->text());
	}

	private static function findLinks(string $query): array {
		/**
		 * Each of these arrays have the structure: [ HREF1 => [DOM_LINK1, DOM_LINK2], ... ]
		 * key   string        relative URL without the leading '/' (from href)
		 * value [DOMElement]  each of the links in the HTML sharing that href
		 */
		$articleLinks = [];
		$categoryLinks = [];

		$categPrefix = preg_quote( Misc::getLocalizedNamespace(NS_CATEGORY) . ':' ); // e.g. 'Categoría\:'

		foreach ( pq($query) as $a ) {

			if ( self::isImageLink($a) ) {
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

	private static function processLinks(array $articleLinks, array $categoryLinks) {

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
						self::hideLink($domLink);
					}
				}
			}
		}
	}

}
