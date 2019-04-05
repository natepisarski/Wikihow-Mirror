<?php

abstract class QCRule {

	// flexibility if we want to track different namespaces
	var $mValidNamespaces = array(NS_MAIN);
	var $mArticle	= null;
	var $mAction	= '';
	var $mKey		= '';
	var $mResult	= null; // action item to patrol, a row from the qc table
	var $mTitle		= null;

	public function __construct($article) {
		global $wgHooks;
		$this->mArticle = $article;
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	protected function textRemoved($part, $oldtext, $newtext) {
		if (preg_match("@{$part}@i", $oldtext) && !preg_match("@{$part}@", $newtext)) {
			return true;
		}
		return false;
	}

	protected function textAdded($part, $oldtext, $newtext) {
		if (!preg_match("@{$part}@i", $oldtext) && preg_match("@{$part}@", $newtext)) {
			return true;
		}
		return false;
	}

	protected function hasText($part, $text) {
		return preg_match("@{$part}@i", $text);
	}

	protected function hasEntry($articleID) {
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField('qc',
			'count(*)',
			array('qc_page' => $articleID,
				'qc_patrolled' => 0,
				'qc_key' => $this->mKey),
			__METHOD__);
		return $count > 0;
	}

	/* unused 3/2019
	function textAddedOrRemoved($part, $oldtext, $newtext) {
		return $this->textAdded($part, $oldtext, $newtext) || $this->textRemoved($part, $oldtext, $newtext);
	}
	*/

	protected function textChanged($part, $oldtext, $newtext) {
		preg_match_all("@" . $part . "@iU", $oldtext, $matches1);
		preg_match_all("@" . $part . "@iU", $newtext, $matches2);
		return !($matches1 == $matches2);
	}

	// Called in subclasses
	abstract protected function flagAction();

	// Called by QG
	abstract public function getNextToPatrolHTML();

	// Called in TipsPatrol
	public function process() {
		if ($this->flagAction()) {
			return $this->logQCEntry();
		}
	}

	protected function getEntryOptions() {
		return array();
	}

	protected function getKey() {
		return $this->mKey;
	}

	protected function getAction() {
		return $this->mAction;
	}

	abstract protected function getYesVotesRequired();
	abstract protected function getNoVotesRequired();

	// Used in TipsPatrol
	public static function deleteIfNotPatrolled($qc_id, $qc_user) {
		if (!$qc_id) {
			return false;
		}

		$dbw = wfGetDB(DB_MASTER);

		return $dbw->delete('qc',
			array('qc_id' => $qc_id, 'qc_patrolled' => 0, 'qc_user' => $qc_user),
			__METHOD__);
	}

	protected function deleteBad($qc_page) {
		// is there something we can delete ?
		$dbw = wfGetDB(DB_MASTER);
		$page_title = $dbw->selectField('page', 'page_title', array('page_id' => $qc_page), __METHOD__);
		if (!$page_title) {
			$dbw->delete('qc', array('qc_page' => $qc_page), __METHOD__);
		}
	}

	// Called from QG
	public static function getTitleFromQCID($qcid) {
		$dbr = wfGetDB(DB_REPLICA);
		$page_id = $dbr->selectField('qc', array('qc_page'), array('qc_id' => $qcid), __METHOD__);

		// construct the HTML to reply
		// load the page
		$t = Title::newFromID($page_id);
		return $t;
	}

	protected static function getRevFromQCID($qcid) {
		$dbr = wfGetDB(DB_REPLICA);
		$rev_id = $dbr->selectField('qc', array('qc_rev_id'), array('qc_id' => $qcid), __METHOD__);

		// construct the HTML to reply
		// load the page
		$r = Revision::newFromID($rev_id);
		if (!$r) return null;
		return $r;
	}

	protected function markPreviousAsPatrolled() {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("qc",
			array("qc_patrolled" => 1),
			array("qc_page" => $this->mArticle->getID(),
				"qc_key" => $this->getKey()),
			__METHOD__);
	}

	public static function markAllAsPatrolled($title) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("qc",
			array("qc_patrolled" => 1),
			array("qc_page" => $title->getArticleID()),
			__METHOD__);
	}

	private function logQCEntry() {
		$user = RequestContext::getMain()->getUser();
		$opts = array(	"qc_key" => $this->getKey(),
						"qc_action" => $this->getAction(),
						"qc_timestamp" => wfTimestampNow(),
						"qc_user" => $user->getID(),
						"qc_user_text" => $user->getName(),
						"qc_yes_votes_req" 	=> $this->getYesVotesRequired(),
						"qc_no_votes_req" 	=> $this->getNoVotesRequired(),
						"qc_page" => $this->mArticle->getID(),
				);
		$opts = array_merge($this->getEntryOptions(), $opts);

		$this->markPreviousAsPatrolled();

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('qc', $opts, __METHOD__);

		return $dbw->insertId();
	}

	private static function getPreviouslyViewedExp() {
		$user = RequestContext::getMain()->getUser();
		$exp = 0;
		if (strtolower($user->getName()) == 'mqg') {
			// expire every 30 min for MQG
			$exp = 60 * 30;
		}
		return $exp;
	}

	/*****
	 * Functions for displaying the QC entry to the patrolling user and accepting votes
	 *
	 ****/

	private static function getCacheKey($userid) {
		return wfMemcKey('qcuserlog', $userid);
	}

	private static function markQCAsViewed($qcid) {
		global $wgMemc;
		$user = RequestContext::getMain()->getUser();
		$userid = $user->isAnon() ? $user->getName() : $user->getID();
		$key = self::getCacheKey($userid);
		$log = $wgMemc->get($key);
		if (!is_array($log)) {
			$log = array();
		}
		$log[] = $qcid;
		$wgMemc->set($key, $log, self::getPreviouslyViewedExp());
	}

	private static function getPreviouslyViewed() {
		global $wgMemc;
		$user = RequestContext::getMain()->getUser();
		$userid = $user->isAnon() ? $user->getName() : $user->getID();

		$key = self::getCacheKey($userid);

		$log = $wgMemc->get($key);
		if (!is_array($log)) {
			return "";
		}

		$good = array();
		foreach ($log as $u) {
			if (!preg_match("@[^0-9]@", $u) && $u != "") {
				$good[] = $u;
			}
		}
		$str = preg_replace("@,$@", "", implode(",", array_unique($good)));

		return $str;
	}

