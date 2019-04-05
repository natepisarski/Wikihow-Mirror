<?php

class Spellchecker extends UnlistedSpecialPage {

	var $skipTool;
	const SPELLCHECKER_EXPIRED = 3600; //60*60 = 1 hour
	const SPCH_AVAIL_IDS_KEY = 'spch_avail_ids';
	const SPCH_IDS_LAST_CHECKED_KEY = 'spch_avail_ids_last_checked1';

	public function __construct() {
		global $wgHooks;
		parent::__construct('Spellchecker');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		global $wgHooks, $wgDebugToolbar;

		$user = $this->getUser();
		$request = $this->getRequest();
		$this->setHeaders();

		$wgHooks['getBreadCrumbs'][] = array('Spellchecker::getBreadCrumbsCallback');

		$out = $this->getContext()->getOutput();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$maintenanceMode = false;
		if ($maintenanceMode) {
			$this->displayMaintenanceMessage($out);
			return;
		}

		$this->skipTool = new ToolSkip("spellchecker", "spellchecker", "sc_checkout", "sc_checkout_user", "sc_page");

		if ( $request->getVal('nextArticle') ) {
			$out->setArticleBodyOnly(true);
			$articleName = $request->getVal('a', "");
			$aid = $request->getVal('aid', 0);
			if ($aid) {
				$t = Title::newfromId($aid);
				if ($t && $t->exists()) {
					$articleName = $t->getText();
				}
			}

			$result = $this->getNextArticle($articleName);

			// if debug toolbar pass logs back in response
			if ($wgDebugToolbar) {
				$result['debug']['log'] = MWDebug::getLog();
			}

			print(json_encode($result));
			return;
		}
		elseif ( $request->getVal('deleteCache') ) {
			if (in_array('staff', $user->getGroups())) {
				self::deleteSpellCheckerCacheKeys();
			}
		}
		elseif ($request->wasPosted()) {
			$out->setArticleBodyOnly(true);
			//user has edited the article from within the Spellchecker tool
			if ( $request->getVal('submit')) {
				$out->setArticleBodyOnly(true);
				// Don't submit any anon edits
				if ($request->getInt('plantId')) {
					$this->savePlantVote();
				} elseif (!$user->isAnon()) {
					$incrementStats = $this->submitEdit();
				}
				$result = $this->getNextArticle();
				// if debug toolbar pass logs back in response
				if ($wgDebugToolbar) {
					$result['debug']['log'] = MWDebug::getLog();
				}

				$result['increment'] = $incrementStats;
				print(json_encode($result));
				return;
			}
		}

		$out->setHTMLTitle(wfMessage('spellcheck')->text());
		$out->setPageTitle(wfMessage('spellcheck')->text());

		$this->addJSAndCSS($out);
		$this->addSpellCheckerTemplateHtml($out);
		$this->addStandingGroups();

		if ( !MobileContext::singleton()->shouldDisplayMobileView() ) {
			$bubbleText = $this->msg( 'spch-tip-bubble' );
			InterfaceElements::addBubbleTipToElement( 'spch-yes', 'spch', $bubbleText );
		}
	}

	// hook to change bread crumbs
	public static function getBreadCrumbsCallback(&$breadcrumb) {
		$mainPageObj = Title::newMainPage();
		$spellchecker = Title::newFromText("Spellchecker", NS_SPECIAL);
		$sep = wfMessage( 'catseparator' )->escaped();
		$breadcrumb = "<li class='home'><a href='{$mainPageObj->getLocalURL()}'>Home</a></li><li>{$sep} <a href='{$spellchecker->getLocalURL()}'>{$spellchecker->getText()}</a></li>";
		return true;
	}

	public static function deleteSpellCheckerCacheKeys() {
		global $wgMemc;
		// Do a set to empty values rather than a delete, since a delete will take longer
		// and prevent a successful memcache cas call in getIds and getNextId
		$wgMemc->set(wfMemcKey(Spellchecker::SPCH_AVAIL_IDS_KEY), array());
		$wgMemc->set(wfMemcKey(Spellchecker::SPCH_IDS_LAST_CHECKED_KEY), 0);
	}

