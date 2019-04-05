<?php

class UnitGuardian extends UnlistedSpecialPage {

	const TABLE_NAME_TOOL = "unitguardian";
	const TABLE_NAME_CONVERSIONS = "unitguardian_conversions";
	const VOTES_TO_PATROL = 3;
	const UP_VOTE = "ugc_up";
	const DOWN_VOTE = "ugc_down";

	var $skipTool;
	var $data;
	var $previousSentenceEnd = '\.!\n\]';
	var $sentenceEnd = '\.|!|\n|$|;';

	function __construct() {
		global $wgHooks;
		parent::__construct('UnitGuardian');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
		$wgHooks['getMobileToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public static function onIsEligibleForMobileSpecial(&$mobileAllowed) {
		global $wgTitle;
		if ($wgTitle && strrpos($wgTitle->getText(), "UnitGuardian") === 0) {
			$mobileAllowed = true;
		}

		return true;
	}

	function execute($par) {
		$request = $this->getRequest();
		$out = $this->getOutput();

		if (!Misc::isMobileMode()) {
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		//code for maintenance message
		$underMaintenance = false;
		if ($underMaintenance) {
			$out->addWikiText("This tool is temporarily down for maintenance. Please check out the [[Special:CommunityDashboard|Community Dashboard]] for other ways to contribute while we iron out a few issues with this tool. Happy editing!");
			return;
		}

		$this->skipTool = new ToolSkip("UnitGuardian");

		if ($request->getVal('clearSkip')) {
			$this->skipTool->clearSkipCache();
		}

		if ($request->wasPosted()) {

			$out->setArticleBodyOnly(true);

			$this->data = $request->getArray('data');
			$action = $request->getVal('action');

			// Anons only have the illusion of their votes counting.  Just discard and give them
			// the next unit conversion. Eventually we'll open it up.
			if ($this->getUser()->isAnon()) {
				$action = 'getNext';
			}

			switch ($action) {
				case 'vote_down':
					$this->vote(self::DOWN_VOTE);
					$this->logAction($action);
					$result = $this->getNextContent();
					break;
				case 'vote_up':
					$this->vote(self::UP_VOTE);
					$this->logAction($action);
					$result = $this->getNextContent();
					break;
				case "not_sure":
				case "maybe":
					$this->logAction($action);
				case 'getNext':
					$result = $this->getNextContent();
					break;
				default:
					$result['error'] = wfMessage('ug-invalid-action');
			}

			print(json_encode($result));
			return;
		}

		$out->setPageTitle(wfMessage('unitguardian'));
		$out->setHTMLTitle(wfMessage('unitguardian'));
		$this->addModules();

		$tmpl = new EasyTemplate(__DIR__);
		$vars = $this->getTemplateVars();
		$vars['tool_info'] = class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : '';

		$tmpl->set_vars($vars);
		$out->addHTML($tmpl->execute("unitguardian.tmpl.php"));

	}

	protected function getTemplateVars() {
		$adw = new ArticleDisplayWidget();
		return $adw->addTemplateVars();
	}

	protected function skipId($id) {
		$this->skipTool->skipItem($id);
	}

	/****
	 * Having some trouble with the sentence regex, so this allows us to skip those problem ones
	 ***/
	protected function getNextContent() {
		// Don't loop more than 5 times
		$i = 0;
		while ($i < 5 && $result = $this->getNext()) {
			$i++;
			if (!empty($result['error'])
				&& $result['error'] != wfMessage('ug-queue-end')->text()) {
				continue;
			} elseif (isset($result['content']) && $result['content'] == "") {
				continue;
			} elseif (!isset($result['title']) || !$result['title']) {
				continue;
			} else {
				//found one!!
				break;
			}
		}
		return $result;
	}

	protected function getNext() {
		$result = array();

		$dbr = wfGetDB(DB_REPLICA);

		$next = null;

		$where = array('ugc_resolved' => 0);
		$skippedIds = $this->skipTool->getSkipped();

		if (is_array($skippedIds) && !empty($skippedIds)) {
			$where[] = "ugc_id not in (" . implode(',', $skippedIds) . ")";
		}
		//for testing
		//$where[] = "ugc_page = 6334595";

		$res = $dbr->select(
			self::TABLE_NAME_CONVERSIONS,
			array(
				'ugc_id',
				'ugc_page',
				'ugc_up',
				'ugc_down',
				'ugc_original',
				'ugc_converted',
				'ugc_template',
			),
			$where,
			__METHOD__,
			array("ORDER BY" => "(ugc_up + ugc_down) DESC", "LIMIT" => 1)
		);

		if ($next || $row = $dbr->fetchObject($res)) {
			$result = get_object_vars($row);
			$t = Title::newFromId($result['ugc_page']);

			if ($t && $t->exists() && $r = Revision::newFromTitle($t)) {
				list($html, $sentence) = $this->getArticleStuff($t, $r, $result['ugc_original']);
				//flatten to take out references, links, etc
				$result['replacement'] = $result['ugc_original'];
				$result['ugc_original'] = Wikitext::flatten($result['ugc_original']);
				$result['html'] = $html;
				$result['content'] = $sentence;
				$result['aid'] = $result['ugc_page'];
				$result['title'] = $t->getText();
				if ($sentence == "") {
					//yuck, one of the problem ones, let's mark it resolved so others don't get it.
					$dbw = wfGetDB(DB_MASTER);
					$dbw->update(UnitGuardian::TABLE_NAME_CONVERSIONS, array('ugc_resolved' => 1), array('ugc_id' => $result['ugc_id']), __METHOD__);
				}
			} else {
				$result['error'] = wfMessage('ug-error-missing-title')->text();
			}
			$this->skipId($result['ugc_id']);
		} else {
			$result['error'] = wfMessage('ug-queue-end')->parse();
		}
		return $result;
	}

	protected function getArticleStuff($title, $revision, $originalText) {
		$revisionText = ContentHandler::getContentText( $revision->getContent() );
		$quotedOriginal = preg_quote($originalText);
		preg_match("@(\p{Lu}|[^{$this->previousSentenceEnd}])*{$quotedOriginal}.*?({$this->sentenceEnd})($|\s|\W|\D)@um", $revisionText, $matches);
		//var_dump("@(\p{Lu}|[^{$this->previousSentenceEnd}])*{$quotedOriginal}.*?({$this->sentenceEnd})($|\s|\W|\D)@u");
		if ($matches[0] == null) {
			$content = "";
		} else {
			$content = trim($matches[0]);
			$firstCharacter = substr($content, 0, 1);
			if (in_array($firstCharacter, array("*", "#"))) {
				//make sure we're not grabbing the leading #, *
				$content = substr($content, 1);
			}
			$lastCharacter = substr($content, -1);
			if (in_array($lastCharacter, array("*", "#", "[", "<", "{"))) {
				//make sure we're not grabbing the leading #, *
				$content = substr($content, 0, -1);
			}
		}

		$content = Wikitext::flatten($content);

		if (Misc::isMobileMode()) {
			$config = WikihowMobileTools::getToolArticleConfig();
			$html = WikihowMobileTools::getToolArticleHtml($title, $config, $revision);
		} else {
			$out = $this->getOutput();
			$revision = Revision::newFromTitle($title);
			$popts = $out->parserOptions();
			$popts->setTidy(true);
			$parserOutput = $out->parse($revisionText, $title, $popts);
			$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));
			$html = WikihowArticleHTML::processArticleHTML(
				$parserOutput,
				array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));

		}
		return array($html, $content);
	}

	protected function addModules() {
		$out = $this->getOutput();
		$out->addModules(array(
			'ext.wikihow.UsageLogs',
			'ext.wikihow.mobile.unitguardian'
		));
	}

	/*
	 * Record an up or down vote.  Mark the row patrolled if the number of
	 * votes needed for patrolling has been met
	 *
	 */
	protected function vote($voteType) {
		$data = $this->data;
		$dbw = wfGetDB(DB_MASTER);
		$isResolved = $this->isResolved($voteType);
		$dbw->update(
			self::TABLE_NAME_CONVERSIONS,
			array(
				"$voteType"  => $data[$voteType] + 1,
				'ugc_resolved' => $isResolved ? 1 : 0
			),
			array(
				'ugc_id' => $data['ugc_id']
			),
			__METHOD__
		);

		if ($isResolved) {
			$action = ($voteType == self::UP_VOTE) ? "approved" : "rejected";
			UsageLogs::saveEvent(
				array(
					'event_type' => 'unit_guardian',
					'event_action' => $action,
					'article_id' => $data['ugc_page'],
					'assoc_id' => $data['ugc_id']
				)
			);
		}

		if ($isResolved && $voteType == self::UP_VOTE) {
			//need to actually put the conversion in now
			$title = Title::newFromID($data['ugc_page']);
			if ($title) {
				$wikitext = Wikitext::getWikitext($dbw, $title);
				$newWikitext = str_replace($data['replacement'], $data['ugc_template'], $wikitext);

				$wp = new WikiPage($title);
				$content = ContentHandler::makeContent( $newWikitext, $title );

				$user = User::newFromName("UnitGuardian");
				$wp->doEditContent($content, "Adding conversion template.", null, false, $user);
			}
		}
	}

	protected function isResolved($voteType) {
		$data = $this->data;

		if ($voteType == self::UP_VOTE) {
			$resolved = ($data[self::UP_VOTE] + 1 - $data[self::DOWN_VOTE]) >= self::VOTES_TO_PATROL;
		} else {
			$resolved = ($data[self::DOWN_VOTE] + 1 - $data[self::UP_VOTE]) >= self::VOTES_TO_PATROL;
		}

		return $resolved;
	}

	protected function logAction($action) {
		// Add a log entry, only if not a planted question
		$t = Title::newFromId($this->data['ugc_page']);
		if ($t && $t->exists()) {
			$log = new LogPage( 'unitguardian', false );
			$logType = Misc::isMobileMode() ? "m" : "d";
			$msg = wfMessage("ug-edit-message-$logType")->rawParams("[[{$t->getText()}]]")->escaped();
			$log->addEntry($action, $t, $msg, null);
		}
	}

	public static function onPageContentSaveComplete($article) {
		$dbw = wfGetDB(DB_MASTER);
		if ($article->getTitle()->inNamespace(NS_MAIN) && !$article->isRedirect()) {
			$dbw->update(self::TABLE_NAME_CONVERSIONS, array('ugc_dirty' => 1), array('ugc_page' => $article->getId()), __METHOD__);
			$dbw->upsert(self::TABLE_NAME_TOOL, array('ug_page' => $article->getId(), 'ug_dirty' => 1, 'ug_whitelist' => 0), array(), array('ug_dirty' => 1), __METHOD__);
		} else {
			$dbw->delete(self::TABLE_NAME_CONVERSIONS, array('ugc_page' => $article->getId(), 'ugc_resolved' => 0), __METHOD__);
			$dbw->delete(self::TABLE_NAME_TOOL, array('ug_page' => $article->getId()), __METHOD__);
		}
		return true;
	}

	public static function onArticleDelete(&$article) {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->delete(self::TABLE_NAME_CONVERSIONS, array('ugc_page' => $article->getId(), 'ugc_resolved' => 0), __METHOD__);
		$dbw->delete(self::TABLE_NAME_TOOL, array('ug_page' => $article->getId()), __METHOD__);

		return true;
	}

	static function processArticle(&$converter, &$dbw, $articleId) {
		$converter->checkForConversion($articleId, $dbw);

		//now that we've updated all conversions in the article, let's remove all the old ones left in the db
		$dbw->delete(self::TABLE_NAME_CONVERSIONS, array('ugc_page' => $articleId, 'ugc_dirty' => 1, 'ugc_resolved' => 0), __METHOD__);
	}

	static function insertNewConversion($articleId, $oldHtml, $newHtml, $template) {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->upsert(self::TABLE_NAME_CONVERSIONS, array('ugc_page' => $articleId, 'ugc_hash' => md5($oldHtml), 'ugc_original' => $oldHtml, 'ugc_converted' => $newHtml, 'ugc_template' => $template), array(), array('ugc_dirty' => 0), __METHOD__);
	}

	static function addArticle(&$dbw, $articleId) {
		$dbw->upsert(self::TABLE_NAME_TOOL, array('ug_page' => $articleId, 'ug_dirty' => 1, 'ug_whitelist' => 0), array(), array('ug_dirty' => 1), __METHOD__);
	}

	static function checkAllDirtyArticles() {
		$dbw = wfGetDB(DB_MASTER);

		$converter = new UnitConverter();

		$res = DatabaseHelper::batchSelect(self::TABLE_NAME_TOOL,  array('ug_page'), array('ug_dirty' => 1, 'ug_whitelist' => 0), __METHOD__);

		$i = 0;
		foreach ($res as $row) {
			self::processArticle($converter, $dbw, $row->ug_page);
			$dbw->update(self::TABLE_NAME_TOOL, array('ug_dirty' => 0), array('ug_page' => $row->ug_page), __METHOD__);
			$i++;
		}

		echo "$i articles processed\n";
	}
}

