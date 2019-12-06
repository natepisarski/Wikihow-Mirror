<?php

class EditorUtil {

	/**
	 * Regex for matching section names to replace
	 */
	public static function getSectionRegex($sectionName) {
		return("== *" . $sectionName . " *==");
	}

	/**
	 * Name of section name to change them to
	 */
	public static function getSectionWikitext($sectionName) {
		return("== " . $sectionName . " ==");
	}

	/**
	 * Use API.php to get information about the article on English
	 * This is done so the code can run properly on international wikis
	 */
	private static function getArticleInfo(array $params): string {
		$params = array_merge([
			'action' => 'query',
			'prop' => 'revisions',
			'rvprop' => 'content|ids',
			'format' => 'json',
		], $params);

		$url = "https://www.wikihow.com/api.php?" . http_build_query($params);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$text = curl_exec($ch);
		curl_close($ch);

		return($text);
	}

	public static function getArticleInfoByName(string $name): string {
		return self::getArticleInfo([ 'titles' => $name ]);
	}

	public static function getArticleInfoById(int $aid): string {
		return self::getArticleInfo([ 'pageids' => $aid ]);
	}

	/**
	 * For each internal link on the original EN article, either:
	 * (a) make the link point to the equivalent INTL article if it exists and is indexable, or
	 * (b) replace the link markup with its anchor text otherwise
	 *
	 * E.g. with '[[Build-Muscle|build muscle]]' in the EN article, the NL output would be:
	 * (a) '[[Spieren-opbouwen|build muscle]]' if the NL article exists, or
	 * (b) 'build muscle' if it doesn't
	 *
	 * @param  string $wikitext Markup for the entire article
	 * @return string           The given $wikitext with links replaced
	 */
	public static function replaceInternalLinks(string $wikitext): string {
		global $wgLanguageCode;

		// Extract internal links from the English wikitext

		// This regex matches both [[Some-Title|anchor text]] and [[Some-Title]]
		$txt = "([^\[\]\|]+)";
		$regex = "/\[\[ $txt (?: \| $txt )? \]\]/x";
		preg_match_all($regex, $wikitext, $matches, PREG_SET_ORDER);

		$langFactory = Language::factory('en');
		$linksByTitle = []; // Array keys are page.page_title
		foreach ($matches as $match) {
			$markup = $match[0];
			$href = urldecode($match[1]);
			$anchor = $match[2] ?? '';
			if (strpos($href, 'Image:') === 0 || strpos($href, ':Category') === 0)
				continue;

			$title = Title::makeTitleSafe(NS_MAIN, $href);
			if (!$title)
				continue;

			$dbKey = $langFactory->ucfirst($title->getDBkey());
			// There may be multiple links to the same page but with different anchor texts
			$linksByTitle[$dbKey][] = compact('markup', 'href', 'anchor');
		}

		if (!$linksByTitle)
			return $wikitext;

		// Fetch the English page IDs from the database

		$dbr = wfGetDB(DB_REPLICA);
		$tables = Misc::getLangDB('en') . '.page';
		$fields = ['page_id', 'page_title'];
		$where = ['page_namespace' => NS_MAIN, 'page_title' => array_keys($linksByTitle)];
		$res = $dbr->select($tables, $fields, $where);

		$linksByID = []; // Array keys are page.page_id
		foreach ($res as $row) {
			$linksByID[$row->page_id] = $linksByTitle[$row->page_title];
		}

		if (!$linksByID)
			return $wikitext;

		// Find article translations in the current language and whether they are indexable

		$where = [
			"d.page_namespace = 0",
			"d.page_id IN (" . $dbr->makeList(array_keys($linksByID)) . ')'
		];
		$transLinks = TranslationLink::getLinks('en', $wgLanguageCode, $where);
		foreach ($transLinks as $transLink) {
			$intlTitle = Title::newFromID($transLink->toAID);
			if (!RobotPolicy::isTitleIndexable($intlTitle))
				continue;
			$intlURL = $intlTitle->getPartialURL();
			foreach ($linksByID[$transLink->fromAID] as &$link) {
				$link['intl_url'] = $intlURL;
			}
		}
		unset($link);

		// Replace the links when available

		foreach ($linksByID as $links) {
			foreach ($links as $link) {
				$markup = $link['markup'];
				$anchor = $link['anchor'];
				$intlURL = $link['intl_url'] ?? null;
				if ($intlURL) { // Make the link point to the equivalent INTL article
					$anchor = $anchor ? "|$anchor" : '';
					$wikitext = str_replace($markup, "[[{$intlURL}{$anchor}]]", $wikitext);
				} else { // Replace the entire link markup with its anchor text
					$wikitext = str_replace($markup, $anchor, $wikitext);
				}
			}
		}
		return $wikitext;
	}