	private function getIds() {
		global $wgMemc;
		$key = wfMemcKey(self::SPCH_AVAIL_IDS_KEY);
		$ids = $wgMemc->get($key, $casToken);
		$newIds = array();
		$success = false;
		if (empty($ids)) {
			$ids = array();
		}

		MWDebug::log("size of ids: " . sizeof($ids));
		// Get 500 more if we drop below 100 available ids to edit
		if (sizeof($ids) < 100) {
			// Only get ids once every 5 minutes max
			$lastCheckedKey = wfMemcKey(self::SPCH_IDS_LAST_CHECKED_KEY);
			$lastChecked = $wgMemc->get($lastCheckedKey);

			MWDebug::log("lastChecked: " . wfTimestamp(TS_MW, $lastChecked));
			MWDebug::log("5 min cutoff: " . wfTimestamp(TS_MW, strtotime("-5 minutes")));
			if (!$lastChecked || intVal($lastChecked) < strtotime("-5 minutes")) {
				$lastChecked = time();
				MWDebug::log('setting lastCheckedKey: ' .  wfTimestamp(TS_MW, $lastChecked));
				$wgMemc->set($lastCheckedKey, $lastChecked);

				MWDebug::log("Getting new spellchecker ids since last check was : " . date($lastChecked));
				$dbr = wfGetDB(DB_REPLICA);
				$expired = wfTimestamp(TS_MW, time() - Spellchecker::SPELLCHECKER_EXPIRED);
				$res = $dbr->select('spellchecker',
					'sc_page',
					array('sc_exempt' => 0, 'sc_errors' => 1, 'sc_dirty' => 0, "sc_checkout < '{$expired}'"),
					__METHOD__,
					array("LIMIT" => 500, "ORDER BY" => "RAND()"));

				$newIds = array();
				foreach ($res as $row) {
					$newIds[] = $row->sc_page;
				}

				$newIds = array_unique(array_merge($ids, $newIds));
				shuffle($newIds);


				MWDebug::log('Setting ids in memcache from getIds(). ids to be set' .  print_r($newIds, true));
				// If cache key hasn't yet been set, set it
				if (empty($ids)) {
					$wgMemc->set($key, $newIds);
				} else {
					$success = $wgMemc->cas($casToken, $key, $newIds);
				}
                // Retry 3 times if we can't successfully cas
				if (!$success) {
					$retries = 0;
					do {
						$wgMemc->get($key, $casToken);
						$success = $wgMemc->cas($casToken, $key, $newIds);
						$retries++;
						MWDebug::log("getIds retry #: " . $retries . ", success? " . $success);
					} while (!$success && $retries < 4);
				}
			}
 		}

		$ids = $success ? $newIds : $ids;
		return array($ids, $casToken);
	}

	private function getNextId(&$retries = 0) {
		global $wgMemc;

		list($ids, $casToken) = $this->getIds();

		// Just return 0 if we get an empty array of ids
		if (empty($ids)) {
			return 0;
		}

		MWDebug::log("ids before pop: " . print_r($ids, true));
		$id = array_pop($ids);
		MWDebug::log("id popped: " . $id);
		MWDebug::log("ids after pop: " . print_r($ids, true));

		$key = wfMemcKey(self::SPCH_AVAIL_IDS_KEY);
		MWDebug::log('setting ids in memcache from getNextId(). ids to be set' . print_r($ids, true));
		// TODO: DO we need to do an initial set if sizeof ids array == 0?
		// TODO: recursion doesn't seem to be working here.  is there something we can fix?
		$success = $wgMemc->cas($casToken, $key, $ids);
		if (!$success) {
			// Set the article id to a non-value since we didn't successfully set the cache key
			$id = 0;
			if ($retries < 4) {
				$retries++;
				MWDebug::log("getNextId retry #: " . $retries);
				return $this->getNextId($retries);
			}
		}
		// If we've  gotten an id and successfully set the array back
		// in memcache, return that id.  Otherwise return 0.
		return $id;
	}

