<?php

/***********************
 *
 *  A class to assist with articles that have added/subtracted NFD templates
 *
 ***********************/
class NFDProcessor {

	var $mTitle     = null; // Title object of article we are processing
	var $mPageID    = 0;    // Page ID of the article we are processing
	var $mResult    = null; // result row from the db
	var $mTemplate  = null; // full template on the article (eg: {{nfd|acc|date}}
	var $mReason    = null; // they type of nfd in the form of an array('type':dup,'article':articleTitle)
	var $mTemplatePart = "nfd";
	var $mRevision  = null; // current revision of the current article we are processing

	public function __construct($revision = null, $wikiPage = null) {
		$this->mRevision    = $revision;
		$this->mTitle       = $wikiPage ? $wikiPage->getTitle() : null;
		$this->mPageID      = $wikiPage ? $wikiPage->getID() : 0;
	}

	/**
	 * Processes the given article. Checks to see if a
	 * NFD template exists, and if so, handles accordingly
	 * NOTE: called by hooks in NFDGuardian.php
	 */
	public function process($echoInfo = false) {
		$wikitext = ContentHandler::getContentText( $this->mRevision->getContent() );
		if (self::hasNFD($wikitext)) { //currently has NFD tag
			//now grab all the relevant information from this tag
			$this->mTemplate = $this->getFullTemplateFromText($wikitext);
			$this->mReason = self::extractReason($this->mTemplate);
			$this->setFirstEdit();

			//now check to see if we actually need to add it in to the db
			/*if ($this->mReason['type'] == "dup") {
				//we don't use duplicates in this tool
				self::markPreviousAsInactive($this->mPageID);
			} else*/ if (self::hasInuseTemplate($wikitext)) {
				//we don't put articles with inuse tags in the tool
				//remove if already in tool
				self::markPreviousAsInactive($this->mPageID);
				if ($echoInfo) {
					print "Removing from tool: " . $this->mTitle->getText() . "\n";
				}
			} elseif ($this->hasBeenDecided()) {
				//its already been decided at another point in time (either in NFDGuardian or in regular discussions
				//now make it advanced
				$this->markAsAdvanced($this->mPageID);
				if ($echoInfo) {
					print "Marking as Advanced: " . $this->mTitle->getText() . "\n";
				}
			} elseif (!$this->availableOrAdvancedInTool($this->mPageID)) {
				$this->logEntry(NFDGuardian::NFD_AVAILABLE);
				if ($echoInfo) {
					print "Adding: " . $this->mTitle->getText() . "\n";
				}
			} else {
				//already in tool, so no need to do anything
			}
		} else { //currently doesn't have NFD tag
			self::markPreviousAsInactive($this->mPageID);
			if ($echoInfo) {
				print "Removing from tool: " . $this->mTitle->getText() . "\n";
			}
		}
	}

	public static function hasNFD($text) {
		return preg_match("@{{nfd@i", $text);
	}

	private static function hasInuseTemplate($wikitext) {
		return preg_match("@{{inuse@i", $wikitext);
	}

	/* unused in 3/2019 - Reuben
	function availableInTool() {
		$dbr = wfGetDB(DB_REPLICA);

		$entries = $dbr->selectField('nfd',
			'count(*)',
			['nfd_page' => $this->mPageID,
				'nfd_patrolled' => 0,
				'nfd_status' => NFDGuardian::NFD_AVAILABLE],
			__METHOD__);

		return $entries > 0;
	}
	*/