	// Called by QG
	public static function getNextToPatrol($type,$by_username) {
		$user = RequestContext::getMain()->getUser();

		if ($type == "") {
			//this is the default in QG, before actual options have been selected
			$hasTips = true;
		} else {
			$hasTips = stripos($type, "newtip");
		}

		if ($hasTips !== false && class_exists('Plants') && Plants::usesPlants("QGTip")) {
			$plants = new TipPlants();
			$result = $plants->getNextPlant();
		}

		$sql = '';
		$revUser = null;
		if ($result == null) {
			// grab the next one
			$dbw = wfGetDB(DB_MASTER);
			$expired = wfTimestamp(TS_MW, time() - 3600);

			$sql = "SELECT * FROM qc LEFT JOIN qc_vote ON qc_id=qcv_qcid AND qcv_user = {$user->getID()}
					WHERE ( qc_checkout_time < '{$expired}' OR qc_checkout_time = '')
					AND qc_patrolled = 0 AND qcv_qcid IS NULL
					AND qc_page > 0 AND qc_key != 'changedintroimage'";

			if ($user->isAnon()) {
				$sql .= " AND qc_user_text != {$dbw->addQuotes($user->getName())} ";
			} else {
				$sql .= " AND qc_user != {$user->getID()} ";
			}

			if (!empty($type)) {
				//fix up types string
				$key = strtolower(preg_replace("@qcrule_@", "", $type));
				$key = $dbw->strencode($key);
				$key = preg_replace("@/@", "_", $key);
				$key = preg_replace("@,@", "','", $key);

				$sql .= " AND qc_key IN ('$key') "; // $opts["qc_key"] = $key;
			}
			else {
				//get all (just video right now)
				$sql .= " AND qc_key IN (" . $dbw->makeList(QG::$defaultQGRules) . ") ";
			}

			$previous = $dbw->strencode(self::getPreviouslyViewed());
			if ($previous) {
				$sql .= " AND qc_id NOT IN ({$previous})";
			}

			if ($by_username) {
				$u = User::newFromName($by_username);
				if ($u) {
					$sql .= " AND qc_user = {$u->getID()} ";
				}
			}

			$sql .= self::getOrderBy($type) . " LIMIT 1";

			$res = $dbw->query($sql, __METHOD__);
			$result = $dbw->fetchObject($res);

			// if we have one, check it out of the queue so multiple people don't get the same item to review
			if ($result) {
				// mark this as checked out
				$dbw->update('qc',
					array('qc_checkout_time' => wfTimestampNow(),
						'qc_checkout_user' => $user->getID()),
					array('qc_id' => $result->qc_id),
					__METHOD__);
			}
			else {
				return null;
			}

			// TODO: should use MW Database interface
			$csql = 'SELECT COUNT(*) AS c FROM revision WHERE rev_page = '.$result->qc_page.' AND rev_id < '.$result->qc_rev_id.' AND rev_id > '.$result->qc_old_rev_id;
			$res = $dbw->query($csql, __METHOD__);
			$revUser = $dbw->fetchObject($res);
		}

		$c = null;
		$key = $result->qc_key;
		$c = self::newRuleFromKey($key);
		$c->mResult = $result;
		$c->mTitle = Title::newFromID($c->mResult->qc_page);
		$c->mUsers = $revUser ? $revUser->c : 0;
		$c->sql = $sql;
		return $c;
	}

	private static function newRuleFromKey($key) {
		$c = null;
		if (preg_match("@changedtemplate_@", $key)) {
			$template = preg_replace("@changedtemplate_@", "", $key);
			$c = new QCRuleTemplateChange($template);
		} elseif ($key == "changedvideo") {
			$c = new QCRuleVideoChange();
		} elseif ($key == "changedintroimage") {
			$c = new QCRuleIntroImage();
		} elseif ($key == "rcpatrol") {
			$c = new QCRCPatrol();
		} elseif ($key == "rollback") {
			$c = new QCRuleRollback();
		} elseif ($key == "newtip") {
			$c = new QCRuleTip();
		}
		return $c;
	}

	private static function getOrderBy($type) {
		$ob = "ORDER BY qc_yes_votes DESC, qc_no_votes DESC";

		if (preg_match('@qcrule_rcpatrol@',$type) > 0) {
			//RC Patrol in there
			//gotta do this by most recent
			$ob .= ", qc_id DESC";
		} else {
			//randomize the ordering
			$rdm = mt_rand(0,1);
			if ($rdm) {
				$ob .= ", qc_id DESC";
			}
			else {
				$ob .= ", qc_page ASC";
			}
		}

		return $ob;
	}

	private static function releaseQC($qcid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('qc',
			array('qc_checkout_time' => "",
				'qc_checkout_user' => 0),
			array('qc_id' => $qcid),
			__METHOD__);
		return true;
	}

	private static function markQCPatrolled($qcid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('qc',
			array('qc_patrolled' => 1),
			array('qc_id' => $qcid),
			__METHOD__);
		return true;
	}

	// Called by QG
	public static function vote($qcid, $vote) {
		$dbw = wfGetDB(DB_MASTER);
		$user = RequestContext::getMain()->getUser();

		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('qc_vote',
			array('count(*)'),
			array('qcv_user' => $user->getID(),
				'qcv_qcid' => $qcid),
			__METHOD__);
		if ($count > 0) {
			self::releaseQC($qcid);
			return;
		}

		$key = $dbw->selectField('qc', 'qc_key', array('qc_id' => $qcid), __METHOD__);

		//admin votes count for 2 on tips
		$vote_num = ($key ==  "newtip" && in_array('sysop',$user->getGroups())) ? 2 : 1;

		$opts = array();
		if ($vote == 1) {
			$opts[] = "qc_yes_votes = qc_yes_votes + ".$vote_num;
			$voteint = 1;
		} else {
			$opts[] = "qc_no_votes = qc_no_votes + ".$vote_num;
			$voteint = 0;
		}

		$dbw->update('qc', $opts, array('qc_id' => $qcid), __METHOD__);
		$dbw->insert('qc_vote',
			array('qcv_user' => $user->getID(),
				'qcv_vote' => $voteint, 'qcv_qcid' => $qcid, 'qc_timestamp' => wfTimestampNow()),
			__METHOD__);

		// check, do we have to mark it as patrolled, or roll the change back?
		$row = $dbw->selectRow('qc',
			array('qc_page', 'qc_key', 'qc_id', 'qc_rev_id', 'qc_yes_votes', 'qc_no_votes', 'qc_yes_votes_req', 'qc_no_votes_req'),
			array('qc_id' => $qcid),
			__METHOD__);

		if ($vote) {
			if (self::isResolved($row, 'up')) {
				self::markQCPatrolled($qcid);
				$c = self::newRuleFromKey($key);
				// TODO: we should report somewhere if $c is null
				if ($c) {
					$c->applyChange($qcid);
				}
			}
		} else {
			if (self::isResolved($row, 'down')) {
				// what kind of rule are we ? figure it out so we can roll it back
				$c = self::newRuleFromKey($key);
				// TODO: we should report somewhere if $c is null
				if ($c) {
					$c->rollbackChange($qcid);
				}
				self::markQCPatrolled($qcid);
			}
		}
		self::markQCAsViewed($qcid);
		self::releaseQC($qcid);

		// log page entry

		$voteParam = $vote > 0 ? "yesvote" : "novote";
		self::log($row, $voteParam);

		Hooks::run("QCVoted", array($user, $title, $vote));
	}

	private static function log($row, $voteParam) {
		$title = Title::newFromID($row->qc_page);
		if (!$title || !$title->exists()) return;

		# Generate a diff link
		$bits[] = 'oldid=' . urlencode( $row->qc_rev_id );
		$bits[] = 'diff=prev';
		$bits = implode( '&', $bits );
		$diff = "[[{$title->getText()}]]";

		$msg = wfMessage("qcrule_log_{$row->qc_key}_{$voteParam}")->rawParams($diff)->escaped();

		$log = new LogPage('qc', false);
		$log->addEntry($voteParam, $title, $msg, array($vote, $row->qc_rev_id, $key));

	}

	private static function isResolved($row, $dir="up") {
		$req = RequestContext::getMain()->getRequest();
		$resolved = false;

		if ($dir == 'up') {
			$resolved = $row->qc_yes_votes >= $row->qc_yes_votes_req;
		} else {
			$resolved = $row->qc_no_votes >= $row->qc_no_votes_req;
		}

		if ($resolved) {
			// public logs
			$param = $dir == "up" ? 'approved' : 'rejected';
			self::log($row, $param);

			// usage logs
			UsageLogs::saveEvent(
				array(
					'event_type' => $req->getVal('event_type'),
					'event_action' => $param,
					'article_id' => $row->qc_page,
					'assoc_id' => $row->qc_id,
					'serialized_data' => json_encode(
						array('type' => $row->qc_key)
					)
				)
			);
		}

		return $resolved;
	}