class AdminUnitGuardian extends UnlistedSpecialPage {
	function __construct() {
		parent::__construct('AdminUnitGuardian');
	}

	function execute($par) {
		$user = $this->getUser();
		$out = $this->getOutput();

		if ( !$user->isLoggedIn() ) {
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		// Display pager
		$llr = new UnitGuardianContents();
		$llr->getList();

		$out->setPageTitle("Admin Unit Guardian");
	}

}

class UnitGuardianContents extends QueryPage {

	function __construct() {
		parent::__construct('UnitGuardianContents');
	}

	function getList() {
		list( $limit, $offset ) = RequestContext::getMain()->getRequest()->getLimitOffset(50, 'rclimit');
		$this->limit = $limit;
		$this->offset = $offset;

		parent::execute('');
	}

	function getSQL() {
		$sql = "SELECT ugc_page as value, ugc_original, ugc_template, ugc_up, ugc_down from " . UnitGuardian::TABLE_NAME_CONVERSIONS . " WHERE ugc_resolved = 1 AND ugc_down >= ugc_up";
		return $sql;
	}

	function formatResult( $skin, $result ) {
		$title = Title::newFromID($result->value);
		$html = "<a href='" . $title->getFullURL() . "'>{$title->getText()}</a><br />({$result->ugc_original}) &lt;&lt;&lt; TO &gt;&gt;&gt; ({$result->ugc_template})<br /> (yes: {$result->ugc_up}, no: {$result->ugc_down})";

		return $html;
	}
}