	private function hasBeenDiscussed($title) {
		if ($title) {
			$discussionTitle = Title::newFromText($title->getText(), NS_TALK);
			if ($discussionTitle) {
				$discussionPage = WikiPage::factory($discussionTitle);
				$wikitext = ContentHandler::getContentText( $discussionPage->getContent() );
				$matches = array();
				$count = preg_match('/{{nfd.*[^{{]}}/i', $wikitext, $matches);
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

	private function hasBeenPatrolled($page_id) {
		$dbr = wfGetDB(DB_REPLICA);

		$count = $dbr->selectField('nfd', 'count(*)', array('nfd_page' => $page_id, 'nfd_patrolled' => "1"));

		return $count > 0;
	}

	private function hasBeenDecided() {
		return $this->hasBeenDiscussed($this->mTitle) || $this->hasBeenPatrolled($this->mPageID);
	}

	private function markAsAdvanced($articleId) {
		//check to see if it exists in the table
		$hasEntry = $this->existsInTool();
		if ($hasEntry) {
			self::markPreviousAsAdvanced($articleId);
		} else {
			$this->logEntry(NFDGuardian::NFD_ADVANCED);
		}
	}

	private function existsInTool() {
		$dbw = wfGetDB(DB_MASTER);

		$articleId = $this->mPageID;
		$count = $dbw->selectField('nfd', 'count(*)', ['nfd_page'=> $articleId], __METHOD__);
		return $count > 0;
	}

	private function availableOrAdvancedInTool($pageId) {
		$dbr = wfGetDB(DB_REPLICA);

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
	 * NOTE: called by NFDGuardian
	 */
	public static function extractReason($nfdTemplate) {
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
	private function getFullTemplate($nfdid = 0) {
		if ($this->mTemplate != null) {
			return $this->mTemplate;
		} else {
			$dbr = wfGetDB(DB_REPLICA);
			$template = $dbr->selectField('nfd', 'nfd_template', ['nfd_id' => $nfdid], __METHOD__);
			return $template;
		}
	}

	/**
	 *  Removes an unneeded NFD entry from the nfd table
	 *  if the title doesn't exist in the db
	 * NOTE: unused 3/2019
	function deleteBad($nfd_page) {
		// is there something we can delete ?
		$dbw = wfGetDB(DB_MASTER);
		$page_title = $dbw->selectField('page', 'page_title', ['page_id' => $nfd_page], __METHOD__);
		if (!$page_title) {
			$dbw->delete('nfd', array('nfd_page'=>$nfd_page));
		}
	}
	 */

	// Used in NFDGuardian
	public static function getTitleFromNFDID($nfdid) {
		$dbw = wfGetDB(DB_MASTER);
		$page_id = $dbw->selectField('nfd', 'nfd_page', ['nfd_id' => $nfdid], __METHOD__);
		$t = Title::newFromID($page_id);
		return $t;
	}

	/* unused in 3/2019
	static function markAsPatrolled($nfdid, $id) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("nfd",
			["nfd_patrolled" => 1],
			["nfd_id" => $nfdid],
			__METHOD__);
		self::markPreviousAsInactive($id);
	}
	*/

	private static function markAsDup($nfdid, $id) {
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

	private static function markPreviousAsAdvanced($id) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as advanced for this entry
		$dbw->update("nfd",
			["nfd_status" => NFDGuardian::NFD_ADVANCED],
			["nfd_page" => $id],
			__METHOD__);
	}

	private function logEntry($status) {
		$user = RequestContext::getMain()->getUser();

		$opts = array(	"nfd_action" => "added",
						"nfd_template" => $this->mTemplate,
						"nfd_reason" => $this->mReason['type'],
						"nfd_timestamp" => $this->mRevision->getTimestamp(),
						"nfd_fe_timestamp" => $this->mFirstEdit,
						"nfd_user" => $user->getID(),
						"nfd_user_text" => $user->getName(),
						"nfd_page" => $this->mPageID,
						"nfd_status" => $status
				);

		self::markPreviousAsInactive($this->mPageID);

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('nfd', $opts, __METHOD__);
	}

	// Called by NFDGuardian
	public function getNextToPatrolHTML() {
		global $wgParser;

		if ( !$this->mResult ) {
			// Nothing to patrol
			return null;
		}

		// Get the page title
		$title = Title::newFromID( $this->mResult->nfd_page );
		if ( !$title || !$title->exists() ) {
			self::markPreviousAsInactive( $this->mResult->nfd_page );
			return "<!--{$this->mResult->nfd_page}-->" .
				"error creating title (id# {$this->mResult->nfd_page}) , oops, please " .
				" <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// Get current page revsion
		$revision = Revision::newFromTitle( $title );
		if ( !$revision ) {
			return "Error creating revision for page ID: " . $this->mResult->nfd_page;
		}

		// Generate article preview
		$popts = RequestContext::getMain()->getOutput()->parserOptions();
		$popts->setTidy( true );
		$text = ContentHandler::getContentText( $revision->getContent() );
		$output = $wgParser->parse( $text, $title, $popts );
		$parserOutput = $output->getText();
		$magic = WikihowArticleHTML::grabTheMagic( $text );
		$html = WikihowArticleHTML::processArticleHTML(
			$parserOutput,
			array( 'no-ads' => true, 'ns' => $title->getNamespace(), 'magic-word' => $magic )
		);

		// Wrap article preview in template
		$tmpl = new EasyTemplate( __DIR__ );
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
	 * Marks the given nfd as viewed by the user (could
	 * be b/c of skip or vote)
	 */
	private static function markNFDAsViewed($nfdid) {
		global $wgMemc;
		$userid = RequestContext::getMain()->getUser()->getID();
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
	 * Gets a list of all articles previously viewed
	 * by the current user
	 */
	private static function getPreviouslyViewed() {
		global $wgMemc;
		$userid = RequestContext::getMain()->getUser()->getID();
		$key = wfMemcKey("nfduserlog");

		$log = $wgMemc->get($key);
		if (!$log || !isset($log[$userid])) {
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

	// Called by NFDGuardian
	public static function getNextToPatrol($type) {
		$user = RequestContext::getMain()->getUser();

		// grab the next one
		$dbw = wfGetDB(DB_MASTER);
		$expired = wfTimestamp(TS_MW, time() - NFDGuardian::NFD_EXPIRED);
		$eligible = wfTimestamp(TS_MW, time() - NFDGuardian::NFD_WAITING);

		$sql = "SELECT * from nfd left join nfd_vote ON nfd_id=nfdv_nfdid AND nfdv_user = {$user->getID()} "
			. " WHERE ( nfd_checkout_time < '{$expired}' OR nfd_checkout_time = '')
				AND nfd_patrolled = 0
				AND nfd_status = '" . NFDGuardian::NFD_AVAILABLE . "'
				AND nfd_user != {$user->getID()}
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
				['nfd_checkout_time' => wfTimestampNow(), 'nfd_checkout_user' => $user->getID()],
				['nfd_id' => $result->nfd_id],
				__METHOD__);
		}

		return $c;
	}

	private static function releaseNFD($nfdid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('nfd',
			['nfd_checkout_time' => "", 'nfd_checkout_user' => 0],
			['nfd_id' => $nfdid],
			__METHOD__);
		return true;
	}

	private static function markNFDPatrolled($nfdid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('nfd',
			['nfd_patrolled' => 1],
			['nfd_id' => $nfdid],
			__METHOD__);
		return true;
	}

	public static function save($nfdid, &$t) {
		$user = RequestContext::getMain()->getUser();
		$dbw = wfGetDB(DB_MASTER);

		$nfdUser = new User();
		$nfdUser->setName( 'NFD Voter Tool' );
		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('nfd_vote',
			'count(*)',
			['nfdv_user' => $user->getID(), 'nfdv_nfdid' => $nfdid],
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
		if ($user->isSysOp()) {
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
				['nfdv_user' => $user->getID(), 'nfdv_vote' => $voteint, 'nfdv_nfdid' => $nfdid, 'nfdv_timestamp' => wfTimestampNow()],
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
			$userName = $user->getName();
			$dateStr = RequestContext::getMain()->getLanguage()->date(wfTimestampNow());

			$comment = wfMessage('nfd_save_message')->rawParams("[[User:$userName|$userName]]", $dateStr)->escaped();
			$formattedComment = TalkPageFormatter::createComment( $nfdUser, $comment );

			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = ContentHandler::getContentText( $r->getContent() );
			}

			$text .= "\n\n$formattedComment\n\n";
			$wikiPage = WikiPage::factory($discussionTitle);
			$content = ContentHandler::makeContent($text, $discussionTitle);
			$wikiPage->doEditContent($content, "");
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
			Hooks::run("NFDVoted", array($user, $title, '0'));
		}
	}

	public static function vote($nfdid, $vote) {
		$user = RequestContext::getMain()->getUser();
		$dbw = wfGetDB(DB_MASTER);

		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('nfd_vote',
			'count(*)',
			array('nfdv_user' => $user->getID(),
				  'nfdv_nfdid' => $nfdid),
			__METHOD__);
		if ($count > 0) {
			self::releaseNFD($nfdid);
			return;
		}
		$opts = array();
		$voteCount = 0;
		if ($user->isSysOp()) {
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
			$dbw->insert('nfd_vote',
				array('nfdv_user' => $user->getID(),
					  'nfdv_vote' => $voteint,
					  'nfdv_nfdid' => $nfdid,
					  'nfdv_timestamp' => wfTimestampNow()),
				__METHOD__);
		}

		$row = $dbw->selectRow('nfd', '*', array('nfd_id'=>$nfdid), __METHOD__);

		// log the vote
		$title = Title::newFromID($row->nfd_page);
		if ($title) {
			$vote_param = $vote > 0 ? "deletevote" : "keepvote";
			$msg = wfMessage("nfdrule_log_{$vote_param}")->rawParams("[[{$title->getText()}]]")->escaped();

			$log = new LogPage( 'nfd', false );
			$log->addEntry('vote', $title, $msg, array($vote));
			Hooks::run("NFDVoted", array($user, $title, $vote));
		}

		// check, do we have to mark it as patrolled, or roll the change back?
		if ($vote) {
			if ($row->nfd_admin_delete_votes >= NFDProcessor::getAdminDeleteVotesRequired()
				&& $row->nfd_delete_votes >= NFDProcessor::getDeleteVotesRequired($row->nfd_keep_votes)
			) {
				self::markNFDPatrolled($nfdid);
				$c = new NFDProcessor();
				$nfdReason = self::extractReason($row->nfd_template);
				$c->deleteArticle($nfdid, $nfdReason);
			}
		} else {
			if ($row->nfd_admin_keep_votes >= NFDProcessor::getAdminKeepVotesRequired()
				&& $row->nfd_keep_votes >= NFDProcessor::getKeepVotesRequired()
			) {
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
	// Called by NFDGuardian
	public static function skip($nfdid) {
		self::markNFDAsViewed($nfdid);
	}

	// Called by NFDGuardian
	public function getFullTemplateFromText($text) {
		$matches = array();
		$count = preg_match('/{{nfd[^{{]*}}/i', $text, $matches);
		if (count($matches) > 0) {
			return $matches[0];
		} else {
			//none given
			return "none";
		}
	}

	private function setFirstEdit() {
		$dbr = wfGetDB(DB_REPLICA);
		$this->mFirstEdit = $dbr->selectField('firstedit',
			'fe_timestamp',
			['fe_page'=> $this->mPageID],
			__METHOD__);
	}

	// Called by NFDGuardian
	public static function getDeleteVotesRequired($currentKeepVotes) {
		global $wgNfdVotesRequired;

		if ($currentKeepVotes > 0) {
			return $wgNfdVotesRequired["advanced_delete"];
		} else {
			return $wgNfdVotesRequired["delete"];
		}
	}

	// Called by NFDGuardian
	public static function getAdminDeleteVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["admin_delete"];
	}

	// Called by NFDGuardian
	public static function getKeepVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["keep"];
	}

	// Called by NFDGuardian
	public static function getAdminKeepVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["admin_keep"];
	}

	/**
	 * Returns the html for the box at the top of NFD Guardian which
	 * contains information about the current article being voted on.
	 */
	private function getArticleInfoBox() {
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
			$wikiPage = WikiPage::factory($t);
			$pageHistory = new HistoryPage($wikiPage);
			$items = $pageHistory->fetchRevisions(100000,0,1);
			$edits = $items->numRows();

			$discussionTitle = Title::newFromText($t->getText(), NS_TALK);

			if ($discussionTitle) {

				$discussionPage = WikiPage::factory($discussionTitle);
				$pageHistory = new HistoryPage($discussionPage);
				$items = $pageHistory->fetchRevisions(100000,0,1);
				$discussion = $items->numRows();
			} else {
				$discussion = 0;
			}
		}

		$articleInfo = $this->getArticleInfo();
		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'age' => wfTimeAgo($this->mResult->nfd_fe_timestamp),
			'authorUrl' => $userLink,
			'authorName' => $userName,
			'views' => $articleInfo->page_counter,
			'nfd' => RequestContext::getMain()->getOutput()->parse($nfdLongReason),
			'edits' => $edits,
			'userEdits' => $uEdits,
			'nfdVotes' => $this->getTotalVotes($this->mResult->nfd_id),
			'discussion' => $discussion
		));

		$html = $tmpl->execute('NFDinfo.tmpl.php');
		return $html;
	}

	private static function replaceTemplatesInText(&$text, $pageId) {
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
	 * Returns the text for the all the votes listed
	 * in the info section.
	 */
	private function getTotalVotes($nfd_id) {
		$dbr = wfGetDB(DB_REPLICA);

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

	private function getArticleInfo() {
		$dbr = wfGetDB(DB_REPLICA);

		$row = $dbr->selectRow(array('page', 'firstedit'), '*', array('fe_page=page_id', 'page_id' => $this->mResult->nfd_page), __METHOD__);
		return $row;
	}

	/**
	 * Deletes the article with the given nfdid
	 * NOTE: called in NFDGuardian
	 */
	public function deleteArticle($nfdid, $nfdReason) {
		$nfdUser = new User();
		$nfdUser->setName( 'NFD Voter Tool' );

		// keep the article
		$dbr = wfGetDB(DB_REPLICA);

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

		$wikiPage = WikiPage::factory($t);
		if (!$wikiPage) {
			return false;
		}

		$dateStr = gmdate('n/j/Y', time());
		$votes = $this->getVotes($nfdid, $dbr);
		$comment = wfMessage('nfd_delete_message')->rawParams($dateStr, $nfdReason['type'], $votes['deleteUsers'], $votes['keepUsers'], "[[".$t->getText()."]]", number_format($wikiPage->getCount(), 0, "", ","))->escaped();

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
					$redirectText = "#REDIRECT [[" . $dupTitle->getPrefixedURL() . "]]";
					$content = ContentHandler::makeContent($redirectText, $t);
					$wikiPage->doEditContent($content, $comment);
					$foundDup = true;
					self::markAsDup($nfdid, $pageid);

					//log redirect in the nfd table
					$log = new LogPage('nfd', false);
					$log->addEntry('redirect', $t, $comment);

					$commentDup = wfMessage('nfd_dup_message')->rawParams($dateStr, $nfdReason['type'], $votes['deleteUsers'], $votes['keepUsers'], "[[".$t->getText()."]]", number_format($wikiPage->getCount(), 0, "", ","), "[[".$dupTitle->getText()."]]")->escaped();
					$formattedComment = TalkPageFormatter::createComment( $nfdUser, $commentDup );
					$discussionTitle = $t->getTalkPage();
					$text = "";
					if ($discussionTitle->getArticleId() > 0) {
						$r = Revision::newFromTitle($discussionTitle);
						$text = ContentHandler::getContentText( $r->getContent() );
					}

					//add a comment to the discussion page
					$discussionPage = WikiPage::factory($discussionTitle);
					$text .= "\n\n$formattedComment\n\n";
					$content = ContentHandler::makeContent($text, $discussionTitle);
					$discussionPage->doEditContent($content, "");
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
				$text = ContentHandler::getContentText( $r->getContent() );
			}

			//add a comment to the discussion page
			$discussionPage = WikiPage::factory($discussionTitle);
			$text .= "\n\n$formattedComment\n\n";
			$content = ContentHandler::makeContent($text, $discussionTitle);
			$discussionPage->doEditContent($content, "");

			//now delete the article
			$wikiPage->doDeleteArticle($comment);

			//no need to log in the deletion table b/c doDeleteArticle does it for you

			//log same delete in the nfd table
			$log = new LogPage('nfd', false);
			$log->addEntry('delete', $t, $comment);
		}

	}

	/**
	 * Helper function to get an array of all the votes
	 * to delete and keep for the given nfdid
	 */
	private function getVotes($nfdid, $dbr) {
		$votes = array();
		$votes['keepUsers'] = "";
		$votes['deleteUsers'] = "";
		$res = $dbr->select('nfd_vote', ['nfdv_user', 'nfdv_vote'], ['nfdv_nfdid' => $nfdid], __METHOD__);
		foreach ($res as $row) {
			$nfdvUser = User::newFromId($row->nfdv_user);
			if ($nfdvUser) {
				if ($row->nfdv_vote == 0) {
					if ($votes['keepUsers']) {
						$votes['keepUsers'] .= ", ";
					}
					$userName = $nfdvUser->getName();
					$votes['keepUsers'] .= "[[User:$userName|$userName]]";
				} else {
					if ($votes['deleteUsers']) {
						$votes['deleteUsers'] .= ", ";
					}
					$userName = $nfdvUser->getName();
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

	// NOTE: used in NFDGuardian.
	public function keepArticle($nfdid) {
		// keep the article
		$dbr = wfGetDB(DB_REPLICA);

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
		$text = ContentHandler::getContentText( $r->getContent() );

		//remove the template
		$text = preg_replace("@\{\{" . $this->mTemplatePart . "[^\}]*\}\}@i", "", $text);

		$wikiPage = WikiPage::factory($t);
		$content = ContentHandler::makeContent($text, $t);
		$summary = wfMessage('nfd_keep_summary_template', $this->mTemplatePart)->text();
		$editSuccess = $wikiPage->doEditContent( $content, $summary )->isOK();

		//now add a discussion message
		if ($editSuccess) {
			$nfdUser = new User();
			$nfdUser->setName( 'NFD Voter Tool' );
			$text = "";
			$discussionTitle = $t->getTalkPage();

			$votes = $this->getVotes($nfdid, $dbr);

			$fullTemplate = $this->getFullTemplate($nfdid);
			$nfdReason = self::extractReason($fullTemplate);
			$keepTemplate = "{{" . $this->mTemplatePart . "|" . $nfdReason['type'] . "|result=keep}}\n";
			$lang = RequestContext::getMain()->getLanguage();
			$dateStr = $lang->date(wfTimestampNow());

			$comment = $keepTemplate . wfMessage('nfd_keep_message')->rawParams($dateStr, $votes['keepUsers'], $votes['deleteUsers'])->escaped();
			$formattedComment = TalkPageFormatter::createComment( $nfdUser, $comment );

			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = ContentHandler::getContentText( $r->getContent() );
			}

			//add a discussion item
			$discussionPage = WikiPage::factory($discussionTitle);
			$text .= $formattedComment;
			$content = ContentHandler::makeContent($text, $discussionTitle);
			$discussionPage->doEditContent($content, "");

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

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'NFDGuardian' );
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	private static function getNextInnards($nfd_type) {
		// grab the next check
		$result = array();
		$c = NFDProcessor::getNextToPatrol($nfd_type);
		if ($c && $c->mTitle)  {
			// nfd_vote, nfd_skip
			$result['title'] 		= "<a href='{$c->mTitle->getLocalURL()}'>{$c->mTitle->getText()}</a>";
			$result['html'] 		= $c->getNextToPatrolHTML();
			$result['nfd_id'] 		= $c->mResult->nfd_id;
			$result['nfd_page']		= $c->mResult->nfd_page;
			$result['nfd_reasons_link'] = self::getNfdReasonsLink();
			$result['nfd_reasons']	= self::getNfdReasonsDropdown($nfd_type);
			$result['nfd_discussion_count'] = self::getDiscussionCount($c->mResult->nfd_page);
		} else {
			$result['done'] 		= 1;
			$result['title'] 		= wfMessage('nfd');
			$result['msg'] 			= "<div class='tool_header'><div id='nfd_options'></div>
										<div id='nfd_head'>
										<p class='nfd_alldone'>".wfMessage('nfd_congrats')."</p>
										<p>".wfMessage('nfd_congrats_3')->text()."</p>
										</div></div>";

			$result['nfd_reasons_link'] = self::getNfdReasonsLink();
			$result['nfd_reasons']	= self::getNfdReasonsDropdown($nfd_type);
		}
		return $result;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ( !$user->isSysop() && !in_array( 'nfd', $user->getGroups()) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($req->getVal('fetchInnards')) {
			//get next article to vote on
			$out->setArticleBodyOnly(true);
			$result = self::getNextInnards($req->getVal('nfd_type'));
			print(json_encode($result));
			return;

		} elseif ($req->getVal('getVoteBlock')) {
			//get all the votes for the right rail module
			$out->setArticleBodyOnly(true);
			$out->addHTML(self::getVoteBlock($req->getVal('nfd_id')));
			return;

		} elseif ( $req->getVal('edit') ) {
			//get the html that goes into the page when a user clicks the edit tab
			$out->setArticleBodyOnly(true);
			$t = Title::newFromID($req->getVal('articleId'));
			if ($t) {
				$a = new Article($t);
				$editor = new EditPage( $a );
				$editor->edit();

				//Old code for when we wanted to remove
				//the nfd template from the edit window
				/*$content = $out->getHTML();
				$out->clearHTML();

				//grab the edit form
				$data = array();
				$data['form'] = $content;

				//then take out the template
				$c = new NFDProcessor();
				$template = $c->getFullTemplate($req->getVal('nfd_id'));
				$articleContent = ContentHandler::getContentText( $a->getPage()->getContent() );
				$articleContent = str_replace($template, "", $articleContent);
				$data['newContent'] = $articleContent;
				print(json_encode($data));*/
			}
			return;
		} elseif ( $req->getVal('discussion')) {
			//get the html that goes into the page when a user clicks the discussion tab
			$out->setArticleBodyOnly(true);
			$t = Title::newFromID($req->getVal('articleId'));
			if ($t) {
				$tDiscussion = $t->getTalkPage();
				if ($tDiscussion) {
					$wikiPage = WikiPage::factory($tDiscussion);
					$wikitext = ContentHandler::getContentText( $wikiPage->getContent() );
					$oldTitle = RequestContext::getMain()->getTitle();
					RequestContext::getMain()->setTitle( $tDiscussion );
					$out->addHTML($out->parse($wikitext));
					$postComment = new PostComment;
					$out->addHTML($postComment->getForm(true, $tDiscussion, true));
					RequestContext::getMain()->setTitle( $oldTitle );
				}
			}
			return;
		} elseif ($req->getVal( 'confirmation' )) {
			//get confirmation dialog after user has edited the article
			$out->setArticleBodyOnly(true);
			print $this->confirmationModal($req->getVal('articleId')) ;
			return;

		} elseif ($req->getVal('history')) {
			//get the html that goes into the page when a user clicks the history tab
			$out->setArticleBodyOnly(true);
			$t = Title::newFromID($req->getVal('articleId'));
			if ($t) {
				$historyContext = clone $this->getContext();
				$historyContext->setTitle( $t );
				$historyContext->setWikiPage( WikiPage::factory( $t ) );

				$pageHistory = Action::factory("history", WikiPage::factory( $t ), $historyContext);
				$pageHistory->onView();

				return;
			}
		} elseif ($req->getVal('helpful')) {
			//get the html that goes into the page when a user clicks the helpfulness tab
			$out->setArticleBodyOnly(true);
			$t = Title::newFromID($req->getVal('articleId'));
			if ($t && $t->exists()) {
				print PageHelpfulness::getJSsnippet("article");
				return;
			}
		} elseif ($req->getVal('diff')) {
			//get the html that goes into the page when a user asks for a diffs
			$out->setArticleBodyOnly(true);
			$t = Title::newFromID($req->getVal('articleId'));
			if ($t) {
				$a = new Article($t);
				$out->addHtml('<div class="article_inner">');
				$a->view();
				$out->addHtml('</div>');
			}
			return;
		} elseif ($req->getVal('article')) {
			//get the html that goes into the page when a user clicks the article tab
			$out->setArticleBodyOnly(true);
			$t = Title::newFromId($req->getVal('articleId'));
			if ($t) {
				$r = Revision::newFromTitle($t);
				if ($r) {
					$popts = $out->parserOptions();
					$popts->setTidy(true);
					print WikihowArticleHTML::processArticleHTML($out->parse(ContentHandler::getContentText( $r->getContent() ), $t, $popts), array('no-ads'=> true, 'ns' => $t->getNamespace()));
				}
			}
			return;

		} elseif ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			if ($req->getVal('submitEditForm')) {
				//user has edited the article from within the NFD Guardian tool
				$out->disable();
				$this->submitEdit();
				$result = self::getNextInnards($req->getVal('nfd_type'));
				print(json_encode($result));
				return;
			} else {
				//user has voted
				if ($req->getVal('nfd_skip', 0) == 1) {
					NFDProcessor::skip($req->getVal('nfd_id'));
				} else {
					NFDProcessor::vote($req->getVal('nfd_id'), $req->getVal('nfd_vote'));
				}
				$out->disable();
				$result = self::getNextInnards($req->getVal('nfd_type'));
				print(json_encode($result));
				return;
			}
		}

		/**
		 * This is the shell of the page, has the buttons, etc.
		 */
		$out->addModules('jquery.ui.dialog');
		$out->addModules( ['ext.wikihow.nfd_guardian', 'ext.wikihow.editor_script'] );
		$out->addModules( ['ext.wikihow.diff_styles', 'ext.wikihow.pagehelpfulness'] );

		//add delete confirmation to bottom of page
		$out->addHtml("<div class='waiting'><img src='" . wfGetPad('/extensions/wikihow/rotate.gif') . "' alt='' /></div>");
		$tmpl = new EasyTemplate( __DIR__ );
		$out->addHTML($tmpl->execute('NFDdelete.tmpl.php'));
		$out->setHTMLTitle(wfMessage('nfd'));
		$out->setPageTitle(wfMessage('nfd'));

		// Load UsageLogs for tracking
		$out->addModules('ext.wikihow.UsageLogs');

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
	 * Returns the html for the confirmation dialog
	 * after a user has edited an article
	 */
	private function confirmationModal($articleId) {
		$html = '';
		$t = Title::newFromID($articleId);
		if ($t) {
			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'titleUrl' => $t->getLocalURL(),
				'title' => $t->getText(),
			));

			$html = $tmpl->execute('NFDconfirmation.tmpl.php');
		}
		return $html;
	}

	/**
	 * Returns the html for voting module
	 * in the right rail for a given
	 * NFD Id.
	 */
	private static function getVoteBlock($nfd_id) {
		$dbr = wfGetDB(DB_REPLICA);
		$row = $dbr->selectRow('nfd', '*', ['nfd_id' => $nfd_id], __METHOD__);

		$html .= self::getDeleteKeepVotes($nfd_id, $row->nfd_delete_votes, $row->nfd_keep_votes, $row->nfd_admin_delete_votes, $row->nfd_admin_keep_votes, $row->nfd_page);

		return $html;
	}

	/**
	 * Handles an edit the user has made.
	 */
	private function submitEdit() {
		$req = $this->getRequest();
		$nfd_id = $req->getVal('nfd_id');
		$t = Title::newFromID($req->getVal('articleId'));
		if ($t) {
			//log the edit
			$params = array();
			$log = new LogPage( 'nfd', true ); // false - dont show in recentchanges
			$msg = wfMessage('nfd_edit_log_message')->rawParams("[[{$t->getText()}]]")->escaped();
			$log->addEntry('edit', $t, $msg, $params);

			// TODO: We should probably validate a CSRF token here
			$text = $req->getVal('wpTextbox1');
			$summary = $req->getVal('wpSummary');

			//check to see if there is still an nfd tag
			$c = new NFDProcessor();
			if (NFDProcessor::hasNFD($text)) {
				//there is an NFD tag still, so lets make sure its the same one
				$fullTemplate = $c->getFullTemplateFromText($text);
				if (strpos($text, $fullTemplate) === false) {
					//nfd template has changed
					$newFullTemplate = $c->getFullTemplateFromText($text);
					$nfdReason = NFDProcessor::extractReason($newFullTemplate);

					$dbw = wfGetDB(DB_MASTER);
					$dbw->update('nfd', array('nfd_template' => $newFullTemplate, 'nfd_reason' => $nfdReason['type']), array('nfd_id' => $nfd_id));
				}
			}

			$wikiPage = WikiPage::factory($t);
			if ($wikiPage) {
				// save the edit
				$content = ContentHandler::makeContent($text, $t);
				$wikiPage->doEditContent($content, $summary);
			}

			if ($req->getval('removeTemplate') == 'true') {
				//they vote to remove template, which is the same as vote to keep
				NFDProcessor::save($req->getInt('nfd_id'), $t);
			} else {
				//they didn't want to remove template, so that's like a skip
				NFDProcessor::skip($req->getInt('nfd_id'));
			}
		}
	}

	/**
	 * Get the keep/delete votes html for the right rail
	 */
	private static function getDeleteKeepVotes($nfd_id, $act_d, $act_k, $act_d_a, $act_k_a, $nfd_page) {
		$t = NFDProcessor::getTitleFromnfdID($nfd_id);

		$req_d = NFDProcessor::getDeleteVotesRequired($act_k);
		$req_d_a = NFDProcessor::getAdminDeleteVotesRequired();
		$req_k = NFDProcessor::getKeepVotesRequired();
		$req_k_a = NFDProcessor::getAdminKeepVotesRequired();

		$dbr = wfGetDB(DB_REPLICA);

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
	 * For the given NFD id, populates the delete and keep arrays
	 * with the current votes for this article.
	 * NOTE: called by NFDProcessor
	 */
	public static function getDeleteKeep(&$delete, &$keep, $nfd_id) {
		$dbr = wfGetDB(DB_REPLICA);

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
	 * For the given userId, returns the html for that user's
	 * avatar. Also makes $foundAdmin true if the current user
	 * is an admin.
	 */
	private static function getActualAvatar($user_id, &$foundAdmin) {
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
	 * Returns the html for an empty vote in the right
	 * rail module. If an admin hasn't voted, then makes
	 * it an "admin" space.
	 */
	private static function getNeededAvatar(&$foundAdmin) {
		$avatar = "<div class='nfd_emptybox'>" . ($foundAdmin ? "" : "Admin") . "</div>";
		$foundAdmin = $foundAdmin || true;

		return $avatar;
	}

	/**
	 * For a given user id, returns the html
	 * for an avatar to be displayed on the right
	 * rail or in the info box
	 * NOTE: called by NFDProcessor
	 */
	public static function getUserInfo($user_id) {
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
	 * Return the total number of discussion messages
	 * for the given article.
	 */
	private static function getDiscussionCount($pageId) {
		$t = Title::newFromId($pageId);
		if ($t) {
			$dt = Title::newFromText($t->getText(), NS_TALK);
			if ($dt) {
				$wikiPage = WikiPage::factory($dt);
				$wikitext = ContentHandler::getContentText( $wikiPage->getContent() );
				return substr_count($wikitext, "de_user");
			}
		}
		return 0;
	}

	/**
	 * Returns the html for the dropdown for users
	 * to select which types of NFD's to show
	 * the user
	 */
	private static function getNfdReasonsDropdown($defaultValue='all') {
		$html = "<div id='nfd_reasons' class='tool_options'><span>" . wfMessage('nfd_dropdown_text') . "<select>";
		$html .= "<option value='all'>all</option>";

		$reasons = self::getNfdReasons();
		foreach ($reasons as $key => $value) {
			$selected = $key == $defaultValue ? " selected='yes' " : "";
			$html .= "<option value='{$key}'{$selected}>{$key}</option>";
		}

		$html .= "</select></span><a id='nfdrules_submit' class='button secondary' href='#'>Done</a>";
		$html .= "<div class='clearall'></div></div>";
		return $html;
	}

	/**
	 * Returns the html for the link the shows/hides the reasons dropdown
	 */
	private static function getNfdReasonsLink($defaultValue='all') {
		$html = "<span id='nfd_reasons_link' class='tool_options_link'>(<a href='#' class='nfd_options_link'>Change Options</a>)</span>";
		return $html;
	}

	/**
	 * Returns an array of all possible nfd reasons
	 * that show up in the nfd templates
	 * NOTE: called by NFDProcessor
	 */
	public static function getNfdReasons() {
		global $wgMemc;

		$key = wfMemcKey("nfdreasons");
		$result = $wgMemc->get($key);
		if ($result) {
			return $result;
		}
		$reasons = array();
		$t = Title::makeTitle(NS_TEMPLATE, "Nfd");
		$r = Revision::newFromTitle($t);
		preg_match_all("@\| [a-z]+ = .*@m", ContentHandler::getContentText( $r->getContent() ), $matches);
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
	 * (/maintenance/wikihow/importNfdArticles.php)
	 * to import all NFD articles into the NFD tables.
	 */
	public static function importNFDArticles() {
		$dbr = wfGetDB(DB_REPLICA);
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
					$wikiPage = WikiPage::factory($t);
					$revision = Revision::newFromTitle($t);
					if ($wikiPage && $revision) {
						$l = new NFDProcessor($revision, $wikiPage);
						$l->process(true);
					}
				}
			}
		}
		//print "Imported a total of " . $count . " articles.\n";
	}

	// Used in importNfdArticles.php
	public static function checkArticlesInNfdTable() {
		$dbr = wfGetDB(DB_REPLICA);

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
				$wikiPage = WikiPage::factory($t);
				/*if ($result->nfd_reason == "dup") {
					NFDProcessor::markPreviousAsInactive($result->nfd_page);
					print "Removing Dup: " . $t->getText() . "\n";
					$count++;
				} else*/ if ($wikiPage->isRedirect()) {
					// check if it's a redirect
					NFDProcessor::markPreviousAsInactive($result->nfd_page);
					print "Removing Redirect: " . $t->getText() . "\n";
					$count++;
				} else {
					// check to see if it still has an NFD tag
					$revision = Revision::newFromTitle($t);
					if ($wikiPage && $revision) {
						$l = new NFDProcessor($revision, $wikiPage);
						$l->process(true);
					}
				}
			} else {
				// title doesn't exist, so remove it from the db
				NFDProcessor::markPreviousAsInactive($result->nfd_page);
				print "Title no longer exists: " . $result->nfd_page . "\n";
				$count++;
			}
		}

		//print "Removed a total of " . $count . " articles from tool.\n";
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
		return "SELECT nfd_page, nfd_fe_timestamp, page_touched AS value " .
			   "FROM nfd " .
			   "LEFT JOIN page ON nfd_page = page_id " .
			   "WHERE nfd_status = " . NFDGuardian::NFD_DUP . " " .
			   "GROUP BY nfd_page";
	}

	function formatResult( $skin, $result ) {
		global $wgContLang;
		$lang = RequestContext::getMain()->getLanguage();
		$title = Title::newFromID($result->nfd_page);
		if ($title) {
			$revision = Revision::newFromTitle($title);
			$previsionRevision = $revision->getPrevious();
			$wikiPage = WikiPage::factory($title);
			if ($revision != null) {
				$link = $lang->date( $revision->getTimestamp() ) . " "
					. Linker::linkKnown( $title,
						htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ),
						[],
						['redirect' => 'no'] )
					. " (" . number_format($previsionRevision->getSize(), 0, "", ",") . " bytes, " . number_format($wikiPage->getCount(), 0, "", ",") . " Views) ";
				$wikiPage = WikiPage::factory($title);
				$redirectTitle = $wikiPage->getRedirectTarget();
				if ($redirectTitle) {
					$link .= " => " .
						Linker::linkKnown( $redirectTitle,
							htmlspecialchars( $wgContLang->convert( $redirectTitle->getPrefixedText() ) ) );
				}
			}
		}
		return $link;
	}

	function getPageHeader() {
		$out = RequestContext::getMain()->getOutput();
		$out->setPageTitle("NFD Duplicates Deleted");
		$out->setHTMLTitle('NFD Dup - wikiHow');
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
		return "SELECT nfd_page AS title, " . NS_MAIN . " AS namespace, nfd_fe_timestamp AS value " .
			   "FROM nfd " .
			   "WHERE nfd_status = " . NFDGuardian::NFD_ADVANCED . " " .
			   "GROUP BY nfd_page";
	}

	function formatResult( $skin, $result ) {
		global $wgContLang;
		$title = Title::newFromID($result->title);
		if ($title) {
			$link = Linker::linkKnown( $title,
				htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
		}
		return $link;
	}
}
