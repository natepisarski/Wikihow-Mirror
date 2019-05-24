<?php

require_once __DIR__ . '/../TranslationLink.php';

/**
 * Modifies the edit page to ask for a URL to translate, fetches content to translate and tracks
 * translations between languages in teh database.

CREATE TABLE `pre_translation_link` (
  `ptl_translator` int(11) NOT NULL,
  `ptl_to_title` varchar(255) NOT NULL,
  `ptl_english_aid` int(11) NOT NULL,
  `ptl_timestamp` varchar(14) NOT NULL,
  PRIMARY KEY  (`ptl_translator`,`ptl_to_title`)
)
 */

class TranslateEditor extends UnlistedSpecialPage {
	const TOOL_NAME = "TRANSLATE_EDITOR";

	// These templates will be removed when we translate
	function __construct() {
		parent::__construct('TranslateEditor');
	}
	/**
	 * Check if the user is a translater, and return true if a translator and false otherwise
	 */
	static function isTranslatorUser() {
		global $wgUser, $wgLanguageCode;

		$userGroups = $wgUser->getGroups();

		//if ($wgUser->getID() == 0 || (!(in_array('translator', $userGroups) ) )) {
		$translatorAcct = wfMessage('translator_account')->text();
		if ($wgUser->getID() == 0
			|| ( !in_array('translator', $userGroups)
				&& strcasecmp($wgUser->getName(), $translatorAcct) != 0 )
			|| $wgLanguageCode == "en"
		) {
			return false;
		} else {
			return true;
		}
	}
	/**
	 * Regex for matching section names to replace
	 */
	static function getSectionRegex($sectionName) {
		return("== *" . $sectionName . " *==");
	}
	/**
	 * Name of section name to change them to
	 */
	static function getSectionWikitext($sectionName) {
		return("== " . $sectionName . " ==");
	}
	/**
	 * Get an array of values from messages, that are newline seperated
	 */
	static function getMsgArray($msg) {
		$arr = preg_split("@[\r\n]+@", wfMessage($msg)->plain());
		// Remove empty elements at end
		$last = sizeof($arr) - 1;
		while ($last >= 0 && !$arr[$last]) {
			unset($arr[$last]);
			$last--;
		}

		return($arr);
	}
	/**
	 * Called when the user goes to an edit page
	 * Override the functionality of the edit to require a URL to translate
	 */
	static function onCustomEdit() {
		global $wgRequest, $wgOut, $wgLanguageCode, $wgUser;

		$draft = $wgRequest->getVal('draft', null);
		$target = $wgRequest->getVal('title', null);
		$action = $wgRequest->getVal('action', null);
		$section = $wgRequest->getVal('section',$wgRequest->getVal('wpSection',null));
		$save = $wgRequest->getVal('wpSave',null);
		$title = Title::newFromURL($target);

		Mustache_Autoloader::register();
		$options =  ['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)];
		$m = new Mustache_Engine($options);
		$vars = [
			'remove_templates' => json_encode( array_map(preg_quote, self::getMsgArray('remove_templates')) ),
			'remove_sections'  => json_encode( array_map(preg_quote, self::getMsgArray('remove_sections')) ),
			'sources_name' => json_encode(wfMessage('Sources')->text()),
			'steps_name' => json_encode(wfMessage('Steps')->text()),
			'references_name' => json_encode(wfMessage('references')->text())
		];

		// We have the dialog to enter the URL when we are adding a new article, and have no existing draft.
		if ($title && $title->inNamespace(NS_MAIN) && self::isTranslatorUser()) {

			if ($draft == null
				&& (!$title->exists() || CreateEmptyIntlArticle::isEligibleToTranslate($title, $wgLanguageCode, $wgUser))
				&& $action=='edit'
			) {

				// Templates to remove from translation
				// Words or things to automatically translate
				$translations = array(array('from' => self::getSectionRegex('Steps'), 'to' => self::getSectionWikitext(wfMessage('Steps'))),
					array('from' => self::getSectionRegex('Tips'), 'to' => self::getSectionWikitext(wfMessage('Tips'))),
					array('from' => self::getSectionRegex('Warnings'), 'to' => self::getSectionWikitext(wfMessage('Warnings'))),
					array('from' => self::getSectionRegex('Ingredients'), 'to' => self::getSectionWikitext(wfMessage('Ingredients'))),
					array('from' => self::getSectionRegex("Things You'll need"), 'to' => self::getSectionWikitext(wfMessage('Thingsyoullneed'))),
					array('from' => self::getSectionRegex("Sources and Citations"), 'to' => self::getSectionWikitext(wfMessage('Sources'))),
					array('from' => self::getSectionRegex("References"), 'to' => self::getSectionWikitext(wfMessage('References'))),
					array('from' => '\[\[Category:[^\]]+\]\]', 'to' => "")
				);
				$vars = array_merge( $vars, [
					'title' => $target,
					'checkForLL' => true,
					'translateURL' => true,
					'translations' => json_encode($translations),
				]);
				if($title->exists()) {
					$dbr = wfGetDB(DB_REPLICA);
					$wikiText = Wikitext::getWikitext($dbr, $title);
					$summary = Wikitext::getSummarizedSection($wikiText);
					if($summary != "") {
						$vars['translationExists'] = true;
						$vars['translatedSummary'] = wfMessage("summary_section_notice")->text() . "\n" . $summary;
					}
				}

				$html = $m->render('TranslateEditor.mustache', $vars);
				$wgOut->addHTML($html);
				QuickEdit::showEditForm($title);
				return false;
			}
			elseif ($section == null && $save == null) {
				$vars = array_merge( $vars, ['title'=>$target, 'checkForLL'=>true, 'translateURL'=>false] );
				$html = $m->render('TranslateEditor.mustache', $vars);
				$wgOut->addHTML($html);
				QuickEdit::showEditForm($title);
				return false;
			}
		}
		return true;
	}
	/**
	 * EXECUTE
	 **/
	function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;
		$user = $this->getUser();
		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}
		$userGroups = $user->getGroups();