	// user skips it, so add this to the stuff they have viewed
	// Called by QG
	public static function skip($qcid) {
		self::markQCAsViewed($qcid);
	}

	// these are specific to the rule that is being used
	// Both are called by QG
	abstract public function getPrompt();
	abstract public function rollbackChange($qcid);

	// since this is specific to only 1 class, template changes, make it non-abstract and just return true
	public function applyChange($qcid) {
		return true;
	}

	/*
	 * I _believe_ this is unused (but it's a really common function name! 3/2019
	function getHeader($t) {
		$html = "<div class='qc_title'>".wfMessage('qc_title_prefix')->text().": <a href='{$t->getFullURL()}' target='new'>" . wfMessage('howto', $t->getText())->text() . "</a></div>";
		return $html;
	}
	*/

	protected function getChangedBy($action_str, $div_id = 'qc_changedby',$u = null) {
		if ($u == null) {
			//normal use
			$userText = $this->mResult->qc_user_text;
			$u = User::newFromName($userText);
		}

		$html = "<div id='{$div_id}' class='qc_by'>{$action_str}";

		if ($u) {
			$display = $u->getRealName() == "" ? $u->getName() : $u->getRealName();
			$img = "<a target='new' href='{$u->getUserPage()->getFullURL()}' class='tooltip'><img src='".Avatar::getAvatarURL($u->getName())."' /></a>";
			$html .= "{$img} <a target='new' href='{$u->getUserPage()->getFullURL()}'>{$display}</a>";
			$html .= "<span class='tooltip_span'>Hi, I'm {$display}</span>";

			//add a Quick Note button for patrols
			if (preg_match('@patrol@',$action_str)) {
				$t = Title::newFromID($this->mResult->qc_page);

				//make and format the Quick Note button
				$qn = QuickNoteEdit::getQuickNoteDiffButton($t, $u, $this->mResult->qc_rev_id, $this->mResult->qc_old_rev_id);
				$class = "";

				$html .= preg_replace("@href@",$class." href",$qn);
			}

		} else {
			$html .= "<a target='new' href='{$userText}'>{$userText}</a>";
		}
		$html .= '</div>';
		return $html;
	}

}

/***********************
 *
 *  An abstract class that groups together some functions that are relevant only to text chagnes
 *  Some rules may not involve text changes (patrolling an edit for example)
 *
***********************/
abstract class QCRuleTextChange extends QCRule {
	var	$mTemplate 	= null;
	var $mRevision	= null;
	var $mLastRevid	= null;

	public function __construct($template, $revision, $article) {
		$this->mTemplate	= $template;
		$this->mRevision	= $revision;
		$this->mArticle		= $article;
	}

	private function getLastRevID() {
		if (!$this->mLastRevid) {
			$dbr = wfGetDB(DB_REPLICA);
			$revid = $this->mRevision->getID();
			$pageid = $this->mRevision->getPage();
			$lastrev = $dbr->selectField('revision', 'max(rev_id)', array('rev_page' => $pageid, 'rev_id < ' . $revid), __METHOD__);
			if (!$lastrev) return null;
			$this->mLastRevid = $lastrev;
		}
		return $this->mLastRevid;
	}

	protected function getLastRevisionText() {
		$lastrev = $this->getLastRevID();
		$r = Revision::newFromID($lastrev);
		if (!$r) return null;
		return ContentHandler::getContentText( $r->getContent() );
	}

	protected function getEntryOptions() {
		$opts = array("qc_rev_id" => $this->mRevision->getID());
		$old_rev = $this->getLastRevID();
		if ($old_rev) {
			$opts['qc_old_rev_id'] = $old_rev;
		}
		return $opts;
	}

}

/***********************
 *
 *  The rule for when an intro image gets added
 *
***********************/
class QCRuleIntroImage extends QCRuleTextChange {

	public function __construct($revision = null, $article = null) {
		$this->mAction = "added";
		$this->mKey			= "changedintroimage";
		parent::__construct($template, $revision, $article);
	}

	private function getPart() {
		return "\[\[Image:.*[\|\]]";
	}

	protected function getYesVotesRequired() {
		global $wgQCIntroImageVotesRequired;
		return $wgQCIntroImageVotesRequired["yes"];
	}

	protected function getNoVotesRequired() {
		global $wgQCIntroImageVotesRequired;
		return $wgQCIntroImageVotesRequired["no"];
	}

	protected function flagAction() {
		global $wgParser;

		// check for a revision
		if (!$this->mRevision) {
			return false;
		}

		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title || !in_array($title->getNamespace(), $this->mValidNamespaces)) {
			return false;
		}

		$part    = $this->getPart();
		$oldtext = $wgParser->getSection($this->getLastRevisionText(), 0);
		$wikitext = ContentHandler::getContentText( $this->mRevision->getContent() );
		$newtext = $wgParser->getSection($wikitext, 0);

		//make sure it doesn't have a nointroimg template in it
		if (preg_match('@{{nointroimg}}@im',$newtext)) return false;

		$ret = false;
		if ($oldtext == null && $this->hasText($part, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		} elseif ($this->textRemoved($part, $oldtext, $newtext)) {
			$this->markPreviousAsPatrolled();
		} elseif ($this->textAdded($part, $oldtext, $newtext) || $this->textChanged($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		}

		wfDebug("QC: intro image added " . print_r($ret, true) . "\n");
		return $ret;
	}

	public function getPrompt() {
		return wfMessage('qcprompt_introimage')->text();
	}

	public function rollbackChange($qcid) {
		global $wgParser;

		// remove the intro image from this article
		$t = self::getTitleFromQCID($qcid);
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}

		$text = ContentHandler::getContentText( $r->getContent() );
		$intro = $wgParser->getSection($text, 0);

		//make sure the image is still in there
		preg_match("@\[\[Image:[^\]]*\]\]@im", $intro, $matches);

		if (sizeof($matches) > 0) {
			$old_rev = self::getRevFromQCID($qcid);
			$wikitext = ContentHandler::getContentText( $old_rev->getContent() );
			$old_intro = $wgParser->getSection($wikitext, 0);

			//make sure the it's not a different image
			if (stripos($old_intro,$matches[0]) === false) {
				return false;
			}

			$newintro = preg_replace("@\[\[Image:[^\]]*\]\]@", "", $intro);

			$wikiPage = WikiPage::factory($t);
			$newtext = $wgParser->replaceSection($text, 0, $newintro);
			$content = ContentHandler::makeContent( $newtext, $t );
			if ( $wikiPage->doEditContent( $content, wfMessage('qc_editsummary_introimage')->text() ) ) {
				return true;
			}
		}
		return false;
	}

	private static function getPicture($text) {
		preg_match("@\[\[Image:[^\]]*\]\]@im", $text, $matches);
		$img = "";
		if (sizeof($matches) > 0) {
			$img = preg_replace("@\[\[Image:@", "", $matches[0]);
			$img = preg_replace("@\|.*@", "", $img);
			$img = preg_replace("@\]\]@", "", $img);
			$imgtitle = Title::makeTitle(NS_IMAGE, $img);
			$x = wfFindFile($imgtitle);
			return $x;
		}
		return null;
	}