	private function getNextArticle($articleName = '') {
		global $wgOut;

		$dbr = wfGetDB(DB_REPLICA);

		if (class_exists('Plants') && Plants::usesPlants('Spellchecker') ) {
			$plants = new SpellingPlants();
			$next = $plants->getNextPlant();
			if ($next != null) {
				$title = Title::newFromID($next->page);
				if ($title) {
					$revision = Revision::newFromTitle($title, $next->oldid);
					$content['title'] = "<a href='{$title->getFullURL()}' target='new'>" . wfMessage('howto', $title->getText())->text() . "</a>";
					$content['text_title'] = wfMessage('howto', $title->getText())->text();
					$content['articleId'] = $next->page;
					$wordMap = array();
					$wordMap[] = array('misspelled' => $next->word, 'correction' => "", 'key' => $next->word, 'key_count' => 0);
					$content['words'] = $wordMap;
					$content['html'] = $this->getArticleHtml($revision, $title);
					$content['qeurl'] = QuickEdit::getQuickEditUrl($title);
					$content['plantId'] = $next->pqs_id;
					return $content;
				}

			}
		}

        $title = empty($articleName) ?	null : Title::newFromText($articleName);
		if ($title && $title->getArticleID() > 0) {
			$articleId = $title->getArticleID();
		}
		else {
			$articleId = $this->getNextId();
        }

		if ($articleId) {
			$sql = "SELECT * from `spellchecker_page` JOIN `spellchecker_word` ON sp_word = sw_id WHERE sp_page = {$articleId}";
			$res =  $dbr->query($sql, __METHOD__);

			$wordMap = array();
			while ($row = $dbr->fetchObject($res)) {
				$word = $row->sw_word;
				$wordMap[] = array('misspelled' => $word, 'correction' => "", 'key' => $row->sp_key, 'key_count' => $row->sp_key_count);
			}

			if (sizeof($wordMap) > 0) {
				$title = Title::newFromID($articleId);
				if ($title) {
					$revision = Revision::newFromTitle($title, $title->getLatestRevID());
					if ($revision) {
						$content['title'] = "<a href='{$title->getFullURL()}' target='new'>" . wfMessage('howto', $title->getText())->text() . "</a>";
						$content['text_title'] = wfMessage('howto', $title->getText())->text();
						$content['articleId'] = $title->getArticleID();
						$content['words'] = $wordMap;

						$content['html'] = $this->getArticleHtml($revision, $title);
						$content['qeurl'] = QuickEdit::getQuickEditUrl($title);

						$this->skipTool->useItem($articleId);
						return $content;
					}
				}
			} else {
				// Remove from queue if we can't find misspelled words for the article
				$this->markAsIneligible($articleId);
			}
		}
		//return error message
        $content['lastQuery'] = $dbr->lastQuery();
		$eoq = new EndOfQueue();
		$content['error'] = $eoq->getMessage('spl');

		return $content;
	}

	protected function savePlantVote() {
		$request = $this->getRequest();
		$words = $request->getArray('words');
		// the "foreach" loop below would crash if $words array is empty
		// (and not an empty array -- this can happen)
		if (!$words) {
			return;
		}

		$plant = new SpellingPlants();

		$data['pqs_id'] = $request->getInt('plantId');
		foreach ($words as $word) {
			if ($word['correction'] == $word['misspelled']) {
				//they pressed no
				$data['response'] = 0;
			} elseif ($word['correction'] == "") {
				//like pressing skip
				$data['response'] = -1;
			} else {
				$data['response'] = 1;
				$data['correction'] = $word['correction'];
			}
		}
		$plant->savePlantAnswer($data['pqs_id'], $data);
	}

