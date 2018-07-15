<?php

/***********************
 *
 *  A class to assist with articles that have added/subtracted NFD templates
 *
 ***********************/
class NFDProcessor {

	var	$mArticle	= null; // current article we are processing
	var $mResult	= null; // result row from the db
	var $mTitle		= null; // title of the article we are processing
	var $mTemplate	= null; // full template on the article (eg: {{nfd|acc|date}}
	var $mReason	= null; // they type of nfd in the form of an array('type':dup,'article':articleTitle)
	var	$mTemplatePart = "nfd";
	var $mRevision	= null; // current revision of the current article we are processing

	function __construct($revision = null, $article = null) {
		$this->mRevision	= $revision;
		$this->mArticle		= $article;
	}

	/**
	 * Processes the given article. Checks to see if a
	 * NFD template exists, and if so, handles accordingly
	 * NOTE: called by hooks in NFDGuardian.php
	 */
	public function process($echoInfo = false) {
		if ($this->hasNFD($this->mRevision->getText())) { //currently has NFD tag
			//now grab all the relevant information from this tag
			$this->mTemplate = $this->getFullTemplateFromText($this->mRevision->getText());
			$this->mReason = $this->extractReason($this->mTemplate);
			$this->setFirstEdit();

			//now check to see if we actually need to add it in to the db
			/*if ($this->mReason['type'] == "dup") {
				//we don't use duplicates in this tool
				self::markPreviousAsInactive($this->mArticle->getID());
			} else*/ if ($this->hasInUse()) {
				//we don't put articles with inuse tags in the tool
				//remove if already in tool
				self::markPreviousAsInactive($this->mArticle->getID());
				if ($echoInfo) {
					echo "Removing from tool: " . $this->mArticle->getTitle()->getText() . "\n";
				}
			} elseif ($this->hasBeenDecided()) {
				//its already been decided at another point in time (either in NFDGuardian or in regular discussions
				//now make it advanced
				$this->markAsAdvanced($this->mArticle->getID());
				if ($echoInfo) {
					echo "Marking as Advanced: " . $this->mArticle->getTitle()->getText() . "\n";
				}
			} elseif (!$this->availableOrAdvancedInTool($this->mArticle->getID())) {
				$this->logEntry(NFDGuardian::NFD_AVAILABLE);
				if ($echoInfo) {
					echo "Adding: " . $this->mArticle->getTitle()->getText() . "\n";
				}
			} else {
				//already in tool, so no need to do anything
			}
		} else { //currently doesn't have NFD tag
			self::markPreviousAsInactive($this->mArticle->getID());
			if ($echoInfo) {
				echo "Removing from tool: " . $this->mArticle->getTitle()->getText() . "\n";
			}
		}
	}

	static function hasNFD($text) {
		return preg_match("@{{nfd@i", $text);
	}

	function hasInuse() {
		return preg_match("@{{inuse@i", $this->mRevision->getText());
	}

	function availableInTool() {
		$dbr = wfGetDB(DB_SLAVE);

		$entries = $dbr->selectField('nfd',
			'count(*)',
			['nfd_page' => $this->mArticle->getID(),
				'nfd_patrolled' => 0,
				'nfd_status' => NFDGuardian::NFD_AVAILABLE],
			__METHOD__);

		return $entries > 0;
	}

	static function hasBeenDiscussed($title) {
		if ($title) {
			$discussionTitle = Title::newFromText($title->getText(), NS_TALK);
			if ($discussionTitle) {
				$discussionArticle = new Article($discussionTitle);
				$content = $discussionArticle->getContent();
				$matches = array();
				$count = preg_match('/{{nfd.*[^{{]}}/i', $content, $matches);
				if ($count > 0) {
					if (stristr($matches[0], "result=keep") === false) {
						return false;
					} else {
						return true;
					}
				}
			}
		}

		return false;
	}

	static function hasBeenPatrolled($page_id) {
		$dbr = wfGetDB(DB_SLAVE);

		$count = $dbr->selectField('nfd', 'count(*)', array('nfd_page' => $page_id, 'nfd_patrolled' => "1"));

		return $count > 0;
	}

	function hasBeenDecided() {
		return $this->hasBeenDiscussed($this->mArticle->getTitle()) || $this->hasBeenPatrolled($this->mArticle->getID());
	}

	function markAsAdvanced($articleId) {
		//check to see if it exists in the table
		$hasEntry = $this->existsInTool();
		if ($hasEntry) {
			self::markPreviousAsAdvanced($articleId);
		} else {
			$this->logEntry(NFDGuardian::NFD_ADVANCED);
		}
	}

	function existsInTool() {
		$dbr = wfGetDB(DB_MASTER);

		$articleId = $this->mArticle->getID();
		$count = $dbr->selectField('nfd', 'count(*)', ['nfd_page'=> $articleId], __METHOD__);
		return $count > 0;
	}

	private function availableOrAdvancedInTool($pageId) {
		$dbr = wfGetDB(DB_SLAVE);

		$count = $dbr->selectField('nfd',
			'count(*)',
			['nfd_page' => $pageId,
				'nfd_patrolled' => 0,
				'(nfd_status = ' . NFDGuardian::NFD_AVAILABLE . ' OR nfd_status = ' . NFDGuardian::NFD_ADVANCED . ')'],
			__METHOD__);
		return $count > 0;
	}

	/**
	 * Given an NFD template in the form {{nfd|rea|date}}, extracts
	 * the specific reason given (3 letter code) and also checks for
	 * the existence of a duplicate article title.
	 */
	function extractReason($nfdTemplate) {
		$nfdReasons = array();
		$nfdReasons['type'] = "none";

		$parts = explode('|', $nfdTemplate);
		if (count($parts) > 2) {
			//reason given
			$nfdReasons['type'] = strtolower( $parts[1] );
			if ($nfdReasons['type'] == 'dup') {
				$nfdReasons['article'] = $parts[2];
			}
		}

		return $nfdReasons;
	}

	/**
	 *
	 * Returns the full NFD template for this article
	 * (eg: {{nfd|acc|date}}
	 *
	 */
	function getFullTemplate($nfdid = 0) {
		if ($this->mTemplate != null) {
			return $this->mTemplate;
		} else {
			$dbr = wfGetDB(DB_SLAVE);
			$template = $dbr->selectField('nfd', 'nfd_template', ['nfd_id' => $nfdid], __METHOD__);
			return $template;
		}
	}

	/**
	 *  Removes an unneeded NFD entry from the nfd table
	 *  if the title doesn't exist in the db
	 */
	function deleteBad($nfd_page) {
		// is there something we can delete ?
		$dbw = wfGetDB(DB_MASTER);
		$page_title = $dbw->selectField('page', 'page_title', ['page_id' => $nfd_page], __METHOD__);
		if (!$page_title) {
			$dbw->delete('nfd', array('nfd_page'=>$nfd_page));
		}
	}

	function getTitleFromNFDID($nfdid) {

		$dbr = wfGetDB(DB_MASTER);
		$page_id = $dbr->selectField('nfd', 'nfd_page', ['nfd_id' => $nfdid], __METHOD__);
		$t = Title::newFromID($page_id);
		return $t;
	}

