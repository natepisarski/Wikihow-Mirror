<?php

class Categorizer extends UnlistedSpecialPage {

	var $pageIdsKey = null;
	var $inUseKey = null;
	var $skippedKey = null;
	var $editPage = false;
	var $noMoreArticlesKey = null;
	var $oneHour = 0;
	var $halfHour = 0;
	var $oneWeek = 0;

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'Categorizer' );

		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');

		$userId = $this->getUser()->getId();
		$this->pageIdsKey = wfMemcKey("cattool_pageids1");
		$this->inUseKey = wfMemcKey("cattool_inuse");
		$this->skippedKey = wfMemcKey("cattool_{$userId}_skipped");
		$this->noMoreArticlesKey = wfMemcKey("cattool_nomore1");

		$this->halfHour = 60 * 30;
		$this->oneHour = 60 * 60;
		$this->oneWeek = 60 * 60 * 24 * 7;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();


		$out->setRobotPolicy( 'noindex,nofollow' );
		$user = $this->getUser();
		if ($user->getId() == 0) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		# Check blocks
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$action = $req->getVal('a', 'default');
		$pageId = $req->getVal('id', -1);
		switch ($action) {
			case 'default':
				$t = $this->getNext();
				$this->display($t);
				break;
			case 'editpage':
				$this->editPage = true;
				$t = Title::newFromId($pageId);
				$this->display($t);
				break;
			case 'complete':
				$out->setArticleBodyOnly(true);
				$this->complete($pageId);
				$t = $this->getNext();
				$vars['head'] = $this->getHeadHtml($t);
				$vars['article'] = $this->getArticleHtml($t);
				print(json_encode($vars));
				break;
			case 'skip':
				$out->setArticleBodyOnly(true);
				$this->skip($pageId);
				$t = $this->getNext();
				$vars['head'] = $this->getHeadHtml($t);
				$vars['article'] = $this->getArticleHtml($t);
				print(json_encode($vars));
				break;
			case 'view':
				$t = Title::newFromId($pageId);
				$this->display($t);
				break;
		}
	}

	/** Get javascript messages to add for Categorizer tool
	 */
	private  function getJSMsgs() {
		$msgs = array('cat_sorry_label');
		return $msgs;
	}

	private function getNext() {
		global $wgMemc;

		$t = null;
		$noMoreArticles = $wgMemc->get($this->noMoreArticlesKey);
		if (!$noMoreArticles) {
			do {
				$pageId = $this->getNextArticleId();
				$t = Title::newFromId($pageId);
			} while ($pageId != -1 && (!$t || !$t->exists()));
		}

		return $t;
	}

	private function getNextArticleId() {
		global $wgMemc;
		$langCode = $this->getLanguage()->getCode();
		$key = $this->pageIdsKey;
		$pageIds = $wgMemc->get($key);
		if (!$pageIds || $this->fetchMoreArticleIds()) {
			$pageIds = $this->getUncategorizedPageIds();
			$wgMemc->set($key, $pageIds, $this->oneWeek);
			// Remove old inuse article ids
			$wgMemc->set($this->inUseKey, array(), $this->halfHour);
		}
		$pageId = -1;
		foreach ($pageIds as $page) {
			try {
				if (!$this->skipped($page) && !$this->inUse($page) && ($langCode != "en" || GoodRevision::patrolledGood(Title::newFromId($page)) ) ) {
					$this->markInUse($page);
					$pageId = $page;
					break;
				}
			} catch (Exception $e) {
				$this->skip($page);
				continue;
			}
		}
		return $pageId;
	}

	private function fetchMoreArticleIds() {
		global $wgMemc;
		$ret = false;
		$pageIds = $wgMemc->get($this->pageIdsKey);
		$inUseIds = $wgMemc->get($this->inUseKey);
		$diff = array();
		if (is_array($pageIds) && is_array($inUseIds)) {
			$diff = array_diff($pageIds, $inUseIds);
		}
		if (empty($diff)) {
			$ret = true;
		}
		return $ret;
	}

	private function skip($pageId) {
		global $wgMemc;
		$key = $this->skippedKey;
		$val = $wgMemc->get($key);
		if (is_array($val)) {
			$val[] = $pageId;
		} else {
			$val = array($pageId);
		}
		$wgMemc->set($key, $val, $this->oneWeek);
		$this->unmarkInUse($pageId);
	}

	private function skipped($pageId) {
		global $wgMemc;
		$key = $this->skippedKey;
		$val = $wgMemc->get($key);
		return $val ? in_array($pageId, $val) : false;
	}

	private function inUse($pageId) {
		global $wgMemc;
		$key = $this->inUseKey;
		$val = $wgMemc->get($key);
		return $val ? in_array($pageId, $val) : false;
	}

	private function unmarkInUse($page) {
		global $wgMemc;
		$key = $this->inUseKey;
		// Remove from page ids
		$pageIds = $wgMemc->get($key);
		if ($pageIds) {
			foreach ($pageIds as $k => $pageId) {
				if ($page == $pageId) {
					unset($pageIds[$k]);
					$wgMemc->set($key, $pageIds, $this->halfHour);
					break;
				}
			}
		}
	}

	private function markInUse($pageId) {
		global $wgMemc;
		$key = $this->inUseKey;
		$val = $wgMemc->get($key);
		if ($val) {
			// Throw an exception if someone else has marked this in use
			if (in_array($pageId, $val)) {
				throw new Exception("pageId in use: $pageId");
			}
			$val[] = $pageId;
		} else {
			$val = array($pageId);
		}
		$wgMemc->set($key, $val, $this->halfHour);
	}

	private function display(&$t) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		$vars = array();
		$this->setVars($vars, $t);
		$out->setArticleBodyOnly($this->editPage);

		if (!$this->editPage) {
			$out->addModules('ext.wikihow.categorizer');
		}
		$msgs = $this->getJSMsgs($msgs);
		$out->addHTML(Wikihow_i18n::genJSMsgs($msgs));

		$vars['article'] = $this->getArticleHtml($t);
		EasyTemplate::set_path(__DIR__.'/');
		$html = $this->editPage ? EasyTemplate::html('Categorizer_editpage.tmpl.php', $vars) : EasyTemplate::html('Categorizer.tmpl.php', $vars);
		$out->addHtml($html);
		$this->displayLeaderboards();
		$out->setHTMLTitle(wfMessage('cat_app_name'));
		$out->setPageTitle(wfMessage('cat_app_name'));
	}

	private function getArticleHtml($t) {
		$out = $this->getOutput();

		if ($t) {
			$popts = $out->parserOptions();
			$popts->setTidy(true);
			$revision = Revision::newFromTitle($t);
			if ($revision) {
				$parserOutput = $out->parse(ContentHandler::getContentText( $revision->getContent() ), $t, $popts);
				$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));
				return WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
			}
		}

		//still here? end of queue
		$eoq = new EndOfQueue();
		$html = $eoq->getMessage('cat');
		return $html;
	}

	private function getHeadHtml(&$t, &$vars = array()) {
		if ($t && $t->exists()) {
			$vars['cats'] = $this->getCategoriesHtml($t);
			$vars['pageId'] = $t->getArticleId();
			$sk = $this->getSkin();
			$vars['title'] = $t->getText();
			$vars['titleUrl'] = "/" . urlencode(htmlspecialchars_decode(urldecode($t->getPartialUrl())));
			$vars['intro'] = $this->getIntroText($t);
		} else {
			// No title to display. See Categorizer::getNext()
			$vars['pageId'] = -1;
			return '<div id="cat_aid">'.$vars['pageId'].'</div>';
		}
		$vars['cat_help_url'] = wfMessage('cat_help_url');
		EasyTemplate::set_path(__DIR__.'/');
		return EasyTemplate::html('Categorizer_head.tmpl.php', $vars);
	}

	private function getIntroText($t) {
		global $wgParser;
		$r = Revision::newFromTitle($t);
		if ($r) {
			$intro = $wgParser->getSection(ContentHandler::getContentText( $r->getContent() ), 0);
			return Wikitext::flatten($intro);
		} else {
			return '';
		}
	}

	private function setVars(&$vars, &$t) {
		$vars['cat_head'] = $this->getHeadHtml($t, $vars);
		$vars['cat_help_url'] = wfMessage('cat_help_url');
		$vars['tree'] = json_encode(CategoryInterests::getCategoryTreeArray());
		$vars['cat_search_label'] = wfMessage('cat_search_label');
		$vars['cat_subcats_label'] = wfMessage('cat_subcats_label');
		if ($this->editPage) {
			$vars['js'] = HtmlSnips::makeUrlTag('/extensions/wikihow/cattool/categorizer.js');
		}
	}

	private function displayLeaderboards() {
		if (!$this->editpage) {
			$stats = new CategorizationStandingsIndividual();
			$stats->addStatsWidget();
			$standings = new CategorizationStandingsGroup();
			$standings->addStandingsWidget();
		}
	}

	private function getCategoriesHtml(&$t) {
		$html = "";
		$cats = array_reverse($this->getCategories($t));
		foreach ($cats as $cat) {
			$isSticky = CategorizerUtil::isStickyCat($cat) && $this->editPage; //on guided editor we want to show the sticky categories
			if ( $isSticky || !CatSearch::ignoreCategory($cat)) {
				$html .= "<span class='ui-widget-content ui-corner-all cat_category  cat_category_initial'><span class='cat_txt'>$cat</span><span class='cat_close'>x</span></span>";
			}
		}
		return $html;
	}

	private function getCategories(&$t) {
		global $wgContLang;

		$parentCats = array_keys($t->getParentCategories());
		$furtherEditingCats = CategorizerUtil::getFurtherEditingCats();
		$cats = array();
		foreach ($parentCats as $parentCat) {
			$parentCat = str_replace("-", " ", $parentCat);
			$catNsText = $wgContLang->getNSText (NS_CATEGORY);
			$parentCat = str_replace("$catNsText:", "", $parentCat);
			// Trim category text in case someone manually entered a category and left some leading whitespace
			$parentCat = trim($parentCat);
			if (false === array_search($parentCat, $furtherEditingCats)) {
				$cats[] = $parentCat;
			}
		}
		return $cats;
	}

	private function getStickyCategoriesOnly(Title &$title): array {
		$allArticleCats = $this->getCategories($title);
		$stickyArticleCats = [];
		foreach (CategorizerUtil::getStickyCats() as $stickyCat) {
			$stickyCat = str_replace('-', ' ', $stickyCat);
			if (in_array($stickyCat, $allArticleCats)) {
				$stickyArticleCats[] = $stickyCat;
			}
		}
		return $stickyArticleCats;
	}

	private function getUncategorizedPageIds($getCount=false) {
		global $wgMemc;

		$pageIds = CategorizerUtil::getUncategorizedPagesIds();
		if (empty($pageIds)) {
			// No more articles to categorize. Let's hold off on checking for 30 min
			// to give the DB a break
			$wgMemc->set($this->noMoreArticlesKey, 1, $this->halfHour);
		}
		return $pageIds;
	}

	private function complete($page) {
		global $wgMemc;

		$key = $this->pageIdsKey;
		// Remove from page ids
		$pageIds = $wgMemc->get($key);
		if ($pageIds) {
			foreach ($pageIds as $k => $pageId) {
				if ($page == $pageId) {
					unset($pageIds[$k]);
					$wgMemc->set($key, $pageIds, $this->oneWeek);
					break;
				}
			}
		}
		$this->categorize($page);
		$this->unmarkInUse($page);
	}

	private function categorize($aid) {
		$req = $this->getRequest();

		$t = Title::newFromId($aid);
		if ($t && $t->exists()) {
			$dbw = wfGetDB(DB_MASTER);
			$wikitext = Wikitext::getWikitext($dbw, $t);

			$intro = Wikitext::getIntro($wikitext);
			$intro = $this->stripCats($intro);

			$stickyCats = $this->getStickyCategoriesOnly($t);
			$submittedCats = array_reverse($req->getArray('cats', array()));
			$cats = array_merge($stickyCats, $submittedCats);

			$intro .= $this->getCatsWikiText($cats);
			$wikitext = Wikitext::replaceIntro($wikitext, $intro);
			$result = Wikitext::saveWikitext($t, $wikitext, 'categorization');

			// Article saved successfully
			if ($result === '') {
				Hooks::run("CategoryHelperSuccess", array());
			}
		}
	}

	private function getCatsWikiText($cats) {
		global $wgContLang;
		$text = "";
		foreach ($cats as $cat) {
			$text .= "\n[[" . $wgContLang->getNSText(NS_CATEGORY) . ":$cat]]";
		}
		return $text;
	}

	private function stripCats($text) {
		global $wgContLang;
		return preg_replace("/\[\[" . $wgContLang->getNSText(NS_CATEGORY) . ":[^\]]*\]\]/im", "", $text);
	}
}