		if (!self::isTranslatorUser()) {
			$wgOut->setRobotPolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$action = $wgRequest->getVal('action', null);
		$target = $wgRequest->getVal('target', null);
		$toTarget = $wgRequest->getVal('toTarget', null);

		if ($action == "getarticle") {
			$this->startTranslation($target, $toTarget);
		}
	}

	/**
	 * Use API.php to get information about the article on English
	 * This is done so the code can run properly on international wikis
	 */
	static function getArticleRevisionInfo($target) {

		$url = "https://www.wikihow.com/api.php?action=query&prop=revisions&titles="
			 . urlencode($target) . "&rvprop=content|ids&format=json";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$text = curl_exec($ch);
		curl_close($ch);

		return($text);
	}
	/**
	 * Start a translation by fetching the article to be translated,
	 * and logging it.
	 */
	function startTranslation($fromTarget, $toTarget) {
		global $wgOut, $wgRequest, $wgLanguageCode, $wgUser;
		$target = urldecode($fromTarget);
		$text = self::getArticleRevisionInfo($target);
		$output = array();
		$wgOut->setArticleBodyOnly(true);
		$json = json_decode($text, true);
		$ak = array_keys($json['query']['pages']);
		$fromAID = intVal($ak[0]);
		$translationExists = false;
		$stubExists = false;
		//first check to see if there's a translation started, and if so, is it the same article
		$toTitle = Title::newFromText(urldecode($toTarget));
		if($toTitle && $toTitle->getArticleID() > 0) {
			$links = TranslationLink::getLinks("en", $wgLanguageCode, ['tl_to_aid = ' . $toTitle->getArticleID()]);
			foreach($links as $link) {
				if($link->fromAID != $fromAID) {
					$translationExists = true;
				} else {
					if($link->isTranslated == TranslationLink::TL_TRANSLATED) {
						$translationExists = true;
					}
				}
			}
		}

		//The article we are translating exists
		if ($fromAID > 0 ) {
			$links = TranslationLink::getLinksTo("en", $fromAID, true);
			foreach ($links as $link) {
				if($link->toLang == $wgLanguageCode) {
					if ($link->isTranslated == TranslationLink::TL_STUBBED && $toTitle && $link->toAID != $toTitle->getArticleID()) {
						$stubExists = true;
						$stubTitle = $link->toURL;
						$translationExists = true;
					} elseif ($link->isTranslated == TranslationLink::TL_STUBBED && !$toTitle) {
						$stubExists = true;
						$stubTitle = $link->toURL;
						$translationExists = true;
					} elseif ($link->isTranslated == TranslationLink::TL_TRANSLATED) {
						$translationExists = true;
					}
				}
			}
			if (!$translationExists) {
				$fromRevisionId = $json['query']['pages'][$fromAID]['revisions'][0]['revid'];
				$txt = $json['query']['pages'][$fromAID]['revisions'][0]['*'];
				if (preg_match("/#REDIRECT/",$txt)) {
					$output['error'] = "It seems the article you are attempting to translate is a redirect. Please contact your project manager.";
					$output['success'] = false;
				}
				else {
					$txt = self::replaceInternalLinks($txt);

					$page_title = $json['query']['pages'][$fromAID]['title'];
					$txt = self::removeSummary($txt, $page_title);

					$output['success'] = true;
					$output['aid'] = $fromAID;
					$output['text'] = $txt;

					$dbw = wfGetDB(DB_MASTER);
					$sql = 'insert into pre_translation_link(ptl_translator, ptl_english_aid, ptl_to_title, ptl_timestamp) values(' . $dbw->addQuotes($wgUser->getId()) . ',' . $dbw->addQuotes($fromAID) . ',' . $dbw->addQuotes($toTarget) . ',' . $dbw->addQuotes(wfTimestampNow()) .  ') on duplicate key update ptl_english_aid=' . $dbw->addQuotes($fromAID) . ', ptl_timestamp=' . $dbw->addQuotes(wfTimestampNow());
					$dbw->query($sql, __METHOD__);
					TranslationLink::writeLog(TranslationLink::ACTION_NAME, 'en', $fromRevisionId, $fromAID,$target,$wgLanguageCode,$toTarget);
				}
			} else {
				if($stubExists) {
					$output['warning'] = wfMessage('translation_stub_warning', $stubTitle)->parse();
				} else {
					$output['error'] = "It seems the article was already translated. Please contact your project manager.";
				}
				$output['success'] = false;
			}
		}
		else {
			$output['success'] = false;
			$output['error'] = "No article at given URL. Please contact your project manager.";
		}
		$wgOut->addHTML(json_encode($output));
	}
	/**
	 * Check if URL exists and is not a redirect
	 */
	static function checkUrl($url) {
		$pages = Misc::getPagesFromURLs(array($url));
		foreach ($pages as $u => $p) {
			if ($p['page_is_redirect'] == 0 && $p['page_namespace'] == NS_MAIN) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Check if there is a link between two article ids
	 * If so, return true otherwise return false
	 */
	static function isLink($langA, $aidA, $langB, $aidB) {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select count(*) as ct from language_links where (ll_from_lang=" . $dbr->addQuotes($langA) . " AND ll_from_aid=" . $dbr->addQuotes($aidA) . " AND " . "ll_to_lang=" . $dbr->addQuotes($langB) . " AND ll_to_aid=" . $dbr->addQuotes($aidB) . ") OR "
		. "(ll_from_lang=" . $dbr->addQuotes($langB) . " AND ll_from_aid=" . $dbr->addQuotes($aidB) . " AND " . "ll_to_lang=" . $dbr->addQuotes($langA) . " AND ll_to_aid=" . $dbr->addQuotes($aidA) . ")";

		$res = $dbr->query($sql, __METHOD__);
		$row = $dbr->fetchObject($res);
		if ($row->ct == 1) {
			return true;
		}
		else {
			return false;
		}
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
	private static function replaceInternalLinks(string $wikitext): string {
		global $wgLanguageCode;

		// Extract internal links from the English wikitext

		$href = '[^[|]+';
		$anchor = '[^[]+';
		$regex = "/\[\[($href)\|($anchor)\]\]/"; // Adapted from Parser.php#stripSectionName()
		preg_match_all($regex, $wikitext, $matches, PREG_SET_ORDER);

		$langFactory = Language::factory('en');
		$linksByTitle = []; // Array keys are page.page_title
		foreach ($matches as $match) {
			$markup = $match[0];
			$href = urldecode($match[1]);
			$anchor = $match[2];
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
					$wikitext = str_replace($markup, "[[$intlURL|$anchor]]", $wikitext);
				} else { // Replace the entire link markup with its anchor text
					$wikitext = str_replace($markup, $anchor, $wikitext);
				}
			}
		}
		return $wikitext;
	}

	private static function removeSummary(string $wikitext, string $page_title): string {
		$namespace = MWNamespace::getCanonicalName(NS_SUMMARY);

		$page_dbkey = str_replace(' ','-',$page_title);
		$title_regex = '('.preg_quote($page_title).'|'.preg_quote($page_dbkey).')';

		$regex = 	'\<!--.*--\>\n'.	//comment
							'==.*==\n'.	//header
							'({{whvid.*}}\n)?'.	//optional video summary
							'{{'.$namespace.':'.$title_regex.'}}';	//summary

		$wikitext = preg_replace('/'.$regex.'/i', '', $wikitext);
		return trim($wikitext);
	}
}