	static function markAsPatrolled($nfdid, $id) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("nfd",
			["nfd_patrolled" => 1],
			["nfd_id" => $nfdid],
			__METHOD__);
		self::markPreviousAsInactive($id);
	}

	static function markAsDup($nfdid, $id) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("nfd",
			["nfd_patrolled" => 1, "nfd_status" => NFDGuardian::NFD_DUP],
			["nfd_id"=> $nfdid],
			__METHOD__);
	}

	/*
	 * Marks all articles with given page_id as inactive, meaning that they are
	 * no longer in the tool, but haven't necessarily been "patrolled" by the tool
	 * (a decision wasn't made, it was just removed from the tool)
	 *
	 * NOTE: called by hooks in NFDGuardian.php
	 */
	public static function markPreviousAsInactive($id) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as inactive for this entry
		$dbw->update("nfd",
			["nfd_status" => NFDGuardian::NFD_INACTIVE],
			["nfd_page"=> $id],
			__METHOD__);
	}

	static function markPreviousAsAdvanced($id) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as advanced for this entry
		$dbw->update("nfd",
			["nfd_status" => NFDGuardian::NFD_ADVANCED],
			["nfd_page" => $id],
			__METHOD__);
	}

	function logEntry($status) {
		global $wgUser;

		$opts = array(	"nfd_action" => "added",
						"nfd_template" => $this->mTemplate,
						"nfd_reason" => $this->mReason['type'],
						"nfd_timestamp" => $this->mRevision->getTimestamp(),
						"nfd_fe_timestamp" => $this->mFirstEdit,
						"nfd_user" => $wgUser->getID(),
						"nfd_user_text" => $wgUser->getName(),
						"nfd_page" => $this->mArticle->getID(),
						"nfd_status" => $status
				);

		self::markPreviousAsInactive($this->mArticle->getID());

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('nfd', $opts, __METHOD__);
	}

	function getNextToPatrolHTML() {
		global $wgOut, $wgParser;

		if ( !$this->mResult ) {
			// Nothing to patrol
			return null;
		}

		// Get the page title
		$title = Title::newFromID( $this->mResult->nfd_page );
		if ( !$title ) {
			self::markPreviousAsInactive( $this->mResult->nfd_page );
			return "<!--{$this->mResult->nfd_page}-->" .
				"error creating title (id# {$this->mResult->nfd_page}) , oops, please " .
				" <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// Get current page revsion
		$revision = Revision::newFromTitle( $title );
		if ( !$revision ) {
			return "Error creating revision";
		}

		// Generate article preview
		$popts = $wgOut->parserOptions();
		$popts->setTidy( true );
		$text = $revision->getText();
		$output = $wgParser->parse( $text, $title, $popts );
		$parserOutput = $output->getText();
		$magic = WikihowArticleHTML::grabTheMagic( $text );
		$html = WikihowArticleHTML::processArticleHTML(
			$parserOutput,
			array( 'no-ads' => true, 'ns' => $title->getNamespace(), 'magic-word' => $magic )
		);

		// Wrap article preview in template
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars( array(
			'titleUrl' => $title->getFullURL(),
			'title' => $title->getText(),
			'nfdId' => $this->mResult->nfd_id,
			'articleHtml' => $html,
			'articleInfo' => $this->getArticleInfoBox()
		) );
		$html = $tmpl->execute( 'NFDarticle.tmpl.php' );

		return $html;
	}

	/**
	 *
	 * Marks the given nfd as viewed by the user (could
	 * be b/c of skip or vote)
	 *
	 */
	static function markNFDAsViewed($nfdid) {
		global $wgMemc, $wgUser;
		$userid = $wgUser->getID();
		$key = wfMemcKey("nfduserlog");
		$log = $wgMemc->get($key);
		if (!$log) {
			$log = array();
		}
		if (!isset($log[$userid])) {
			$log[$userid] = array();
		}
		$log[$userid][] = $nfdid;
		$wgMemc->set($key, $log);
	}

	/**
	 *
	 * Gets a list of all articles previously viewed
	 * by the current user
	 *
	 */
	static function getPreviouslyViewed() {
		global $wgMemc, $wgUser;
		$userid = $wgUser->getID();
		$key = wfMemcKey("nfduserlog");

		$log = $wgMemc->get($key);

		if (!$log) {
			return "";
		}
		if (!isset($log[$userid])) {
			return "";
		}
		$good = array();
		foreach ($log[$userid] as $u) {
			if (!preg_match("@[^0-9]@", $u) && $u != "") {
				$good[] = $u;
			}
		}
		$str = preg_replace("@,$@", "", implode(",", array_unique($good)));

		return $str;
	}

	public static function getNextToPatrol($type) {
		global $wgUser;

		// grab the next one
		$dbw = wfGetDB(DB_MASTER);
		$expired = wfTimestamp(TS_MW, time() - NFDGuardian::NFD_EXPIRED);
		$eligible = wfTimestamp(TS_MW, time() - NFDGuardian::NFD_WAITING);


		$sql = "SELECT * from nfd left join nfd_vote ON nfd_id=nfdv_nfdid AND nfdv_user = {$wgUser->getID()} "
			. " WHERE ( nfd_checkout_time < '{$expired}' OR nfd_checkout_time = '')
				AND nfd_patrolled = 0
				AND nfd_status = '" . NFDGuardian::NFD_AVAILABLE . "'
				AND nfd_user != {$wgUser->getID()}
				AND nfd_timestamp < '{$eligible}'
				AND nfdv_nfdid is NULL ";

		if ($type != 'all' && $type) {
			$sql .= " AND nfd_reason = " . $dbw->addQuotes($type) . " ";
		} else {
			$sql .= " AND nfd_reason != 'dup' ";
		}

		$previous = self::getPreviouslyViewed();
		if ($previous) {
			$sql .= " AND  nfd_id NOT IN ({$previous})";
		}

		$sql .= " ORDER BY nfd_fe_timestamp ASC";

		$sql .= " LIMIT 1";
		$res = $dbw->query($sql, __METHOD__);
		$result = $dbw->fetchObject($res);

		if (!$result) {
			return null;
		}

		$c = new NFDProcessor();
		$c->mResult = $result;
		$c->mTitle = Title::newFromID($c->mResult->nfd_page);

		if (!$c->mTitle) {
			self::markPreviousAsInactive($c->mResult->nfd_page);
		}

		// if we have one, check it out of the queue so multiple people don't get the same item to review
		if ($result) {
			// mark this as checked out
			$dbw->update('nfd',
				['nfd_checkout_time' => wfTimestampNow(), 'nfd_checkout_user' => $wgUser->getID()],
				['nfd_id' => $result->nfd_id],
				__METHOD__);
		}

		return $c;
	}

	function releaseNFD($nfdid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('nfd',
			['nfd_checkout_time' => "", 'nfd_checkout_user' => 0],
			['nfd_id' => $nfdid],
			__METHOD__);
		return true;
	}

	function markNFDPatrolled($nfdid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('nfd',
			['nfd_patrolled' => 1],
			['nfd_id' => $nfdid],
			__METHOD__);
		return true;
	}

	public static function save($nfdid, &$t) {
		global $wgUser, $wgLang;
		$dbw = wfGetDB(DB_MASTER);
		$nfdUser = new User();
		$nfdUser->setName( 'NFD Voter Tool' );
		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('nfd_vote',
			'count(*)',
			['nfdv_user' => $wgUser->getID(), 'nfdv_nfdid' => $nfdid],
			__METHOD__);
		if ($count > 0) {
			self::releaseNFD($nfdid);
			return;
		}

		//delete all the delete votes
		$dbw->delete('nfd_vote', ['nfdv_nfdid' => $nfdid, 'nfdv_vote' => 1], __METHOD__);
		$dbw->update('nfd',
			['nfd_delete_votes' => 0, 'nfd_admin_delete_votes' => 0],
			['nfd_id' => $nfdid],
			__METHOD__);

		//now mark a keep vote
		$opts = array();
		$voteCount = 0;
		if ($wgUser->isSysOp()) {
			$voteCount = 2;
			$opts[] = "nfd_admin_keep_votes = nfd_admin_keep_votes + 1";
		} else {
			$voteCount = 1;
		}

		$opts[] = "nfd_keep_votes = nfd_keep_votes + " . $voteCount;
		$voteint = 0;
		$dbw->update('nfd', $opts, ['nfd_id '=> $nfdid], __METHOD__);
		if ($nfdid) {
			$dbw->insert('nfd_vote',
				['nfdv_user' => $wgUser->getID(), 'nfdv_vote' => $voteint, 'nfdv_nfdid' => $nfdid, 'nfdv_timestamp' => wfTimestampNow()],
				__METHOD__);
		}

		// check, do we have to mark it as patrolled, or roll the change back?
		$row = $dbw->selectRow('nfd',
			['nfd_admin_keep_votes', 'nfd_keep_votes', 'nfd_page'],
			['nfd_id' => $nfdid],
			__METHOD__);

		if ($row->nfd_admin_keep_votes >= NFDProcessor::getAdminKeepVotesRequired() && $row->nfd_keep_votes >= NFDProcessor::getKeepVotesRequired()) {
			// what kind of rule are we ? figure it out so we can roll it back
			$c = new NFDProcessor();
			$c->keepArticle($nfdid);
			self::markNFDPatrolled($nfdid);
		} else {
			//not enough votes to keep, so just mark about the save
			//post on discussion page
			$discussionTitle = $t->getTalkPage();
			$userName = $wgUser->getName();
			$dateStr = $wgLang->date(wfTimestampNow());

			$comment = wfMessage('nfd_save_message')->rawParams("[[User:$userName|$userName]]", $dateStr)->escaped();
			$formattedComment = TalkPageFormatter::createComment( $nfdUser, $comment );

			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = $r->getText();
			}

			$article = new Article($discussionTitle);
			$text .= "\n\n$formattedComment\n\n";
			$article->doEdit($text, "");
		}

		self::markNFDAsViewed($nfdid);
		self::releaseNFD($nfdid);

		// log page entry
		$title = Title::newFromID($row->nfd_page);
		if ($title) {
			$log = new LogPage( 'nfd', false );

			$vote_param = "keepvote";

			$msg = wfMessage("nfdrule_log_{$vote_param}")->rawParams("[[{$title->getText()}]]")->escaped();
			$log->addEntry('vote', $title, $msg, array($vote));
			wfRunHooks("NFDVoted", array($wgUser, $title, '0'));
		}
	}

	public static function vote($nfdid, $vote) {
		global $wgUser;
		$dbw = wfGetDB(DB_MASTER);
		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('nfd_vote', array('count(*)'), array('nfdv_user'=>$wgUser->getID(), 'nfdv_nfdid'=>$nfdid));
		if ($count > 0) {
			self::releaseNFD($nfdid);
			return;
		}
		$opts = array();
		$voteCount = 0;
		if ($wgUser->isSysOp()) {
			$voteCount = 2;
			if ($vote == 1) {
				$opts[] = "nfd_admin_delete_votes = nfd_admin_delete_votes + 1";
			} else {
				$opts[] = "nfd_admin_keep_votes = nfd_admin_keep_votes + 1";
			}
		} else {
			$voteCount = 1;
		}
		if ($vote == 1) {
			$opts[] = "nfd_delete_votes = nfd_delete_votes + " . $voteCount;
			$voteint = 1;
		} else {
			$opts[] = "nfd_keep_votes = nfd_keep_votes + " . $voteCount;
			$voteint = 0;
		}

		$dbw->update('nfd', $opts, array('nfd_id'=>$nfdid), __METHOD__);
		if ($nfdid != null) {
			$dbw->insert('nfd_vote', array('nfdv_user'=>$wgUser->getID(), 'nfdv_vote'=>$voteint, 'nfdv_nfdid'=>$nfdid, 'nfdv_timestamp' => wfTimestampNow()), __METHOD__);
		}

		$row = $dbw->selectRow('nfd', '*', array('nfd_id'=>$nfdid), __METHOD__);
		// log the vote
		$title = Title::newFromID($row->nfd_page);
		if ($title) {
			$log = new LogPage( 'nfd', false );

			$vote_param = $vote > 0 ? "deletevote" : "keepvote";

			$msg = wfMessage("nfdrule_log_{$vote_param}")->rawParams("[[{$title->getText()}]]")->escaped();
			$log->addEntry('vote', $title, $msg, array($vote));
			wfRunHooks("NFDVoted", array($wgUser, $title, $vote));
		}

		// check, do we have to mark it as patrolled, or roll the change back?
		if ($vote) {
			if ($row->nfd_admin_delete_votes >= NFDProcessor::getAdminDeleteVotesRequired() && $row->nfd_delete_votes >= NFDProcessor::getDeleteVotesRequired($row->nfd_keep_votes)) {
				self::markNFDPatrolled($nfdid);
				$c = new NFDProcessor();
				$nfdReason = self::extractReason($row->nfd_template);
				$c->deleteArticle($nfdid, $nfdReason);
			}
		} else {
			if ($row->nfd_admin_keep_votes >= NFDProcessor::getAdminKeepVotesRequired() && $row->nfd_keep_votes >= NFDProcessor::getKeepVotesRequired()) {
				// what kind of rule are we ? figure it out so we can roll it back
				$c = new NFDProcessor();
				$c->keepArticle($nfdid);
				self::markNFDPatrolled($nfdid);
			}
		}
		self::markNFDAsViewed($nfdid);
		self::releaseNFD($nfdid);

	}

	// user skips it, so add this to the stuff they have viewed
	function skip($nfdid) {
		self::markNFDAsViewed($nfdid);
	}

	function getPart() {
		return "\{\{" . $this->mTemplatePart;
	}

	function getFullTemplateFromText($text) {
		$matches = array();
		$count = preg_match('/{{nfd[^{{]*}}/i', $text, $matches);
		if (count($matches) > 0) {
			return $matches[0];
		} else {
			//none given
			return "none";
		}
	}

	function setFirstEdit() {
		$dbr = wfGetDB(DB_SLAVE);

		$this->mFirstEdit = $dbr->selectField('firstedit', 'fe_timestamp', ['fe_page'=> $this->mArticle->getID()], __METHOD__);
	}

	static function getDeleteVotesRequired($currentKeepVotes) {
		global $wgNfdVotesRequired;

		if ($currentKeepVotes > 0) {
			return $wgNfdVotesRequired["advanced_delete"];
		} else {
			return $wgNfdVotesRequired["delete"];
		}
	}

	static function getAdminDeleteVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["admin_delete"];
	}

	static function getKeepVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["keep"];
	}

	static function getAdminKeepVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["admin_keep"];
	}

	/**
	 *
	 * Returns the html for the box at the
	 * top of NFD Guardian which contains
	 * information about the current
	 * article being voted on.
	 *
	 */
	function getArticleInfoBox() {
		global $wgOut;

		//first find out who the author was
		$articleInfo = $this->getArticleInfo();
		if (intval($articleInfo->fe_user) > 0) {
			$u = User::newFromId($articleInfo->fe_user);
			$userLink = $u->getUserPage()->getInternalURL();
			$userName = $u->getName();

			$cp = new ContribsPager( RequestContext::getMain(), array( 'target' => $userName ) );
			$uEdits = $cp->getNumRows();
		} else {
			$u = User::newFromName($articleInfo->fe_user_text);

			$userLink = "/User:" . $articleInfo->fe_user_text;
			$userName = $articleInfo->fe_user_text;

			$cp = new ContribsPager( RequestContext::getMain(), array( 'target' => $userName ) );
			$uEdits = $cp->getNumRows();
		}

		//now get the reason the article has been nominated
		$nfdReasons = NFDGuardian::getNfdReasons();
		$nfdReason = self::extractReason($this->mResult->nfd_template);
		$nfdLongReason = isset($nfdReasons[$nfdReason['type']]) ? $nfdReasons[$nfdReason['type']] : '';
		self::replaceTemplatesInText($nfdLongReason, $this->mResult->nfd_page);
		if ($nfdReason['type'] == 'dup' && $nfdReason['article'] != "") {
			$t = Title::newFromText($nfdReason['article']);
			if ($t) {
				$nfdLongReason .= " with [[" . $t->getText() . "]]";
			}
		}

		//finally check the number of discussion items for this
		//article. We ask for confirmation for articles
		//with a lot of discussion items.
		$t = Title::newFromID($this->mResult->nfd_page);
		if ($t) {
			$a = new Article($t);
			// $pageHistory = new PageHistory($a);
			// $pager = new PageHistoryPager( $pageHistory );
			// $edits = $pager->getNumRows();
			$pageHistory = new HistoryPage($a);
			$items = $pageHistory->fetchRevisions(100000,0,1);
			$edits = $items->numRows();

			$discussionTitle = Title::newFromText($t->getText(), NS_TALK);

			if ($discussionTitle) {

				$discussionArticle = new Article($discussionTitle);
				// $pageHistory = new PageHistory($discussionArticle);
				// $pager = new PageHistoryPager( $pageHistory );
				// $discussion = $pager->getNumRows();
				$pageHistory = new HistoryPage($discussionArticle);
				$items = $pageHistory->fetchRevisions(100000,0,1);
				$discussion = $items->numRows();
			} else {
				$discussion = 0;
			}
		}

		$articleInfo = $this->getArticleInfo();
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'age' => wfTimeAgo($this->mResult->nfd_fe_timestamp),
			'authorUrl' => $userLink,
			'authorName' => $userName,
			'views' => $articleInfo->page_counter,
			'nfd' => $wgOut->parse($nfdLongReason),
			'edits' => $edits,
			'userEdits' => $uEdits,
			'nfdVotes' => $this->getTotalVotes($this->mResult->nfd_id),
			'discussion' => $discussion
		));

		$html = $tmpl->execute('NFDinfo.tmpl.php');
		return $html;
	}

	static function replaceTemplatesInText(&$text, $pageId) {
		$t = Title::newFromID($pageId);
		if ($t) {
			//check for talk page
			$talk = $t->getTalkPage();
			if ($talk) {
				$talkLink = "Discussion:" . $talk->getText();;
				$text = str_replace("{{TALKSPACEE}}:{{PAGENAME}}", $talkLink, $text);
			}
		}
	}

	/**
	 *
	 * Returns the text for the all the votes listed
	 * in the info section.
	 *
	 */
	function getTotalVotes($nfd_id) {

		$dbr = wfGetDB(DB_SLAVE);

		$keeps = array();
		$deletes = array();
		$admin = true;

		NFDGuardian::getDeleteKeep($deletes, $keeps, $nfd_id);

		$html = "";
		if (count($deletes) == 0 && count($keeps) == 0) {
			$html .= "There have been no votes yet.";
		} else {
			if (count($deletes) > 0) {
				$i = 0;
				foreach ($deletes as $delete) {
					if ($i > 0) {
						$html .= ", ";
					}
					$html .= NFDGuardian::getUserInfo($delete);
					$i++;
				}
				$html .= " voted to delete. ";
			} else {
				$html .= "There have been no votes to delete. ";
			}
			if (count($keeps) > 0) {
				$i = 0;
				foreach ($keeps as $keep) {
					if ($i > 0) {
						$html .= ", ";
					}
					$html .= NFDGuardian::getUserInfo($keep);
					$i++;
				}
				$html .= " voted to keep. ";
			} else {
				$html .= "There have been no votes to keep. ";
			}
		}

		return $html;

	}

	function getArticleInfo() {
		$dbr = wfGetDB(DB_SLAVE);

		$row = $dbr->selectRow(array('page', 'firstedit'), '*', array('fe_page=page_id', 'page_id' => $this->mResult->nfd_page), __METHOD__);
		return $row;
	}

	/**
	 *
	 * Deletes the article with the given nfdid
	 *
	 */
	function deleteArticle($nfdid, $nfdReason) {
		global $wgUser, $wgLang;

		$nfdUser = new User();
		$nfdUser->setName( 'NFD Voter Tool' );

		// keep the article
		$dbr = wfGetDB(DB_SLAVE);

		// load the revision text
		$pageid = $dbr->selectField('nfd', array('nfd_page'), array('nfd_id'=> $nfdid), __METHOD__);
		$t = Title::newFromID($pageid);
		if (!$t) {
			return false;
		}

		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}

		$article = new Article($t);
		if (!$article) {
			return false;
		}

		$dateStr = gmdate('n/j/Y', time()); //$wgLang->date(wfTimestampNow());
		$votes = $this->getVotes($nfdid, $dbr);
		$comment = wfMessage('nfd_delete_message')->rawParams($dateStr, $nfdReason['type'], $votes['deleteUsers'], $votes['keepUsers'], "[[".$t->getText()."]]", number_format($article->getCount(), 0, "", ","))->escaped();

		$foundDup = false;
		if ($nfdReason['type'] == "dup") {
			//check if it was a duplicate

			$dupTitle = Title::newFromText($nfdReason['article']);
			if (!$dupTitle) {
				$dupTitle = Title::newFromText("Deleted-Article", NS_PROJECT);
			}

			if ($dupTitle) {
				$dupRev = Revision::newFromTitle($dupTitle);
				if ($dupRev) {
					//the duplicate title exists, so turn the current article into a redirct
					$editSuccess = $article->doEdit("#REDIRECT [[" . $dupTitle->getPrefixedURL() . "]]", $comment);
					$foundDup = true;
					self::markAsDup($nfdid, $pageid);

					//log redirect in the nfd table
					$log = new LogPage('nfd', false);
					$log->addEntry('redirect', $t, $comment);

					$commentDup = wfMessage('nfd_dup_message')->rawParams($dateStr, $nfdReason['type'], $votes['deleteUsers'], $votes['keepUsers'], "[[".$t->getText()."]]", number_format($article->getCount(), 0, "", ","), "[[".$dupTitle->getText()."]]")->escaped();
					$formattedComment = TalkPageFormatter::createComment( $nfdUser, $commentDup );
					$discussionTitle = $t->getTalkPage();
					$text = "";
					if ($discussionTitle->getArticleId() > 0) {
						$r = Revision::newFromTitle($discussionTitle);
						$text = $r->getText();
					}

					//add a comment to the discussion page
					$articleDiscussion = new Article($discussionTitle);
					$text .= "\n\n$formattedComment\n\n";
					$articleDiscussion->doEdit($text, "");

				}
			}
		}

		//if we haven't found a duplicate, then go ahead and do the delete
		if (!$foundDup) {
			$formattedComment = TalkPageFormatter::createComment( $nfdUser, $comment );

			$discussionTitle = $t->getTalkPage();
			$text = "";
			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = $r->getText();
			}

			//add a comment to the discussion page
			$articleDiscussion = new Article($discussionTitle);
			$text .= "\n\n$formattedComment\n\n";
			$articleDiscussion->doEdit($text, "");

			//now delete the article
			$editSuccess = $article->doDeleteArticle($comment);


			//no need to log in the deletion table b/c doDeleteArticle does it for you

			//log same delete in the nfd table
			$log = new LogPage('nfd', false);
			$log->addEntry('delete', $t, $comment);
		}

	}

	/**
	 *
	 * Helper function to get an array of all the votes
	 * to delete and keep for the given nfdid
	 *
	 */
	function getVotes($nfdid, &$dbr) {
		$votes = array();
		$votes['keepUsers'] = "";
		$votes['deleteUsers'] = "";
		$res = $dbr->select('nfd_vote', ['nfdv_user', 'nfdv_vote'], ['nfdv_nfdid' => $nfdid], __METHOD__);
		foreach ($res as $row) {
			$user = User::newFromId($row->nfdv_user);
			if ($user) {
				if ($row->nfdv_vote == 0) {
					if ($votes['keepUsers']) {
						$votes['keepUsers'] .= ", ";
					}
					$userName = $user->getName();
					$votes['keepUsers'] .= "[[User:$userName|$userName]]";
				} else {
					if ($votes['deleteUsers']) {
						$votes['deleteUsers'] .= ", ";
					}
					$userName = $user->getName();
					$votes['deleteUsers'] .= "[[User:$userName|$userName]]";
				}
			}
		}

		if (!$votes['keepUsers']) {
			$votes['keepUsers'] = "No one";
		}
		if (!$votes['deleteUsers']) {
			$votes['deleteUsers'] = "No one";
		}

		return $votes;
	}

	/*
	 * NOTE: used in NFDGuardian.
	 */
	public function keepArticle($nfdid) {
		global $wgUser, $wgLang;

		// keep the article
		$dbr = wfGetDB(DB_SLAVE);

		$pageid = $dbr->selectField('nfd', 'nfd_page', ['nfd_id' => $nfdid], __METHOD__);

		// load the revision text
		$t = Title::newFromID($pageid);
		if (!$t || !$t->exists()) {
			return false;
		}
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}
		$text = $r->getText();

		//remove the template
		$text = preg_replace("@\{\{" . $this->mTemplatePart . "[^\}]*\}\}@i", "", $text);

		$a = new Article($t);
		$editSuccess = $a->doEdit( $text, wfMessage('nfd_keep_summary_template', $this->mTemplatePart)->text() );

		//now add a discussion message
		if ($editSuccess) {
			$nfdUser = new User();
			$nfdUser->setName( 'NFD Voter Tool' );
			$text = "";
			$article = "";
			$discussionTitle = $t->getTalkPage();

			$votes = $this->getVotes($nfdid, $dbr);

			$fullTemplate = $this->getFullTemplate($nfdid);
			$nfdReason = $this->extractReason($fullTemplate);
			$keepTemplate = "{{" . $this->mTemplatePart . "|" . $nfdReason['type'] . "|result=keep}}\n";
			$dateStr = $wgLang->date(wfTimestampNow());

			$comment = $keepTemplate . wfMessage('nfd_keep_message')->rawParams($dateStr, $votes['keepUsers'], $votes['deleteUsers'])->escaped();
			$formattedComment = TalkPageFormatter::createComment( $nfdUser, $comment );

			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = $r->getText();
			}

			//add a discussion item
			$article = new Article($discussionTitle);
			$text .= $formattedComment;
			$article->doEdit($text, "");

			//log keep
			$keepLogComment = wfMessage('nfd_keep_log_message')->rawParams($dateStr, $votes['keepUsers'], $votes['deleteUsers'], "[[".$t->getText()."]]")->escaped();
			$log = new LogPage('nfd', false);
			$log->addEntry('keep', $t, $keepLogComment);
		} else {
			return false;
		}
	}
}