	public function getNextToPatrolHTML() {
		global $wgParser;

		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = Title::newFromID($this->mResult->qc_page);
		if (!$t) {
			$this->deleteBad($this->mResult->qc_page);
			return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// get current revsion
		$r = Revision::newFromId($this->mResult->qc_rev_id);
		if (!$r) {
			return "Error creating revision";
		}

		// grab the intro image
		$text = ContentHandler::getContentText( $r->getContent() );
		$intro = $wgParser->getSection($text, 0);

		//ignore if we have a {{nointroimg}} template in there
		$a = new Article($t);
		$templates = $a->getTitle()->getTemplateLinksFrom();
		if (in_array('Template:Nointroimg',$templates)) {
			$this->deleteBad($this->mResult->qc_page);
			return "<!--{$this->mResult->qc_page}--><p></p><p>Intro images have been disabled for this article. Please <a href='#' onclick='window.location.reload()'>refresh</a> for the next article.</p>";
		}

		$html = "";
		$changedby = $this->getChangedBy("Image added by: ");
		$pic = self::getPicture($intro);
		if ($pic) {
			//make sure it's not too big
			if ($pic->width > 600) $pic = $pic->getThumbnail(600);

			if ($r->getPrevious()) {
				$old = $r->getPrevious()->getID();
			}
			else {
				$old = -1;
			}

			$thumbresult['new'] = $r->getID();
			$thumbresult['old'] = $old;
			$thumbresult['title'] = $t;

			$pic_width = ((632-$pic->width) /2) + $pic->width - 32; //31px = thumbbutton width

			$thumbs = ThumbsUp::getThumbsUpButton($thumbresult);
			$style = " style='margin-left:".$pic_width."px;'";
			$thumbs = "<div class='qc_changedby_inset'{$style}>{$thumbs}</div>";

			$html .= 	"<div id='qc_bigpic'>
						".$thumbs."
						<img class='qc_bigpic_img' src='" . $pic->getURL() . "' width='".$pic->width."' height='".$pic->height."' />
						</div>";
		} else {
			$html .= "<br />" . wfMessage('qc_nothing_found')->text();
		}

		$html = "<div id='quickeditlink'></div>";
		$html .= "<div id='qc_box'>".$changedby.$html."</div>";
		$popts = RequestContext::getMain()->getOutput()->parserOptions();
		$popts->setTidy(true);
		$magic = WikihowArticleHTML::grabTheMagic($text);
		$parsed = RequestContext::getMain()->getOutput()->parse($text, $t, $popts);
		$html .= WikihowArticleHTML::processArticleHTML($parsed, array('ns' => $t->getNamespace(), 'magic-word' => $magic));
		$html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
		$html .= "<div id='numqcusers'>{$this->mUsers}</div>";
		return $html;
	}

}

/***********************
 *
 *  The rule for when a video is added, chagned or removed
 *
***********************/
class QCRuleVideoChange extends QCRuleTextChange {

	public function __construct($revision = null, $article = null) {
		$this->mKey		= "changedvideo";
		$this->mValidNamespaces = array(NS_MAIN, NS_VIDEO);
		parent::__construct(null, $revision, $article);
	}

	private function getPart() {
		return "\{\{Video:.*[\|\}]";
	}

	protected function getYesVotesRequired() {
		global $wgQCVideoChangeVotesRequired;
		return $wgQCVideoChangeVotesRequired["yes"];
	}

	protected function getNoVotesRequired() {
		global $wgQCVideoChangeVotesRequired;
		return $wgQCVideoChangeVotesRequired["no"];
	}

	protected function flagAction() {

		// check for a revision
		if (!$this->mRevision) {
			return false;
		}

		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title || !in_array($title->getNamespace(), $this->mValidNamespaces)) {
			return false;
		}

		// deal with the situation where the Video: page has been changed
		// TODO: can we narrow it down to just the video changing? probably not. if
		// a video namespace page has changed, we can assume the video has changed
		if ($title->inNamespace(NS_VIDEO)) {
			if ($this->getLastRevisionText() == null) {
				$this->mAction = "added";
			}
			// do we already have an entry in the QC log for the main namespace article
			// for this type of rule? do we need to check? I guess we do.
			$mainTitle = Title::newFromText($title->getText());
			$hasEntry = $this->hasEntry($mainTitle->getArticleID());
			if ($hasEntry) {
				return false;
			}
			$this->mArticle = new Article($mainTitle);
			return true;
		}

		// we may have already put this in for a video namespace edit
		$hasEntry = $this->hasEntry($title->getArticleID());
		if ($hasEntry)  {
			return false;
		}

		// deal with the situation where the main namespace video has been changed
		$part	  = $this->getPart();
		$oldtext = $this->getLastRevisionText();
		$newtext = ContentHandler::getContentText( $this->mRevision->getContent() );

		$ret = false;
		if ($newtext == null && $this->hasText($part, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		} elseif ($this->textRemoved($part, $oldtext, $newtext)) {
			$this->markPreviousAsPatrolled();
		} elseif ($this->textAdded($part, $oldtext, $newtext) || $this->textChanged($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		}

		wfDebug("QC: video change " . print_r($ret, true) . "\n");
		return $ret;
	}

	public function getPrompt() {
		return wfMessage('qcprompt_video')->text();
	}

	// returns array with title text and video wikitext
	private function getVideoSection($text) {
		global $wgParser;
		$index = 0;
		$vidsection = null;
		while ($section = $wgParser->getSection($text, $index)) {
			if (preg_match("@^==\s*" . wfMessage('video')->text() . "@", $section)) {
				$vidsection = $section;
				$vidname = preg_replace("@^==\s".wfMessage('video')->text()."\s==\s{{([^}]*)\}}@", "$1", $section);
				break;
			}
			$index++;
		}

		// format the video name
		if (!empty($vidname)) {
			$parts = explode('|',$vidname);
			$vidname = $parts[0];
		}

		if (!empty($vidsection)) {
			$vidresult = array();
			$vidresult['vidtitle'] = self::getVideoTitle($vidname);
			$vidresult['vidsection'] = trim($vidsection);
		}
		return $vidresult;
	}

	// get the title of the video
	private static function getVideoTitle($text) {
		$videotitletext = '';

		$t = Title::newFromText($text);
		if ($t) {
			$vidrev = Revision::newFromTitle($t);
			if ($vidrev) {
				$vidtext = ContentHandler::getContentText( $vidrev->getContent() );
				$parts = explode('|', $vidtext);

				if (!empty($parts[3])) {
					$videotitletext = $parts[3];
				}
			}
		}
		return trim($videotitletext);
	}

	public function rollbackChange($qcid) {
		// remove the video from this article
		// remove the intro image from this article
		$t = self::getTitleFromQCID($qcid);
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}

		$text = ContentHandler::getContentText( $r->getContent() );
		$vidsection = $this->getVideoSection($text);
		if (!$vidsection) {
			return true;
		}

		$a = new Article($t);

		# replace section doesn't work for some reason for the Video section
		$newtext = str_replace($vidsection['vidsection'], "", $text);

		$wikiPage = WikiPage::factory($t);
		$content = ContentHandler::makeContent($newtext, $t);
		if ( $wikiPage->doEditContent( $content, wfMessage('qc_editsummary_video')->text() )->isOK() ) {
			return true;
		}

		return false;
	}

	public function getNextToPatrolHTML() {
		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = Title::newFromID($this->mResult->qc_page);
		if (!$t) {
			// is there something we can delete ?
			$this->deleteBad($this->mResult->qc_page);
			return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// get current revsion
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return "Error creating revision";
		}

		$vidsection = $this->getVideoSection(ContentHandler::getContentText( $r->getContent() ));

		$html = "";
		$changedby = $this->getChangedBy("Video added by: ");

		if (!empty($vidsection)) {
			$html .= "<div id='qc_bigvid'><div class='section_text'>";
			if (!empty($vidsection['vidtitle'])) $html .= "<h3 id='qc_vidtitle'>\"".$vidsection['vidtitle']."\"</h3>";
			$html .= RequestContext::getMain()->getOutput()->parse($vidsection['vidsection']) . "</div>";
			$html .= "</div>";
		} else {
			$html .= "<br />" . wfMessage('qc_nothing_found')->text();
		}

		$html = "<div id='qc_box'>".$changedby.$html."</div>";
		$html .= "<div id='quickeditlink'></div>";
		$popts = RequestContext::getMain()->getOutput()->parserOptions();
		$popts->setTidy(true);
		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $r->getContent() ));
		$parsed = RequestContext::getMain()->getOutput()->parse(ContentHandler::getContentText( $r->getContent() ), $t, $popts);
		$html .= WikihowArticleHTML::processArticleHTML($parsed, array('no-ads'=>1, 'ns' => $t->getNamespace(), 'magic-word' => $magic));
		$html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
		$html .= "<div id='numqcusers'>{$this->mUsers}</div>";
		return $html;
	}
}