	/*
	 *
	 * Processes an article submit
	 *
	 */
	private function submitEdit() {
		global $wgRequest, $wgUser;
		$user = $this->getContext()->getUser();
		$incrementStats = false;

		$t = Title::newFromID($wgRequest->getInt('articleId'));
		if ($t && $t->exists() && $t->userCan('edit', false)) {

			// Whitelist the article if there was an error sent by the client - meaning no words in the word map
			// were found in the html
			if ($wgRequest->getVal('error', 0)) {
				self::onArticleDemoted($t->getArticleID());
			}
			$wp = WikiPage::factory($t);
			if ($wp && $wp->exists()) {
				$text = ContentHandler::getContentText($wp->getContent());
				$result = $this->replaceMisspelledWords($text);
				if ($result['replaced']) {
					//save the edit
					$summaryMessage = $user->isAnon() ? 'spch-edit-summary-anon' : 'spch-edit-summary';
					$summary = wfMessage($summaryMessage)->text();
					$content = ContentHandler::makeContent( $text, $t );
					$wp->doEditContent($content, $summary, EDIT_UPDATE);
					Hooks::run("Spellchecked", array($wgUser, $t, '0'));
				}

				// Remove article from spellchecker queue if the user
				// whitelisted or replaced at least one word
				if ($result['replaced'] || $result['whitelisted']) {
					// Set this to no errors so it doesn't get sucked back into the spell checker queue until another edit
					$dbw = wfGetDB(DB_MASTER);
					$dbw->update('spellchecker', array('sc_errors' => 0), array('sc_page' => $t->getArticleID()));

					// Add a log entry
					$log = new LogPage( 'spellcheck', false ); // false - dont show in recentchanges, it'll show up for the doEdit call
					$msg = wfMessage('spch-edit-message')->rawParams("[[{$t->getText()}]]")->escaped();
					$entryType = $result['replaced'] ? 'edit' : '';
					$log->addEntry($entryType, $t, $msg, null);
					$incrementStats = true;
				}
				$this->skipTool->unUseItem($wp->getID());
			}
		}

		return $incrementStats;
	}

	private function replaceMisspelledWords(&$text) {
		$request = $this->getRequest();
		$words = $request->getArray('words');
		$whitelistWords = array();
		$replaced = false;
		$whitelisted = false;
		foreach ($words as $word) {
			if ($word['misspelled'] != $word['correction'] && $word['correction'] != "") {
				$replacementKey = str_replace($word['misspelled'], $word['correction'], $word['key']);
				$text = str_replace($word['key'], $replacementKey, $text);
				$replaced = true;
			} elseif ($word['misspelled'] == $word['correction']) {
				$whitelistWords[] = $word['misspelled'];
				$whitelisted = true;
			}
		}

		if (sizeof($whitelistWords) > 0) {
			wikiHowDictionary::batchAddWordsToWhitelist($whitelistWords);
		}
		return array('replaced' => $replaced, 'whitelisted' => $whitelisted);
	}

	public static function onArticleDemoted($id) {
		// remove from spellchecker
		$spellcheckerWhitelist = new SpellcheckerArticleWhitelist();
		$spellcheckerWhitelist->addArticleToWhitelist(Title::newFromID($id));

		return true;
	}

	public static function onMarkNabbed($id) {
		$dbw = wfGetDB(DB_MASTER);

		// upsert does an ON DUPLICATE KEY UPDATE query
		$dbw->upsert( 'spellchecker',
						array( 'sc_page' => $id,
								'sc_timestamp' => $dbw->timestamp(),
								'sc_dirty' => 1,
								'sc_errors' => 0,
								'sc_exempt' => 0
							),
						array( 'sc_page' ),
						array( 'sc_exempt' => 0 ),
						__METHOD__
					);
		return true;

	}
	public static function markAsDirty($id) {
		$dbw = wfGetDB(DB_MASTER);

		$sql = "INSERT INTO spellchecker (sc_page, sc_timestamp, sc_dirty, sc_errors, sc_exempt) VALUES (" .
					$id . ", " . wfTimestampNow() . ", 1, 0, 0) ON DUPLICATE KEY UPDATE sc_dirty = '1', sc_timestamp = " . wfTimestampNow();
		$dbw->query($sql, __METHOD__);
	}