/***********************
 *
 *  The special page for dealing with entries in the NFD queue
 *
 ***********************/
class NFDGuardian extends SpecialPage {

	const NFD_AVAILABLE = 0;
	const NFD_INACTIVE = 1;
	const NFD_ADVANCED = 2;
	const NFD_DUP = 3;

	const NFD_WAITING = 604800; //60*60*24*7 = 604800 = 7 days
	const NFD_EXPIRED = 3600; //60*60 = 1 hour

	function __construct() {
		global $wgHooks;
		parent::__construct( 'NFDGuardian' );
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	function getNextInnards($nfd_type) {
		// grab the next check
		$result = array();
		$c = NFDProcessor::getNextToPatrol($nfd_type);
		if ($c && $c->mTitle)  {
			// nfd_vote, nfd_skip
			$result['title'] 		= "<a href='{$c->mTitle->getLocalURL()}'>{$c->mTitle->getText()}</a>";
			$result['html'] 		= $c->getNextToPatrolHTML();
			$result['nfd_id'] 		= $c->mResult->nfd_id;
			$result['nfd_page']		= $c->mResult->nfd_page;
			$result['nfd_reasons_link'] = $this->getNfdReasonsLink();
			$result['nfd_reasons']	= $this->getNfdReasonsDropdown($nfd_type);
			$result['nfd_discussion_count'] = $this->getDiscussionCount($c->mResult->nfd_page);
		} else {
			$result['done'] 		= 1;
			$result['title'] 		= wfMessage('nfd');
			$result['msg'] 			= "<div class='tool_header'><div id='nfd_options'></div>
										<div id='nfd_head'>
										<p class='nfd_alldone'>".wfMessage('nfd_congrats')."</p>
										<p>".wfMessage('nfd_congrats_3')->text()."</p>
										</div></div>";

			$result['nfd_reasons_link'] = $this->getNfdReasonsLink();
			$result['nfd_reasons']	= $this->getNfdReasonsDropdown($nfd_type);
		}
		return $result;
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgRequest, $wgTitle;

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ( !$wgUser->isSysop() && !in_array( 'nfd', $wgUser->getGroups()) ) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($wgRequest->getVal('fetchInnards')) {
			//get next article to vote on
			$wgOut->disable();
			$result = self::getNextInnards($wgRequest->getVal('nfd_type'));
			print_r(json_encode($result));
			return;

		} elseif ($wgRequest->getVal('getVoteBlock')) {
			//get all the votes for the right rail module
			$wgOut->setArticleBodyOnly(true);
			$wgOut->addHTML(self::getVoteBlock($wgRequest->getVal('nfd_id')));
			return;

		} elseif ( $wgRequest->getVal('edit') ) {
			//get the html that goes into the page when a user clicks the edit tab
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if ($t) {
				$a = new Article($t);
				$editor = new EditPage( $a );
				$editor->edit();

				//Old code for when we wanted to remove
				//the nfd template from the edit window
				/*$content = $wgOut->getHTML();
				$wgOut->clearHTML();

				//grab the edit form
				$data = array();
				$data['form'] = $content;

				//then take out the template
				$c = new NFDProcessor();
				$template = $c->getFullTemplate($wgRequest->getVal('nfd_id'));
				$articleContent = $a->getContent();
				$articleContent = str_replace($template, "", $articleContent);
				$data['newContent'] = $articleContent;
				print_r(json_encode($data));*/
			}
			return;
		} elseif ( $wgRequest->getVal('discussion')) {
			//get the html that goes into the page when a user clicks the discussion tab
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if ($t) {
				$tDiscussion = $t->getTalkPage();
				if ($tDiscussion) {
					$a = new Article($tDiscussion);
					$content = $a->getContent();
					$wgOldTitle = $wgTitle;
					$wgTitle = $tDiscussion;
					$wgOut->addHTML($wgOut->parse($content));
					$postComment = new PostComment;
					$wgOut->addHTML($postComment->getForm(true, $tDiscussion, true));
					$wgTitle = $wgOldTitle;
				}
			}
			return;
		} elseif ($wgRequest->getVal( 'confirmation' )) {
			//get confirmation dialog after user has edited the article
			$wgOut->setArticleBodyOnly(true);
			echo $this->confirmationModal($wgRequest->getVal('articleId')) ;
			return;

		} elseif ($wgRequest->getVal('history')) {
			//get the html that goes into the page when a user clicks the history tab
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if ($t) {
				$historyContext = clone $this->getContext();
				$historyContext->setTitle( $t );
				$historyContext->setWikiPage( WikiPage::factory( $t ) );

				$pageHistory = Action::factory("history", WikiPage::factory( $t ), $historyContext);
				$pageHistory->onView();

				return;
			}
		} elseif ($wgRequest->getVal('helpful')) {
			//get the html that goes into the page when a user clicks the helpfulness tab
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if ($t && $t->exists()) {
				echo PageHelpfulness::getJSsnippet("article");
				return;
			}
		} elseif ($wgRequest->getVal('diff')) {
			//get the html that goes into the page when a user asks for a diffs
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if ($t) {
				$a = new Article($t);
				$wgOut->addHtml('<div class="article_inner">');
				$a->view();
				$wgOut->addHtml('</div>');
			}
			return;
		} elseif ($wgRequest->getVal('article')) {
			//get the html that goes into the page when a user clicks the article tab
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromId($wgRequest->getVal('articleId'));
			if ($t) {
				$r = Revision::newFromTitle($t);
				if ($r) {
					$popts = $wgOut->parserOptions();
					$popts->setTidy(true);
					echo WikihowArticleHTML::processArticleHTML($wgOut->parse($r->getText(), $t, $popts), array('no-ads'=> true, 'ns' => $t->getNamespace()));
				}
			}
			return;

		} elseif ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true);
			if ($wgRequest->getVal('submitEditForm')) {
				//user has edited the article from within the NFD Guardian tool
				$wgOut->disable();
				$this->submitEdit();
				$result = self::getNextInnards($wgRequest->getVal('nfd_type'));
				print_r(json_encode($result));
				return;
			} else {
				//user has voted
				if ($wgRequest->getVal('nfd_skip', 0) == 1) {
					NFDProcessor::skip($wgRequest->getVal('nfd_id'));
				} else {
					NFDProcessor::vote($wgRequest->getVal('nfd_id'), $wgRequest->getVal('nfd_vote'));
				}
				$wgOut->disable();
				$result = self::getNextInnards($wgRequest->getVal('nfd_type'));
				print_r(json_encode($result));
				return;
			}
		}