class QCRuleRollback extends QCRule {

	var $mRevision	= null;

	public function __construct($revision = null, $article = null) {
		$this->mArticle = $article;
		$this->mRevision = $revision;
		$this->mKey = "rollback";
		$this->mAction = "rollback_edit";
	}

	protected function flagAction() {
		if ($this->mArticle->getTitle()->inNamespace(NS_MAIN) &&
			preg_match("@Reverted edits@", $this->mRevision->mComment)) {
			return true;
		}
		return false;
	}

	public function getPrompt() {
		return wfMessage('qcprompt_rollback')->text();
	}

	protected function getYesVotesRequired() {
		global $wgQCRollbackVotesRequired;
		return $wgQCRollbackVotesRequired["yes"];
	}

	protected function getNoVotesRequired() {
		global $wgQCRollbackVotesRequired;
		return $wgQCRollbackVotesRequired["no"];
	}

	protected function getEntryOptions() {
		$dbr = wfGetDB(DB_REPLICA);
		$opts = array();
		$min_rev = $dbr->selectField('revision', array('rev_id'),
			array('rev_page' => $this->mArticle->getID(),
				"rev_id < " . $this->mRevision->mId),
			__METHOD__,
			array("ORDER BY" => "rev_id desc", "LIMIT" => 1)
		);
		$opts['qc_old_rev_id'] = $min_rev;
		$opts['qc_rev_id'] = $this->mRevision->mId;
		return $opts;
	}

	public function getNextToPatrolHTML() {
		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = $this->mTitle; // Title::newFromID($this->mResult->qc_page);
		if (!$t) {
			$this->deleteBad($this->mResult->qc_page);
			return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		$r = Revision::newFromTitle($t);
		$d = new DifferenceEngine($t, $this->mResult->qc_old_rev_id, $this->mResult->qc_rev_id);
		$d->loadRevisionData();
		// interesting
		$html = "";
		$changedby = $this->getChangedBy("Rollback performed by: ");

		$out = RequestContext::getMain()->getOutput();
		$out->clearHTML();
		$d->showDiffPage(true);
		$html = "<div id='qc_box'>".$changedby.$html.$out->getHTML()."</div>";
		$out->clearHTML();
		$html .= "<div id='quickeditlink'></div>";
		$popts = $out->parserOptions();
		$popts->setTidy(true);
		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $r->getContent() ));
		$parsed = $out->parse(ContentHandler::getContentText( $r->getContent() ), $t, $popts);
		$html .= WikihowArticleHTML::processArticleHTML($parsed, array('no-ads'=>1, 'ns' => $t->getNamespace(), 'magic-word' => $magic));
		$html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
		$html .= "<div id='numqcusers'>{$this->mUsers}</div>";
		return $html;
	}

	public function rollbackChange($qcid) {
		// try to rollback the last edit if we can
		$dbr = wfGetDB(DB_REPLICA);
		$result = $dbr->selectRow('qc', '*', array('qc_id' => $qcid), __METHOD__);
		$t = self::getTitleFromQCID($qcid);
		$a = new Article($t);
		$last_rev = $a->getRevisionFetched();
		// is this it them most recent revision? if so, we can roll it back
		wfDebug("GOT " . print_r($a, true) . " {$last_rev->mId} vs {$result->qc_rev_id}\n");
		if ($last_rev->mId == $result->qc_rev_id) {
			$a->commitRollback($last_rev->mUserText, wfMessage('qc_editsummary_rollback', $last_rev->mUserText)->text(), false, $result);
		}
		return true;
	}
}

class QCRCPatrol extends QCRule {

	var $mRcids = null;

	public function __construct($article = null, $rcids = null) {
		$this->mArticle = $article;
		$this->mRcids = $rcids;
		$this->mKey	= "rcpatrol";
	}

	protected function flagAction() {
		global $wgMemc;
		$dbr = wfGetDB(DB_REPLICA);
		$user = RequestContext::getMain()->getUser();
		$key = wfMemcKey("patrolcount", $user->getID());
		$count = (int)$wgMemc->get($key);
		if (!$count) {
			$count = $dbr->selectField('logging',
				'count(*)',
				array('log_type' => 'patrol',
					'log_user' => $user->getID()),
				__METHOD__);
			$wgMemc->set($key, $count, 3600);
		}
		// did this user recently revert this page? if so, let's not do this
		// because they patrol a shitty edit, but it's ok because they reverted it!
		$old = wfTimestamp(TS_MW, time() - 10*60);
		$revert = $dbr->selectField('recentchanges',
			'count(*)',
			array('rc_user' => $user->getID(),
				'(rc_comment like "Reverted edits%" OR rc_comment = "Quick edit while patrolling")',
				'rc_cur_id' => $this->mArticle->getTitle()->getArticleID()),
			__METHOD__);
		if ($revert > 0) {
			return false;
		}

		// now, let's filter based on how much patrolling experience the user has
		// todo: could throw this in a global maybe?
		$logqc = false;
		if ($count < 500) {
			$logqc = true;
		} elseif ($count >= 500 && $count < 1500 && rand(0,99) <= 60) {
			$logqc = true;
		} elseif (rand(0, 99) <= 2) {
			$logqc = true;
		}

		#debug $logqc = true;
		return $logqc;
	}

	public function getPrompt() {
		return wfMessage('qcprompt_rcpatrol')->text();
	}

	protected function getYesVotesRequired() {
		global $wgQCRCPatrolVotesRequired;
		return $wgQCRCPatrolVotesRequired["yes"];
	}

	protected function getNoVotesRequired() {
		global $wgQCRCPatrolVotesRequired;
		return $wgQCRCPatrolVotesRequired["no"];
	}

	protected function getEntryOptions() {
		// get the old and new rev_id based on rcids
		$dbr = wfGetDB(DB_REPLICA);
		$opts = array();
		$min_rev = $dbr->selectField('recentchanges',
			'rc_last_oldid',
			array('rc_id' => min($this->mRcids)),
			__METHOD__);
		$max_rev = $dbr->selectField('recentchanges',
			'rc_this_oldid',
			array('rc_id' => max($this->mRcids)),
			__METHOD__);
		$opts['qc_old_rev_id'] = $min_rev;
		$opts['qc_rev_id'] = $max_rev;
		$opts['qc_extra'] = min($this->mRcids) . "," . max($this->mRcids);
		return $opts;
	}

	public function rollbackChange($qcid) {
		$dbw = wfGetDB(DB_MASTER);
		$row = $dbw->selectRow('qc', array('*'), array('qc_id' => $qcid), __METHOD__);
		$t = Title::newFromID($row->qc_page);
		if (!$t) {
			return false;
		}

		$rcids = explode(",", $row->qc_extra);
		$dbw->update('recentchanges',
			array('rc_patrolled' => 0),
			array('rc_cur_id' => $t->getArticleID(),
				'rc_id <= ' . $rcids[1],
				'rc_id >= ' . $rcids[0]),
			__METHOD__);

		return true;
	}

