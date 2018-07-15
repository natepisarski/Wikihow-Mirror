<?php

class DOMUtil
{
	/**
	 * Hide links to de-indexed articles so web crawlers don't see them, which can hurt SEO.
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

class DOMHelper
{

	private function shouldHideLinks(): bool {
		global $wgTitle, $wgUser;

		return
			// Replace links only for anons...
			$wgUser->isAnon() &&
			// in indexable pages...
			RobotPolicy::isIndexable($wgTitle) &&
			// that haven't been whitelisted
			!ArticleTagList::hasTag('deindexed_link_removal_whitelist', $wgTitle->getArticleID());
	}

	public function hideLinks(string $query)
	{
		if (!$this->shouldHideLinks()) {
			return;
		}

		list($hrefs, $links) = $this->findLinks($query);
		if (!$hrefs) {
			return;
		}

		// Find out which pages are indexed articles

		$res = wfGetDB(DB_SLAVE)->select(
			['page', 'index_info'],
			['page_title'],
			[
				'page_namespace' => 0,
				'page_title' => $hrefs,
				'page_id = ii_page',
				'ii_policy IN (1, 4)',
			]
		);

		$isIndexed = [];
		foreach ($res as $row) {
			$isIndexed[$row->page_title] = true;
		}

		// Replace all other links with their anchor text

		$idx = 0;
		foreach ($hrefs as $href) {
			if (!isset($isIndexed[$href])) {
				$this->hideLink($links[$idx]);
			}
			$idx++;
		}
	}

	/**
	 * Find all links to other pages
	 */
	private function findLinks(string $query): array
	{
		$hrefs = []; // [string]      Relative URLs without the leading '/'
		$links = []; // [DOMElement]  <a> elements

		foreach(pq($query) as $a) {
			$hasImageInLink = pq('img', $a)->length;
			if ($hasImageInLink) {
				continue;
			}

			$title = $a->getAttribute('title');
			$href = $a->getAttribute('href');
			if ($title && $href && strpos($href, '/') === 0 && strpos($href, '.php') === false) {
				$hrefs[] = urldecode(substr($href, 1));
				$links[] = $a;
			}
		}
		return [$hrefs, $links];
	}

	/**
	 * Replace an HTML link with its anchor text
	 */
	private function hideLink(DOMElement $link)
	{
		$pqObject = pq($link);
		$pqObject->replaceWith($pqObject->text());
	}
}