	public static function markAsIneligible($id) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('spellchecker', array('sc_errors' => 0, 'sc_dirty' => 0), array('sc_page' => $id), __METHOD__);
	}

	/**
	 * @param $out
	 */
	private function displayMaintenanceMessage($out) {

		$out->setHTMLTitle(wfMessage('spellchecker')->text());
		$out->setPageTitle(wfMessage('spellchecker')->text());

		$out->addWikiText("This tool is temporarily down for maintenance. Please check out the [[Special:CommunityDashboard|Community Dashboard]] for other ways to contribute while we iron out a few issues with this tool. Happy editing!");
		return;
	}

	/**
	 * @param $revision
	 * @param $title
	 * @return string
	 */
	protected function getArticleHtml($revision, $title) {
		$out = $this->getOutput();
		$popts = $out->parserOptions();
		$popts->setTidy(true);
		$out->setPageTitle($title->getFullText());
		$parserOutput = $out->parse(ContentHandler::getContentText( $revision->getContent() ), $title, $popts);
		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));
		$html = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
		return $html;
	}

	protected function addStandingGroups() {
		$indi = new SpellcheckerStandingsIndividual();
		$indi->addStatsWidget();

		$group = new SpellcheckerStandingsGroup();
		$group->addStandingsWidget();
	}

	/**
	 * @param $out
	 */
	protected function addSpellCheckerTemplateHtml($out) {
		$tmpl = new EasyTemplate(__DIR__);
		$out->addHTML($tmpl->execute('Spellchecker.tmpl.php'));
	}

	/**
	 * @param $wgDebugToolbar
	 * @param $out
	 */
	protected function addJSAndCSS($out) {
		global $wgDebugToolbar;

		WikihowSkinHelper::maybeAddDebugToolbar($out);

		// Note: Mousetrap is needed by the Quick Edit (popupEdit.js)
		$out->addModules('common.mousetrap');
		$out->addHTML(QuickNoteEdit::displayQuickEdit()); // Quick Edit

		// Spellchecker js, css and mw messages
		$out->addModules(
			array('ext.wikihow.spellchecker', 'ext.wikihow.UsageLogs')
		);
	}

}

class Spellcheckerwhitelist extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('Spellcheckerwhitelist');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
			return;
		}

		$isStaff = in_array('staff', $user->getGroups());

		if (!$isStaff) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}


		$dbr = wfGetDB(DB_REPLICA);

		$out->addWikiText(wfMessage('spch-whitelist-inst'));

		$words = array();
		$res = $dbr->select(wikiHowDictionary::WHITELIST_TABLE, "*", '', __METHOD__);
		while ($row = $dbr->fetchObject($res)) {
			$words[] = $row;
		}
		asort($words);

		$res = $dbr->select(wikiHowDictionary::CAPS_TABLE, "*", '', __METHOD__);

		$caps = array();
		while ($row = $dbr->fetchObject($res)) {
			$caps[] = $row->sc_word;
		}
		asort($caps);

		$out->addHTML("<ul>");
		foreach ($words as $word) {
			if ($word->{wikiHowDictionary::WORD_FIELD} != "")
				$out->addHTML("<li>" . $word->{wikiHowDictionary::WORD_FIELD} );
			if ($isStaff && $word->{wikiHowDictionary::USER_FIELD} > 0) {
				$user = User::newFromId($word->{wikiHowDictionary::USER_FIELD});
				$out->addHTML(" (" . $user->getName() . ")");
			}
			$out->addHTML("</li>");
		}

		foreach ($caps as $word) {
			if ($word != "")
				$out->addHTML("<li>" . $word . "</li>");
		}

		$out->addHTML("</ul>");

		$out->setHTMLTitle(wfMessage('spch-whitelist'));
		$out->setPageTitle(wfMessage('spch-whitelist'));
	}
}

class SpellcheckerArticleWhitelist extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('SpellcheckerArticleWhitelist');
	}

	public function execute($par) {
		global $wgOut, $wgUser, $wgRequest;

		if (!in_array('staff', $wgUser->getGroups())) {
			$wgOut->setRobotPolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}


		$this->skipTool = new ToolSkip("spellchecker", "spellchecker", "sc_checkout", "sc_checkout_user", "sc_page");

		$message = "";
		if ( $wgRequest->wasPosted() ) {
			$articleText = $wgRequest->getVal('articleName');
			$title = Title::newFromText($articleText);

			if ($title && $title->getArticleID() > 0) {
				if ($this->addArticleToWhitelist($title))
					$message = $title->getText() . " was added to the article whitelist.";
				else
					$message = $articleText . " could not be added to the article whitelist.";
			}
			else
				$message = $articleText . " could not be added to the article whitelist.";
		}

		$tmpl = new EasyTemplate( __DIR__ );

		$tmpl->set_vars(array('message' => $message));

		$wgOut->addHTML($tmpl->execute('ArticleWhitelist.tmpl.php'));

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select("spellchecker", "sc_page", array("sc_exempt" => 1));

		$wgOut->addHTML("<ol>");
		while ($row = $dbr->fetchObject($res)) {
			$title = Title::newFromID($row->sc_page);

			if ($title)
				$wgOut->addHTML("<li><a href='" . $title->getFullURL() . "'>" . $title->getText() . "</a></li>");
		}
		$wgOut->addHTML("</ol>");

		$wgOut->setHTMLTitle(wfMessage('spch-articlewhitelist'));
		$wgOut->setPageTitle(wfMessage('spch-articlewhitelist'));
	}

	public function addArticleToWhitelist($title) {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "INSERT INTO spellchecker (sc_page, sc_timestamp, sc_dirty, sc_errors, sc_exempt) VALUES (" .
					$title->getArticleID() . ", " . wfTimestampNow() . ", 0, 0, 1) ON DUPLICATE KEY UPDATE sc_exempt = '1', sc_errors = 0, sc_timestamp = " . wfTimestampNow();
		return $dbw->query($sql);
	}
}