	public function getNextToPatrolHTML() {
		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = $this->mTitle; // Title::newFromID($this->mResult->qc_page);
		if (!$t) {
			$this->deleteBad($this->mResult->qc_page);
			return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// get current revsion
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return "Error creating revision";
		}

		$d = new DifferenceEngine($t, $this->mResult->qc_old_rev_id, $this->mResult->qc_rev_id);
		$d->loadRevisionData();
		// interesting
		$html = "";
		$changedby = $this->getChangedBy("Edits patrolled by: ");

		$out = RequestContext::getMain()->getOutput();
		$out->clearHTML();
		$d->showDiffPage(true);
		$html = "<div id='qc_box'>".$changedby.$html.$out->getHTML()."</div>";
		$out->clearHTML();
		$html .= "<div id='quickeditlink'></div>";
		$popts = $out->parserOptions();
		$popts->setTidy(true);
		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $r->getContent() ));
		$parsed = $out->parse(ContentHandler::getContentText( $r->getContent() ), $t, $popts);
		$html .= WikihowArticleHTML::processArticleHTML($parsed, array('no-ads'=>1, 'ns' => $t->getNamespace(), 'magic-word' => $magic));
		$html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
		$html .= "<div id='numqcusers'>{$this->mUsers}</div>";
		return $html;
	}
}

/***********************
 *
 *  The rule for additions/removal of templates like stub and copyedit
 *
***********************/
class QCRuleTemplateChange extends QCRuleTextChange {

	public function __construct($template, $revision = null, $article = null) {
		parent::__construct($template, $revision, $article);
		$this->mKey	= "changedtemplate_" . strtolower($this->mTemplate);
	}

	private function getPart() {
		return "\{\{" . $this->mTemplate;
	}

	protected function flagAction() {

		// check for a revision
		if (!$this->mRevision) {
			return false;
		}

		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title || !in_array($title->getNamespace(), $this->mValidNamespaces)) {
			return false;
		}

		$part	 = $this->getPart();
		$oldtext = $this->getLastRevisionText();
		$newtext = $this->mRevision->getText();

		$ret = false;
		if ($this->textRemoved($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "removed";
		} elseif ($this->textAdded($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		}

		wfDebug("QC: template change " . print_r($ret, true) . "\n");
		return $ret;
	}

	protected function getYesVotesRequired() {
		global $wgTemplateChangedVotesRequired;
		return $wgTemplateChangedVotesRequired[$this->mAction]["yes"];
	}

	protected function getNoVotesRequired() {
		global $wgTemplateChangedVotesRequired;
		return $wgTemplateChangedVotesRequired[$this->mAction]["no"];
	}

	public function getNextToPatrolHTML() {
		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = Title::newFromID($this->mResult->qc_page);
		if (!$t) {
			$this->deleteBad($this->mResult->qc_page);
			return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// get current revsion
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return "Error creating revision";
		}

		$changedby = $this->getChangedBy("Template " . $this->mResult->qc_action . " by: ");

		$html = "<div id='quickeditlink'></div>";
		$html .= "<div id='qc_box'>".$changedby.$html."</div>";
		$popts = RequestContext::getMain()->getOutput()->parserOptions();
		$popts->setTidy(true);
		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $r->getContent() ));
		$parsed = RequestContext::getMain()->getOutput()->parse(ContentHandler::getContentText( $r->getContent() ), $t, $popts);
		$html .= WikihowArticleHTML::processArticleHTML($parsed, array('no-ads'=>1, 'ns' => $t->getNamespace(), 'magic-word' => $magic));
		$html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
		$html .= "<div id='numqcusers'>{$this->mUsers}</div>";
		return $html;
	}

	public function getPrompt() {
		return wfMessage('qcprompt_template', preg_replace("@changedtemplate_@", "", $this->getKey()))->text();
	}

	// in this case, we want to apply the template to the page because it's been voted "yes" on
	public function applyChange($qcid) {
		$dbr = wfGetDB(DB_REPLICA);

		// load the revision text
		$pageid = $dbr->selectField('qc', array('qc_page'), array('qc_id' => $qcid), __METHOD__);
		$t = Title::newFromID($pageid);
		if (!$t) {
			return false;
		}

		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}

		$text = ContentHandler::getContentText( $r->getContent() );
		if (preg_match("@\{\{" . $this->mTemplate . "@", $text)) {
			return true;
		}

		// add the template  since it doesn't already have it
		$text = "{{{$this->mTemplate}}}" . $text;
		$wikiPage = WikiPage::factory($t);
		$content = ContentHandler::makeContent($text, $t);
		return $wikiPage->doEditContent( $content, wfMessage('qc_editsummary_template_add', $this->mTemplate)->text() );
	}

	public function rollbackChange($qcid) {
		// roll back the chagne from the db
		$dbr = wfGetDB(DB_REPLICA);

		// load the revision text
		$pageid = $dbr->selectField('qc', array('qc_page'), array('qc_id' => $qcid), __METHOD__);
		$t = Title::newFromID($pageid);
		if (!$t) {
			return false;
		}

		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}

		$text = ContentHandler::getContentText( $r->getContent() );
		$text = preg_replace("@\{\{" . $this->mTemplate . "[^\}]*\}\}@U", "", $text);

		$wikiPage = WikiPage::factory($t);
		$content = ContentHandler::makeContent($text, $t);
		return $wikiPage->doEditContent( $content, wfMessage('qc_editsummary_template', $this->mTemplate)->text() );
	}
}


/***********************
 *
 *  The rule for tips patrol
 *
***********************/
class QCRuleTip extends QCRule {

	var $mTipId = null;

	public function __construct($article = null, $tipId = null) {
		$this->mArticle = $article;
		$this->mTipId = $tipId;
		$this->mKey	= "newtip";
		$this->mAction = "added";
	}

	protected function flagAction() {
		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title || !in_array($title->getNamespace(), $this->mValidNamespaces)) {
			return false;
		}