	/**
	 * Preserve existing category tags from the INTL article, and remove any EN category tags
	 */
	public static function replaceCategories(string $enWikiText, string $intlWikiText): string
	{
		// Remove EN category tags

		$enRegex = "/\[\[Category:[^\]]+\]\]/";
		preg_match_all($enRegex, $enWikiText, $enMatches);
		foreach ($enMatches[0] as $categTag) {
			$categTag = preg_quote($categTag) . '\s*\n?';
			$enWikiText = preg_replace("/$categTag/", '', $enWikiText);
		}

		// Add INTL category tags

		$intlCateg = preg_quote( Misc::getLocalizedNamespace(NS_CATEGORY) );
		$intlRegex = "/\[\[(?:Category|$intlCateg):[^\]]+\]\]/";
		preg_match_all($intlRegex, $intlWikiText, $intlMatches);
		if ($intlMatches[0]) {
			$categTags = implode("\n", $intlMatches[0]);
			$enWikiText = $categTags . "\n" . $enWikiText;
		}

		return $enWikiText;
	}

	public static function getSummary(string $wikiText, string $dbKey): string {
		$ns = MWNamespace::getCanonicalName(NS_SUMMARY);
		$regex = '(' . preg_quote($dbKey) . '|' . preg_quote($dbKey) . ')';

		$regex = '<!--.*-->\n' 						// comment
			   . '==.*==\n' 						// header
			   . '({{whvid.*}}\n)?' 				// optional video summary
			   . '{{' . $ns . ':' . $regex . '}}';	// summary

		preg_match("/$regex/i", $wikiText, $matches);
		return $matches[0] ?? '';
	}

	public static function removeSummary(string $wikiText, string $dbKey): string {
		$summary = self::getSummary($wikiText, $dbKey);
		if ( $summary ) {
			$wikiText = str_replace($summary, '', $wikiText);
		}
		return trim($wikiText);
	}

	/**
	 * Get an array of values from messages, that are newline seperated
	 */
	public static function getMsgArray($msg) {
		$arr = preg_split("@[\r\n]+@", wfMessage($msg)->plain());
		// Remove empty elements at end
		$last = sizeof($arr) - 1;
		while ($last >= 0 && !$arr[$last]) {
			unset($arr[$last]);
			$last--;
		}

		return($arr);
	}

	public static function getSectionTranslations(): array {
		return [
			[ 'from' => self::getSectionRegex('Steps'), 'to' => self::getSectionWikitext(wfMessage('Steps')) ],
			[ 'from' => self::getSectionRegex('Tips'), 'to' => self::getSectionWikitext(wfMessage('Tips')) ],
			[ 'from' => self::getSectionRegex('Warnings'), 'to' => self::getSectionWikitext(wfMessage('Warnings')) ],
			[ 'from' => self::getSectionRegex('Ingredients'), 'to' => self::getSectionWikitext(wfMessage('Ingredients')) ],
			[ 'from' => self::getSectionRegex("Things You'll Need"), 'to' => self::getSectionWikitext(wfMessage('Thingsyoullneed')) ],
			[ 'from' => self::getSectionRegex("Things You’ll Need"), 'to' => self::getSectionWikitext(wfMessage('Thingsyoullneed')) ],
			[ 'from' => self::getSectionRegex("Sources and Citations"), 'to' => self::getSectionWikitext(wfMessage('Sources')) ],
			[ 'from' => self::getSectionRegex("References"), 'to' => self::getSectionWikitext(wfMessage('References')) ],
		];
	}
}