		/**
		 * This is the shell of the page, has the buttons, etc.
		 */
		$wgOut->addModules('jquery.ui.dialog');
		$wgOut->addModules( ['ext.wikihow.nfd_guardian', 'ext.wikihow.editor_script'] );
		$wgOut->addModules( ['ext.wikihow.diff_styles', 'ext.wikihow.pagehelpfulness'] );

		//add delete confirmation to bottom of page
		$wgOut->addHtml("<div class='waiting'><img src='" . wfGetPad('/extensions/wikihow/rotate.gif') . "' alt='' /></div>");
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$wgOut->addHTML($tmpl->execute('NFDdelete.tmpl.php'));
		$wgOut->setHTMLTitle(wfMessage('nfd'));
		$wgOut->setPageTitle(wfMessage('nfd'));

		// Load UsageLogs for tracking
		$wgOut->addModules('ext.wikihow.UsageLogs');

		// add standings widget
		$group= new NFDStandingsGroup();
		$indi = new NFDStandingsIndividual();

		$indi->addStatsWidget();
		$group->addStandingsWidget();
	}

	// used by community dashboard widget
	public static function getUnfinishedCount(&$dbr) {
		$eligible = wfTimestamp(TS_MW, time() - NFDGuardian::NFD_WAITING);

		return $dbr->selectField('nfd',
			'count(*)',
			['nfd_patrolled' => 0, 'nfd_status' => NFDGuardian::NFD_AVAILABLE, "nfd_timestamp < '{$eligible}'"],
			__METHOD__);
	}

	/**
	 *
	 * Returns the html for the confirmation dialog
	 * after a user has edited an article
	 *
	 */
	function confirmationModal($articleId) {
		$html = '';
		$t = Title::newFromID($articleId);
		if ($t) {
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array(
				'titleUrl' => $t->getLocalURL(),
				'title' => $t->getText(),
			));

			$html = $tmpl->execute('NFDconfirmation.tmpl.php');
		}
		return $html;
	}

	/**
	 *
	 * Returns the html for voting module
	 * in the right rail for a given
	 * NFD Id.
	 *
	 */
	function getVoteBlock($nfd_id) {
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow('nfd', '*', ['nfd_id' => $nfd_id], __METHOD__);

		$html .= self::getDeleteKeepVotes($nfd_id, $row->nfd_delete_votes, $row->nfd_keep_votes, $row->nfd_admin_delete_votes, $row->nfd_admin_keep_votes, $row->nfd_page);

		return $html;
	}

	/**
	 *
	 * Handles an edit the user has made.
	 *
	 */
	function submitEdit() {
		global $wgRequest;

		$nfd_id = $wgRequest->getVal('nfd_id');
		$t = Title::newFromID($wgRequest->getVal('articleId'));
		if ($t) {
			$a = new Article($t);
			//log the edit
			$params = array();
			$log = new LogPage( 'nfd', true ); // false - dont show in recentchanges
			$msg = wfMessage('nfd_edit_log_message')->rawParams("[[{$t->getText()}]]")->escaped();
			$log->addEntry('edit', $t, $msg, $params);

			$text = $wgRequest->getVal('wpTextbox1');
			$summary = $wgRequest->getVal('wpSummary');

			//check to see if there is still an nfd tag
			$c = new NFDProcessor();
			if (NFDProcessor::hasNFD($text)) {
				//there is an NFD tag still, so lets make sure its the same one
				$fullTemplate = $c->getFullTemplateFromText($text);
				if (strpos($text, $fullTemplate) === false) {
					//nfd template has changed
					$newFullTemplate = $c->getFullTemplateFromText($text);
					$nfdReason = $c->extractReason($newFullTemplate);

					$dbw = wfGetDB(DB_MASTER);
					$dbw->update('nfd', array('nfd_template' => $newFullTemplate, 'nfd_reason' => $nfdReason['type']), array('nfd_id' => $nfd_id));
				}
			}

			if ($a) {
				//save the edit
				$a->doEdit($text, $summary);
			}

			if ($wgRequest->getval('removeTemplate') == 'true') {
				//they vote to remove template, which is the same as vote to keep
				NFDProcessor::save($wgRequest->getInt('nfd_id'), $t);
			} else {
				//they didn't want to remove template, so that's like a skip
				NFDProcessor::skip($wgRequest->getInt('nfd_id'));
			}
		}
	}

	/**
	 *
	 * Get the keep/delete votes html for the right rail
	 *
	 */
	function getDeleteKeepVotes($nfd_id, $act_d, $act_k, $act_d_a, $act_k_a, $nfd_page) {
		$t = NFDProcessor::getTitleFromnfdID($nfd_id);

		$req_d = NFDProcessor::getDeleteVotesRequired($act_k);
		$req_d_a = NFDProcessor::getAdminDeleteVotesRequired();
		$req_k = NFDProcessor::getKeepVotesRequired();
		$req_k_a = NFDProcessor::getAdminKeepVotesRequired();

		$dbr = wfGetDB(DB_SLAVE);

		if ($t) {
			$link = "<a href='{$t->getFullURL()}' target='new'>" . wfMessage('howto', $t->getText()) . "</a>";
		} else {
			//the article has been deleted, so grab out of the archive
			$title = $dbr->selectField( 'archive',
				'ar_title',
				['ar_page_id' => $nfd_page],
				__METHOD__,
				['ORDER BY' => 'ar_timestamp DESC', 'LIMIT' => '1'] );
			if ($title) {
				$link = wfMessage('howto', str_replace( '-', ' ', $title ));
			}
		}

		$delete = array();
		$keep = array();
		$status = '';

		self::getDeleteKeep($delete, $keep, $nfd_id);

		$html .= "<div id='nfd_vote_1'><div class='nfd_vote_head'>Keep Votes</div>";

		//get keep boxes
		$foundAdmin = $req_k_a > 0 ? false : true;
		for ($i = 0; $i < count($keep); $i++) {
			$html .= self::getActualAvatar($keep[$i], $foundAdmin);
		}
		for ($i = $act_k; $i < $req_k; $i++) {
			$html .= self::getNeededAvatar($foundAdmin);
		}

		$html .= "</div><div id='nfd_vote_2'>";

		//get left arrow
		if ($act_k >= $req_k && $act_k_a >= $req_k_a) {
			$html .= "<div class='nfd_arrow nfd_left_win'></div>";
			$status = 'removed';
		} else {
			$html .= "<div class='nfd_arrow nfd_left'></div>";
		}
		//get right arrow
		if ($act_d >= $req_d && $act_d_a >= $req_d_a) {
			$html .= "<div class='nfd_arrow nfd_right_win'></div>";
			$status = 'approved';
		} else {
			$html .= "<div class='nfd_arrow nfd_right'></div>";
		}
		$html .= "</div><div id='nfd_vote_3'><div class='nfd_vote_head nfd_head_no'>Delete Votes</div>";

		//get delete boxes
		$foundAdmin = $req_d_a > 0 ? false : true;
		for ($i = 0; $i < count($delete); $i++) {
			$html .= self::getActualAvatar($delete[$i], $foundAdmin);
		}

		for ($i = $act_d; $i < $req_d; $i++) {
			$html .= self::getNeededAvatar($foundAdmin);
		}

		$html .= '</div>';

		if (!$status && count($delete)+count($keep) > 1) {
			$status = 'tie';
		}

		//grab main image
		$img = "<div class='nfd_vote_img nfd_img_$status'></div>";

		$top = "<div id='nfd_vote_text'>$img" . wfMessage('nfdvote_'.$status, $link)->text() . "</div>";

		//add it all up
		$html = "$top<div id='nfd_votes'>$html</div><div class='clearall'></div>";

		return $html;
	}

	/**
	 *
	 * For the given NFD id, populates the delete and keep arrays
	 * with the current votes for this article.
	 *
	 */
	static function getDeleteKeep(&$delete, &$keep, $nfd_id) {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select( 'nfd_vote',
			['nfdv_user','nfdv_vote', 'nfdv_timestamp'],
			['nfdv_nfdid' => $nfd_id],
			__METHOD__,
			['ORDER BY' => 'nfdv_timestamp DESC'] );

		foreach ($res as $row) {
			if ($row->nfdv_vote == '1') {
				array_push($delete, $row->nfdv_user);
			} else {
				array_push($keep, $row->nfdv_user);
			}
		}
	}

	/**
	 *
	 * For the given userId, returns the html for that user's
	 * avatar. Also makes $foundAdmin true if the current user
	 * is an admin.
	 *
	 */
	static function getActualAvatar($user_id, &$foundAdmin) {
		if ($user_id) {
			$u = new User();
			$u->setID($user_id);

			if ($u->loadFromDatabase()) {
				$foundAdmin = $foundAdmin || ($u->getGroups() && $u->isSysop());
			}

			$img = Avatar::getAvatarURL($u->getName());
			if (!$img) {
				$img = Avatar::getDefaultPicture();
			} else {
				$img = "<img src='$img' />";
			}
			$avatar = "<div class='nfd_avatar'><a href='{$u->getUserPage()->getFullURL()}' target='_blank' class='tooltip'>{$img}</a>";
			$avatar .= "<span class='tooltip_span'>Hi, I'm {$u->getName()}</span></div>";
		}
		return $avatar;
	}

	/**
	 *
	 * Returns the html for an empty vote in the right
	 * rail module. If an admin hasn't voted, then makes
	 * it an "admin" space.
	 *
	 */
	static function getNeededAvatar(&$foundAdmin) {
		$avatar = "<div class='nfd_emptybox'>" . ($foundAdmin ? "" : "Admin") . "</div>";
		$foundAdmin = $foundAdmin || true;

		return $avatar;
	}

	/**
	 *
	 * For a given user id, returns the html
	 * for an avatar to be displayed on the right
	 * rail or in the info box
	 *
	 */
	static function getUserInfo($user_id) {
		if ($user_id) {
			$u = new User();
			$u->setID($user_id);

			$img = Avatar::getAvatarURL($u->getName());
			if (!$img) {
				$img = Avatar::getDefaultPicture();
			} else {
				$img = "<img src='$img' />";
			}
			$avatar = "<span><a href='{$u->getUserPage()->getFullURL()}' target='_blank' class='tooltip'>{$img}</a>";
			$avatar .= "<span class='tooltip_span'>Hi, I'm {$u->getName()}</span></span>";
			$avatar .= "<a target='new' href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>";
		}
		return $avatar;
	}

	/**
	 *
	 * Return the total number of discussion messages
	 * for the given article.
	 *
	 */
	static function getDiscussionCount($pageId) {
		$t = Title::newFromId($pageId);
		if ($t) {
			$dt = Title::newFromText($t->getText(), NS_TALK);
			if ($dt) {
				$article = new Article($dt);
				return substr_count($article->getContent(), "de_user");
			}
		}
		return 0;
	}

	/**
	 *
	 * Returns the html for the dropdown for users
	 * to select which types of NFD's to show
	 * the user
	 *
	 */
	function getNfdReasonsDropdown($defaultValue='all') {
		$html = "<div id='nfd_reasons' class='tool_options'><span>" . wfMessage('nfd_dropdown_text') . "<select>";
		$html .= "<option value='all'>all</option>";

		$reasons = $this->getNfdReasons();
		foreach ($reasons as $key => $value) {
			$selected = $key == $defaultValue ? " selected='yes' " : "";
			$html .= "<option value='{$key}'{$selected}>{$key}</option>";
		}

		$html .= "</select></span><a id='nfdrules_submit' class='button secondary' href='#'>Done</a>";
		$html .= "<div class='clearall'></div></div>";
		return $html;
	}

	/**
	 *
	 * Returns the html for the link the shows/hides the reasons dropdown
	 *
	 */
	function getNfdReasonsLink($defaultValue='all') {
		$html = "<span id='nfd_reasons_link' class='tool_options_link'>(<a href='#' class='nfd_options_link'>Change Options</a>)</span>";
		return $html;
	}

	/**
	 *
	 * Returns an array of all possible nfd reasons
	 * that show up in the nfd templates
	 *
	 */
	static function getNfdReasons() {
		global $wgMemc;

		$key = wfMemcKey("nfdreasons");
		$result = $wgMemc->get($key);
		if ($result) {
			return $result;
		}
		$reasons = array();
		$t = Title::makeTitle(NS_TEMPLATE, "Nfd");
		$r = Revision::newFromTitle($t);
		preg_match_all("@\| [a-z]+ = .*@m", $r->getText(), $matches);
		$reasons = array();
		foreach ($matches[0] as $match) {
			preg_match_all('@^| ([a-z]+[^\[]) = \[\[[^\]]*\]\](.*)$@', $match, $m);
			//now grab the short code from the match
			$shortReason = $m[1][1];
			//now grab the long reason from the match
			$longReason = $m[2][1];

			if ($shortReason) {
				$reasons[$shortReason] = $longReason;
			}
		}

		$wgMemc->set($key, $reasons);

		return $reasons;
	}

	/*************
	 * This function is used in the maintenance script
	 * (/maintenance/importNfdArticles.php)
	 * to import all NFD articles into the NFD tables.
	 */
	public static function importNFDArticles() {

		$dbr = wfGetDB(DB_SLAVE);

		$count = 0;

		$resultsNFD = array();

		$res = $dbr->select(
			['templatelinks', 'page'],
			'page_id',
			[ 'tl_from = page_id',
				'page_namespace' => '0',
				"tl_title IN ('NFD', 'Nfd')" ],
			__METHOD__);
		foreach ($res as $row) {
			$pageids[] = $row->page_id;
		}

		foreach ($pageids as $pageid) {
			$entries = $dbr->selectField('nfd',
				'count(*)',
				[ 'nfd_page' => $pageid,
					'nfd_patrolled' => 0,
					'(nfd_status = ' . NFDGuardian::NFD_AVAILABLE . ' OR nfd_status = ' . NFDGuardian::NFD_ADVANCED . ')' ]);
			if ($entries == 0) {
				$t = Title::newFromID($pageid);
				if ($t && $t->exists()) {
					$article = new Article($t);
					$revision = Revision::newFromTitle($t);
					if ($article && $revision) {
						$l = new NFDProcessor($revision, $article);
						$l->process(true);
					}
				}
			}
		}
		//echo "Imported a total of " . $count . " articles.\n";
	}

	public static function checkArticlesInNfdTable() {
		$dbr = wfGetDB(DB_SLAVE);

		$count = 0;

		$results = array();
		$res = $dbr->select( 'nfd',
			['nfd_id', 'nfd_page', 'nfd_reason'],
			['nfd_patrolled' => '0', "(nfd_status = '" . NFDGuardian::NFD_AVAILABLE . "' OR nfd_status = '" . NFDGuardian::NFD_ADVANCED . "')"],
			__METHOD__);
		foreach ($res as $row) {
			$results[] = $row;
		}

		foreach ($results as $result) {
			$t = Title::newFromID($result->nfd_page);
			if ($t) {
				$a = new Article($t);
				/*if ($result->nfd_reason == "dup") {
					NFDProcessor::markPreviousAsInactive($result->nfd_page);
					echo "Removing Dup: " . $t->getText() . "\n";
					$count++;
				} else*/ if ($a->isRedirect()) {
					//check if its a redirect
					NFDProcessor::markPreviousAsInactive($result->nfd_page);
					echo "Removing Redirect: " . $t->getText() . "\n";
					$count++;
				} else {
					//check to see if it still has an NFD tag
					$revision = Revision::newFromTitle($t);
					if ($a && $revision) {
						$l = new NFDProcessor($revision, $a);
						$l->process(true);
					}
				}
			} else {
				//title doesn't exist, so remove it from the db
				NFDProcessor::markPreviousAsInactive($result->nfd_page);
				echo "Title no longer exists: " . $result->nfd_page . "\n";
				$count++;
			}
		}

		//echo "Removed a total of " . $count . " articles from tool.\n";
	}

}