class wikiHowDictionary {
	const DICTIONARY_LOC	= "/maintenance/wikihow/spellcheck/custom.pws";
	const WHITELIST_TABLE	= "spellchecker_whitelist";
	const CAPS_TABLE		= "spellchecker_caps";
	const WORD_TABLE		= "spellchecker_word";
	const WORD_FIELD		= "sw_word";
	const USER_FIELD		= "sw_user";
	const VOTES_FIELD		= "sw_votes";
	const ACTIVE_FIELD		= "sw_active";
	const MIN_VOTES			= 5;

	/***
	 *
	 * Takes the given word and, if allowed, adds it
	 * to the temp table in the db to be added
	 * to the dictionary at a later time
	 * (added via cron on the hour)
	 *
	 */
/* seems to no longer be used, 4/2016:
	private static function addWordToWhitelist($word) {
		global $wgUser, $wgMemc;

		$word = strtolower(trim($word));
		$userId = $wgUser->getId();
		// Admins get 2 votes, everyone else gets 1
		$votes = in_array('sysop', $wgUser->getGroups()) ? 2 : 1;


		//now check to see if the word can be added to the library
		//only allow a-z and apostrophe
		//check for numbers
		if ( preg_match("@[^a-z']@", $word) ) {
			return false;
		}
		$dbw = wfGetDB(DB_MASTER);
		$word = $dbw->strencode($word);
		$sql = "INSERT INTO "
			. self::WHITELIST_TABLE
			. " (" . self::WORD_FIELD . "," . self::USER_FIELD . "," . self::ACTIVE_FIELD . "," . self::VOTES_FIELD . ") "
			. " VALUES "
			. " ('$word', $userId, 0, $votes)"
			. " ON DUPLICATE KEY UPDATE"
		 	. " " . self::VOTES_FIELD . " = " . self::VOTES_FIELD . " + $votes";
		$dbw->query($sql);

		$key = wfMemcKey('spellchecker_whitelist');
		$wgMemc->delete($key);

		return true;
	}
*/

	public static function batchAddWordsToWhitelist($words) {
		global $wgUser, $wgMemc;

		$dbw = wfGetDB(DB_MASTER);
		$userId = $wgUser->getId();
		// Admins get 2 votes, everyone else gets 1
		$votes = in_array('sysop', $wgUser->getGroups()) ? 2 : 1;

		$wordsToAdd = array();
		foreach ($words as $word) {
			$word = strtolower(trim($word));

			//now check to see if the word can be added to the library
			//only allow a-z and apostrophe
			//check for numbers
			if (preg_match("@[^a-z'’]@", $word)) {
				continue;
			}
			$word = $dbw->strencode($word);
			$wordsToAdd[] = $word;
		}


		if (!empty($wordsToAdd)) {
			$table = self::WHITELIST_TABLE;

			$keys = array(self::WORD_FIELD, self::VOTES_FIELD);
			$keys = "(" . implode(",", $keys) . ")";

			$values = array();
			foreach ($wordsToAdd as $word) {
				$values[] = "('" . $word . "', $votes)";
			}
			$values = implode(",", $values);

			$sql = "INSERT IGNORE INTO $table $keys VALUES $values";
			$sql .= " ON DUPLICATE KEY UPDATE " . self::VOTES_FIELD . "= $votes + " . self::VOTES_FIELD;
			$dbw->query($sql);

			$key = wfMemcKey('spellchecker_whitelist');
			$wgMemc->delete($key);
		}

		return true;
	}

/* seems to no longer be used, 4/2016:
	public static function addWordsToWhitelist($words) {
		$success = true;

		foreach ($words as $word) {
			$success = self::addWordToWhitelist($word) && $success;
		}

		return $success;
	}
*/

/* seems to no longer be used, 4/2016:
	public static function invalidateArticlesWithWord(&$dbr, &$dbw, $word) {
		//now go through and check articles that contain that word.
		$sql = "SELECT * FROM `" . self::WORD_TABLE . "` JOIN `spellchecker_page` ON `sp_word` = `sw_id` WHERE sw_word = " . $dbr->addQuotes($word);
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			$page_id = $row->sp_page;
			$dbw->update('spellchecker', array('sc_dirty' => "1"), array('sc_page' => $page_id), __METHOD__);
		}
	}
*/