		return true;
	}

	public function getPrompt() {
		return wfMessage('qcprompt_newtip')->text();
	}

	protected function getYesVotesRequired() {
		global $wgQCNewTipVotesRequired;
		return $wgQCNewTipVotesRequired["yes"];
	}

	protected function getNoVotesRequired() {
		global $wgQCNewTipVotesRequired;
		return $wgQCNewTipVotesRequired["no"];
	}

	protected function getEntryOptions() {
		// get the tip ID
		$opts = array();
		$opts['qc_extra'] = $this->mTipId;
		return $opts;
	}

	public function rollbackChange($qcid) {
		//bad tip!
		$dbr = wfGetDB(DB_REPLICA);
		$tipid = $dbr->selectField('qc', array('qc_extra'), array('qc_id' => $qcid), __METHOD__);

		//use TipsPatrol's function
		TipsPatrol::deleteTipFromLog($tipid);
		return true;
	}

	public function applyChange($qcid) {
		//grab tip data from tip id
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select('qc', array('qc_extra','qc_user'), array('qc_id' => $qcid), __METHOD__);
		foreach ($res as $row) {
			$tipId = $row->qc_extra;
			$tipUserId = $row->qc_user;
		}

		// QG is now the first step (before Tips Patrol)
		if ($tipId) {
			$res = $dbw->update('tipsandwarnings', array('tw_guarded' => 1), array('tw_id' => $tipId), __METHOD__);
			return $res;
		}
		return false;
		// $tipData = TipsPatrol::getTipData($tipId, $tipUserId);
		// $tipText = $tipData['tw_tip'];
		// $tipPage = $tipData['tw_page'];

		// //use TipsPatrol's functions
		// $res = TipsPatrol::keepTip($tipId, $tipPage, $tipText);
		// if ($res) {
			// TipsPatrol::deleteTipFromLog($tipId);
		// }
		// return $res;
	}

	public function getNextToPatrolHTML() {
		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = $this->mTitle; // Title::newFromID($this->mResult->qc_page);
		if (!$t) {
			$this->deleteBad($this->mResult->qc_page);
			return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// get current revsion
		$r = Revision::newFromTitle($t);
		if (!$r) return "Error creating revision";

		$html = "";

		//grab all that good tip stuff
		if ($this->mResult->qc_id == -1) {
			//must be planted
			$the_tip = $this->mResult->qc_content;
			$tip_page = $this->mResult->qc_page;
		} else {
			$tipData = TipsPatrol::getTipData($this->mResult->qc_extra, $this->mResult->qc_user);
			$the_tip = $tipData['tw_tip'];
			$tip_page = $tipData['tw_page'];
		}

		//now first step (approved > added)
		//$approvedby = $this->getChangedBy("Tip added by: ","qc_approvedby",$tip_user);

		$tip_html = '<h3>New Tip</h3><div class="wh_block">'.strip_tags($the_tip).'</div>';

		$tip_html = "<div id='qc_box'>".$tip_html."</div>";
		$out = RequestContext::getMain()->getOutput();
		$out->clearHTML();

		if (Misc::isMobileMode()) {
			return array('tip_html' => $tip_html, 'article_id' => $t->getArticleID(), 'tip' => $the_tip);
		}
		else {
			$html = "<div id='quickeditlink'></div>";
			$popts = $out->parserOptions();
			$popts->setTidy(true);
			$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $r->getContent() ));
			$parsed = $out->parse(ContentHandler::getContentText( $r->getContent() ), $t, $popts);
			$html .= WikihowArticleHTML::processArticleHTML($parsed, array('no-ads'=>1, 'ns' => $t->getNamespace(), 'magic-word' => $magic));
			$html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
			$html .= "<div id='numqcusers'>{$this->mUsers}</div>";

			if ($this->mResult->qc_id == -1) {
				$html .= "<input type='hidden' name='pqt_id' id='pqt_id' value='{$this->mResult->pqt_id}' />";
			}

			return $tip_html.$html;
		}
	}
}


/***********************
 *
 *  The admin page for dealing with entries in the QC queue
 *
***********************/
class QG extends SpecialPage {

	public static $defaultQGRules = array('changedvideo', 'newtip');

	public function __construct() {
		parent::__construct( 'QG' );
	}

	public static function getUnfinishedCount(&$dbr) {
		$count = $dbr->selectField('qc',
			'count(*) as C',
			array('qc_patrolled' => 0,
				'qc_key' => self::$defaultQGRules),
			__METHOD__);

		return $count;
	}

	public static function getUnfinishedTipsCount(&$dbr) {
		$count = $dbr->selectField('qc',
			'count(*) as C',
			array('qc_patrolled' => 0,
				'qc_key' => 'newtip'),
			__METHOD__);

		return $count;
	}

	private function getQuickEditLink($title) {
		if ($title) {
			$url = $title->getFullText();
		}
		$editURL = Title::makeTitle(NS_SPECIAL, 'QuickEdit')->getFullURL() . '?type=editform&target=' . urlencode($url);
		$class = "class='button secondary buttonright'";
		$link =  "<a title='" . wfMessage("Editold-quick")->text() . "' accesskey='e' href='' $class onclick=\"return initPopupEdit('".$editURL."') ;\">" .
			htmlspecialchars( wfMessage( 'Editold-quick' )->text() ) . "</a> ";
		return $link;
	}

	private function getSubmenu() {
		$menu = "<div id='qg_submenu'><div id='qg_options'></div></div>";

		return $menu;
	}

	private function getButtons($item) {

		$buttons =	"<div id='qc_head' class='tool_header'>
						<h1 id='question'></h1>
						<a href='#' class='button secondary' id='qc_skip' " . $this->dataAttr($item, 'skip') . ">".wfMessage('qc_skip_article')->text()."</a>
						<a href='#' class='button primary op-action' id='qc_yes' " . $this->dataAttr($item, 'vote_up') . ">Yes</a>
						<a href='#' class='button secondary op-action' id='qc_no' " . $this->dataAttr($item, 'vote_down') . ">No</a>
						<div class='clearall'></div>
					</div>
					<input type='hidden' id='qcrule_choices' value='' />";
		return $buttons;
	}

	private function dataAttr($item, $action) {
		$type = $item->mResult->qc_key;
		$itemId = $item->mResult->qc_id;
		$articleId = $item->mTitle->mArticleID;

		return "data-event_action='$action' " .
			"data-article_id='$articleId'" .
			"data-type='$type'";
	}

	private function getNextInnards($qc_type,$by_username) {
		// grab the next check
		$result = array();

		$c = QCRule::getNextToPatrol($qc_type,$by_username);
		if ($c)  {
			// qc_vote, qc_skip
			$result['title'] 		= "<a href='{$c->mTitle->getLocalURL()}'>{$c->mTitle->getText()}</a>";
			$result['question'] 	= $c->getPrompt();
			$result['qctabs']		= $this->getTabs($qc_type);
			$result['choices' ]		= $this->getSubmenu();
			$result['buttons']		= $this->getButtons($c);
			$result['quickedit'] 	= $this->getQuickEditLink($c->mTitle);
			$result['html'] 		= $c->getNextToPatrolHTML();
			$result['qc_id'] 		= $c->mResult->qc_id;
			$result['sql']			= $c->sql;
			$result['pqt_id']		= $c->mResult->pqt_id;
			$result['title_unformatted'] = $c->mTitle->getText();
		} else {
			$tool = $qc_type == 'NewTip' ? 'tg' : 'qc';
			$eoq = new EndOfQueue();
			$result['done'] 		= 1;
			$result['title'] 		= wfMessage('quality_control')->text();
			$result['qctabs']		= $this->getTabs($qc_type);
			$result['choices' ]		= $this->getSubmenu();
			$result['msg'] = $eoq->getMessage($tool);
		}
		return $result;
	}

	// generate the HTML for the rule selector checkboxes
	private function getOptionMenu($menu_name, $chosen, $username) {
		global $wgQCRulesToCheck;

		if ($menu_name == 'options') {
			//options menu
			$rules = $wgQCRulesToCheck;
			if (in_array('RCPatrol',$rules)
				&& !in_array('sysop', RequestContext::getMain()->getUser()->getGroups())
			) {
				$rules = array_diff($rules,array('RCPatrol'));
			}

			$html = "<a href='#' class='button secondary' id='qcrules_submit'>Done</a>";
			$html .= '<div>';
			foreach ($rules as $key => $rule) {
				if (count($rules)/2 <= $key) $html .= '</div>';
				(preg_match("@{$rule}@i", $chosen) or empty($chosen)) ? $checked = true : $checked = false;
				//hack for unchecking the first RCPatrol view
				if (empty($chosen) and $rule == 'RCPatrol') $checked = false;
				$html .= '<p>'. Xml::checkLabel(wfMessage('qcrule_' . strtolower($rule))->text(),'qcrule_choice','qcrule_' . strtolower($rule),$checked) .'</p>';
			}
			$html .= "</div>";
		}
		else {
			//QG by user
			$html = 'Username: ' .
					Xml::input('qg_byuser_input',30,$username,array('type'=>'text','id'=>'qg_byuser_input')) .
					'<div id="qg_byuser_buttons">
						<a id="qg_byuser_off" class="button secondary" href="#">Off</a>
						<a id="qg_byuser_go" class="button secondary" href="#">Go</a>
					</div>';
		}

		return $html;
	}