class NFDDup extends QueryPage {
	function __construct( $name = 'NFDDup' ) {
		parent::__construct( $name );
	}

	function getName() {
		return "NFDDup";
	}

	function isExpensive() {
		# page_counter is not indexed
		return false;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		return "SELECT nfd_page, nfd_fe_timestamp, page_touched as value FROM nfd LEFT JOIN page ON nfd_page = page_id WHERE nfd_status = " . NFDGuardian::NFD_DUP . " GROUP BY nfd_page";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$title = Title::newFromID($result->nfd_page);
		if ($title) {
			$revision = Revision::newFromTitle($title);
			$previsionRevision = $revision->getPrevious();
			$article = new Article($title);
			if ($revision != null) {
				$link = $wgLang->date( $revision->getTimestamp() ) . " " . $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ), 'redirect=no', " (" . number_format($previsionRevision->getSize(), 0, "", ",") . " bytes, " . number_format($article->getCount(), 0, "", ",") . " Views) " );
				$redirectTitle = Title::newFromRedirect($revision->getText());
				if ($redirectTitle) {
					$link .= " => " . $skin->makeKnownLinkObj( $redirectTitle, htmlspecialchars( $wgContLang->convert( $redirectTitle->getPrefixedText() ) ) );
				}
			}
		}
		return $link;
	}

	function getPageHeader() {
		global $wgOut;
		$wgOut->setPageTitle("NFD Duplicates Deleted");
		$wgOut->setHTMLTitle('NFD Dup - wikiHow');
		return;
	}
}

class NFDAdvanced extends QueryPage {

	function __construct( $name = 'NFDAdvanced' ) {
		parent::__construct( $name );
	}

	function getName() {
		return "NFDAdvanced";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}

	function isSyndicated() { return false; }

	function getSQL() {
		return "SELECT nfd_page as title, " . NS_MAIN . " as namespace, nfd_fe_timestamp as value FROM nfd WHERE nfd_status = " . NFDGuardian::NFD_ADVANCED . " GROUP BY nfd_page";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$title = Title::newFromID($result->title);
		if ($title) {
			$link = $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
		}
		return $link;
	}
}