	/***
	 *
	 * Gets a link to the pspell library
	 *
	 */
	public static function getLibrary() {
		global $IP;
		$pspell_config = pspell_config_create("en", 'american');
		pspell_config_mode($pspell_config, PSPELL_FAST);
		//no longer using the custom dictionary
		//pspell_config_personal($pspell_config, $IP . self::DICTIONARY_LOC);
		$pspell_link = pspell_new_config($pspell_config);

		return $pspell_link;
	}

	/***
	 *
	 * Checks the given word using the pspell library
	 * and our internal whitelist
	 *
	 * Returns: -1 if the word is ok
	 *			id of the word in the spellchecker_word table
	 *
	 */
	public static function spellCheckWord(&$dbw, $word, &$pspell, &$wordArray) {

		// Ignore upper-case
		if (strtoupper($word) == $word) {
			return -1;
		}

		//check against our internal whitelist
		// only check lowercase
		$lc = strtolower($word);
		if (isset($wordArray[$lc]) && $wordArray[$lc] === true) {
			return -1;
		}


		//if only the first letter is capitalized, then
		//uncapitalize it and see if its in our list
//		$regWord = lcfirst($word);
//		if ($wordArray[$regWord] === true) {
//			return -1;
//		}

		// Skip word if it has any special characters
		if (strpos($word, "''") !== false
			/*|| preg_match("@^'|'$@", $word)*/
			|| preg_match("/[\[\]\{\}!@#$%^&*(()-+=_:;<>?\"]+/m", $word)) {
			return -1;
		}

		// Ignore numbers
		//if (preg_match('/^[A-Z]*$/',$word)) return;
		if (preg_match('/[0-9]/',$word)) {
			return - 1;
		}

		// Return dictionary words
		if (pspell_check($pspell,$word)) {
			return -1;
		}


		$suggestions = pspell_suggest($pspell,$word);
		$corrections = "";
		if (sizeof($suggestions) > 0) {
			if (sizeof($suggestions) > 5) {
				$corrections = implode(",", array_splice($suggestions, 0, 5));
			} else {
				$corrections = implode(",", $suggestions);
			}
		}

		//first check to see if it already exists
		$id = $dbw->selectField(self::WORD_TABLE, 'sw_id', array('sw_word' => $word), __METHOD__);
		if ($id === false) {
			$dbw->insert(self::WORD_TABLE, array('sw_word' => $word, 'sw_corrections' => $corrections), __METHOD__);
			$id = $dbw->insertId();
		}

		return $id;

	}

	/******
	 *
	 * Returns an array of words that make up our internal whitelist.
	 *
	 ******/
	public static function getWhitelistArray() {
		global $wgMemc;

		$key = wfMemcKey('spellchecker_whitelist');
		$wordArray = $wgMemc->get($key);

		if (!is_array($wordArray)) {
			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select(self::WHITELIST_TABLE,
				'*', array('sw_votes > '. self::MIN_VOTES), __METHOD__);

			$wordArray = array();
			foreach ($res as $word) {
				$wordArray[$word->sw_word] = true;
			}

			$wgMemc->set($key, $wordArray);
		}

		return $wordArray;


	}

	/***
	 *
	 * Returns a string with all the CAPS words in them
	 * to compare against words that are in articles
	 *
	 */
/* seems to be unused, 4/2016:
	public static function getCaps() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(self::CAPS_TABLE, "*", '', __METHOD__);

		$capsString = "";
		while ($row = $dbr->fetchObject($res)) {
			$capsString .= " " . $row->sc_word . " ";
		}

		return $capsString;
	}
*/
}