	// tabs for options and checkboxes
	private function getTabs($qc_type) {

		$html = "<div id='qg_tabs' class='tool_options_link'>
					<a href='#' id='qgtab_byuser'>" . wfMessage('qc_byuser')->text() . "</a>
					<a href='#' id='qgtab_options'>" . wfMessage('qc_rulestocheck')->text() . "</a>
				</div>";

		return $html;
	}

	// formatted sidenav box for QG voting
	private function getVoteBlock($qc_id) {
		if ($qc_id != -1) {
			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select('qc', array('qc_yes_votes_req','qc_no_votes_req','qc_key'), array('qc_id' => $qc_id), __METHOD__);
			$row = $dbr->fetchObject($res);

			$html = $this->getYesNoVotes($qc_id, $row->qc_yes_votes_req, $row->qc_no_votes_req, $row->qc_key);
		} else {
			$html = "";
		}

		return $html;
	}

	// get the yes/no boxes for voters
	private function getYesNoVotes($qc_id, $req_y, $req_n, $qc_key){
		$t = QCRule::getTitleFromQCID( $qc_id );
		$link = "<a href='{$t->getFullURL()}' target='new'>" . wfMessage('howto', $t->getText())->text() . "</a>";

		$yes = array();
		$no = array();
		$status = '';

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('qc_vote', array('qcv_user','qcv_vote'), array('qcv_qcid' => $qc_id), __METHOD__, array('ORDER BY' => 'qcv_vote DESC'));

		foreach ($res as $row) {
			if ($row->qcv_vote == '1') {
				array_push( $yes,$row->qcv_user );
			} else {
				array_push( $no,$row->qcv_user );
			}
		}

		$html .= "<div id='qc_vote_1' class='qc_votes'><div class='qc_vote_head'>Yes Votes</div>";

		//get yes boxes
		foreach ( $yes as $userId ) {
			$html .= self::getAvatar( $userId );
		}
		$html .= "</div><div id='qc_vote_2'>";

		$yesCount = count( $yes );
		$noCount = count( $no );

		//get left arrow
		if ($yesCount >= $req_y) {
			$html .= "<div class='qc_arrow qc_left_win'></div>";
			$status = 'approved';
		} else {
			$html .= "<div class='qc_arrow qc_left'></div>";
		}

		//get right arrow
		if ($noCount >= $req_n && $status != 'approved') {
			$html .= "<div class='qc_arrow qc_right_win'></div>";
			$status = 'removed';
		} else {
			$html .= "<div class='qc_arrow qc_right'></div>";
		}
		$html .= "</div><div id='qc_vote_3' class='qc_votes'><div class='qc_vote_head qc_head_no'>No Votes</div>";

		//get no boxes
		foreach ( $no as $userId ) {
			$html .= self::getAvatar( $userId );
		}
		$html .= '</div>';

		if ( $status == '' ) {
			if ( $yesCount == $noCount ) {
				$status = 'tie';
			} else  {
				$status = 'need_more';
			}
		}

		//grab main image
		$img = "<div class='qc_vote_img qc_img_$status'></div>";

		//grab upper text
		if ( $status == 'approved' || $status == 'removed' ) {
			$text = wfMessage('qcrule_'.$qc_key)->text().' '.wfMessage( 'qcvote_'.$status )->text();
		} elseif ( $status == 'tie' ) {
			$text = wfMessage( 'qcvote_'.$status )->text();
		} else {
			$text = wfMessage( 'qcvote_'.$status )->text();
		}

		//format the top part
		$top = "<div id='qc_vote_text'>$img<p class='first'>$text $link</p></div>";

		//add it all up
		$html = "$top<div id='qc_votes'>$html</div>";

		return $html;
	}

	private static function getAvatar($user_id) {
		if ($user_id) {
			$u = new User();
			$u->setID($user_id);

			$img = Avatar::getAvatarURL($u->getName());
			if ($img == '') {
				$img = Avatar::getDefaultPicture();
			}
			else {
				$img = "<img src='$img' />";
			}
			$avatar = "<div class='qc_avatar'><a href='{$u->getUserPage()->getFullURL()}' target='_blank' class='tooltip'>{$img}</a>";
			$avatar .= "<span class='tooltip_span'>Hi, I'm {$u->getName()}</span></div>";
		}
		else {
			$avatar = "<div class='qc_emptybox'></div>";
		}
		return $avatar;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$ctx = MobileContext::singleton();
		$isMobile = $ctx->shouldDisplayMobileView();

		if (!$user || ($user->getID() == 0 && !$isMobile)) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ($req->getVal('fetchInnards')) {
			$out->setArticleBodyOnly(true);
			header('Vary: Cookie' );
			$result = $this->getNextInnards($req->getVal('qc_type'),$req->getVal('by_username'));
			print json_encode($result);
			return;

		} elseif ($req->getVal('getOptions')) {
			$out->setArticleBodyOnly(true);
			$out->addHTML($this->getOptionMenu($req->getVal('menuName'),$req->getVal('choices'),$req->getVal('username')));
			return;

		} elseif ($req->getVal('getVoteBlock')) {
			$out->setArticleBodyOnly(true);
			$out->addHTML($this->getVoteBlock($req->getVal('qc_id')));
			return;

		} elseif ($req->wasPosted()) {
			if (class_exists('Plants') && Plants::usesPlants("QGTip") && $req->getVal('qc_id') == -1) {
				$plant = new TipPlants();
				if ($req->getVal("qc_skip") == 1) {
					$vote = -2;
				} elseif ($req->getVal("qc_vote") == 1) {
					$vote = 1;
				} else {
					$vote = 0;
				}

				$plant->savePlantAnswer($req->getVal('pqt_id'), $vote);
			} elseif ($req->getVal('qc_skip', 0) == 1) {
				QCRule::skip($req->getVal('qc_id'));
			} else {
				QCRule::vote($req->getVal('qc_id'), $req->getVal('qc_vote'));
			}
			$out->setArticleBodyOnly(true);
			$result = $this->getNextInnards($req->getVal('qc_type'),$req->getVal('by_username'));
			header('Vary: Cookie' );
			print json_encode($result);
			return;
		}

		// This is the shell of the page, has the buttons, etc.
		$out->setHTMLTitle('Quality Guardian');
		$out->addModules('ext.wikihow.UsageLogs');
		$out->addModules('ext.wikihow.quality_guardian');
		$out->addModules('ext.wikihow.diff_styles');
		$out->addHTML(QuickNoteEdit::displayQuickEdit() . QuickNoteEdit::displayQuickNote(true));
		$out->setHTMLTitle(wfMessage('quality_control')->text());
		$out->setPageTitle(wfMessage('quality_control')->text());

		// add standings widget
		$group= new QCStandingsGroup();
		$indi = new QCStandingsIndividual();

		$indi->addStatsWidget();
		$group->addStandingsWidget();
	}

}

class NoVotesAgainst extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'NoVotesAgainst' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$dbw = wfGetDB(DB_MASTER);
		$out->addHTML("<h2>Top Users with No Votes for Rollback Edits</h2>");
		$res = $dbw->query("SELECT qc_user_text, count(*) as C FROM qc
			LEFT JOIN qc_vote ON qc_id=qcv_qcid
			WHERE qc_key='rollback' AND qcv_vote=0
			GROUP BY qc_user_text ORDER BY C DESC LIMIT 50", __METHOD__);
		$out->addHTML("<ul>");
		foreach ($res as $row) {
			$out->addHTML("<li>{$row->qc_user_text} - {$row->C} No votes\n</li>");
		}
		$out->addHTML("</ul>");
	}

}
