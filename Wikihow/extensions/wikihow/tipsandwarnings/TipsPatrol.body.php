<?php

class TipsPatrol extends SpecialPage {

	const TIP_EXPIRED = 1800; // 30 minutes
	const TIP_ACTION_DEFAULT = 0;
	const TIP_ACTION_DELETE = 1;
	const TIP_ACTION_KEEP = 2;
	const TIP_ACTION_SKIP = 3;

	const TPC_DIFFICULTY_EASY = 0;
	const TPCOACH_ENABLED = "tpcoach_enabled";

	var $skipTool;

	public function __construct() {
		global $wgHooks;
		parent::__construct("TipsPatrol");
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		global $wgDebugToolbar;

		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ($user->isAnon() || self::isBlockedFromTipsPatrol($user)) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->skipTool = new ToolSkip("tiptool", "tipsandwarnings", "tw_checkout", "tw_checkout_user", "tw_id");

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$result = array();
			try {

				$tipId = $req->getVal('tipId');

				if ($req->getVal('coachTip')) {
					$tip = $req->getVal('tip');
					$this->coachResult($tipId, $tip, $result);
				} elseif ($tipId != null && $req->getVal('skipTip') ) {
					$this->logTip($tipId, self::TIP_ACTION_SKIP);
					$this->skipTool->skipItem($tipId);
					$this->skipTool->unUseItem($tipId);
				} elseif ($req->getVal('deleteTip')) {
					$tip = $req->getVal('tip');
					$this->logTip($tipId, self::TIP_ACTION_DELETE, $tip);
					$articleId = $req->getVal('articleId');
					$this->deleteTip($tipId, $articleId, $tip);
					Hooks::run('TipsPatrolled');
				} elseif ($req->getVal('keepTip')) {
					$articleId = $req->getVal('articleId');
					$tip = $req->getVal('tip');
					$this->keepTip($tipId, $articleId, $tip);
					//$this->logTip($tipId, self::TIP_ACTION_KEEP, $tip);
					//$qcId = $this->addToQG($tipId, $articleId, $tip);
					//$this->logTip($tipId, self::TIP_ACTION_KEEP, $tip, $qcId);
					$dbw = wfGetDB(DB_MASTER);
					$dbw->delete('tipsandwarnings', array('tw_id' => $tipId));
					Hooks::run('TipsPatrolled');
				}

				$this->getNextTip($result);

				// if debug toolbark pass logs back in response
				if ($wgDebugToolbar) {
					$result['debug']['log'] = MWDebug::getLog();
				}

				print json_encode($result);

			} catch (MWException $e) {
				$result['error'][] = $e->getText();
				print json_encode($result);
				throw $e;
			}

			return;
		}

		$out->setHTMLTitle(wfMessage('tipspatrol')->text());
		$out->setPageTitle(wfMessage('tipspatrol')->text());

		$out->addModules('ext.wikihow.UsageLogs');
		$out->addModules('jquery.ui.dialog');
		$out->addModules('common.mousetrap');
		$out->addModules('ext.wikihow.tips_patrol');

		WikihowSkinHelper::maybeAddDebugToolbar($out);

		EasyTemplate::set_path(__DIR__);
		$vars = array();
		$vars['tip_skip_title'] = wfMessage('tip_skip_title')->text();
		$vars['tip_keep_title'] = wfMessage('tip_keep_title')->text();
		$vars['tip_delete_title'] = wfMessage('tip_delete_title')->text();
		$out->addHTML(EasyTemplate::html('TipsPatrol.tmpl.php', $vars));
		$coach = $this->getRequest()->getVal("coach");
		// code to init the tipspatrol javascript.. doing it here lets us pass in extra variables when we init
		$out->addScript("<script>$(document).ready(function(){WH.TipsPatrol.init($coach)});</script>");

		$bubbleText = "Only publish this tip if you can make it helpful, clear, and grammatically correct. Most tips should get deleted.";

		InterfaceElements::addBubbleTipToElement('tip_tip', 'tptrl', $bubbleText);
		$this->displayLeaderboards();
	}

/*
* tables for tips patrol coaching data

CREATE TABLE `tipspatrol_test` (
  `tpt_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `tpt_tip` text collate utf8_unicode_ci,
  `tpt_fail_message` text collate utf8_unicode_ci,
  `tpt_success_message` text collate utf8_unicode_ci,
  `tpt_difficulty` int(1) unsigned NOT NULL DEFAULT 0,
  `tpt_page_id` int(8) unsigned NOT NULL,
  `tpt_user_id` int(8) unsigned NOT NULL,
  `tpt_answer` int(1) unsigned NOT NULL,
  PRIMARY KEY  (`tpt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tipspatrol_completed_test` (
  `tpc_user_id` int(8) unsigned NOT NULL,
  `tpc_test_id` int(8) unsigned NOT NULL,
  `tpc_score` int(1) NOT NULL,
  `tpc_timestamp` varchar(14) collate utf8_unicode_ci NOT NULL default '',
  KEY (`tpc_user_id`),
  KEY `tpc_timestamp` (`tpc_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tipspatrol_views` (
  `tpv_user_id` int(8) unsigned NOT NULL,
  `tpv_count` int(8) unsigned NOT NULL,
  `tpv_user_blocked` BOOL NOT NULL DEFAULT 0,
  PRIMARY KEY (`tpv_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/
	private function isBlockedFromTipsPatrol($user) {
		$dbr = wfGetDB(DB_REPLICA);
		$blocked = $dbr->selectField('tipspatrol_views', 'tpv_user_blocked', 'tpv_user_id = ' . intval($user->getID())) ?: false;
		return $blocked;
	}

	private function showCoachTip() {
		$user = $this->getUser();

		if (!self::isTPCoachEnabled()) {
			return false;
		}

		if ($this->getRequest()->getVal("forceCoach") == true) {
			MWDebug::log("force coach is true");
			return true;
		}

		$userGroups = $user->getGroups();
		if (in_array('staff', $userGroups) || in_array('admin', $userGroups) || in_array('newarticlepatrol', $userGroups)) {
			return false;
		}

		$userId = $user->getID();
		$dbr = wfGetDB(DB_REPLICA);
		$patrolledCount = $dbr->selectField('tipspatrol_views', 'tpv_count', 'tpv_user_id = ' . intval($userId)) ?: 0;

		// do not show a coach tip for your first one
		if ($patrolledCount < 1) {
			return false;
		}

		$coachedCount = $dbr->selectField('tipspatrol_completed_test', 'count(*)', 'tpc_user_id = ' . intval($userId));

		$firstSectionSize = 10;
		$secondSectionSize = 100;
		// default, show a tip 5% of the time
		$p = 95;
		if ($patrolledCount <= $firstSectionSize) {
			// 3 in first 10
			$target = 3;
			if ($coachedCount < $target) {
				$target = $target - $coachedCount;
				$p = 100 - 100 * $target / ($firstSectionSize - $patrolledCount);
			}
		} elseif ($patrolledCount <= $secondSectionSize) {
			// show a tip every 15 tips or so
			$p = 85;
		}
		$r = rand(1, 100);
		if ($r > $p) {
			return true;
		}
		return false;
	}

/* disabled functionality - Reuben, May 2016
	function disableTPCoach() {
		if (!self::isTPCoachEnabled()) {
			return;
		}
		ConfigStorage::dbStoreConfig(self::TPCOACH_ENABLED, 0);
	}

	function enableTPCoach() {
		if (self::isTPCoachEnabled()) {
			return;
		}
		ConfigStorage::dbStoreConfig(self::TPCOACH_ENABLED, 1);
	}
*/

	public function isTPCoachEnabled() {
		$msg = wfMessage('tp_coach_enabled');
		$ret = $msg->exists() && (int)$msg->text() > 0;
		return $ret;
	}

	private function getCoachTip(&$content) {
		$user = $this->getUser();
		$out = $this->getOutput();

		$content['error'] = true;

		// get a coach tip that the users hasn't seen before
		$userId = $user->getID();
		$where = array("tpt_id != 1 AND tpt_id NOT IN (SELECT tpc_test_id from tipspatrol_completed_test where tpc_user_id = $userId)");

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('tipspatrol_test', array('*'), $where, __METHOD__);

		$numRows = $res->numRows();
		if ($numRows < 1) {
			return false;
		}

		$index = rand(0, $numRows - 1);
		$res->seek($index);
		$row = $res->current();

		$title = Title::newFromID($row->tpt_page_id);
		$revision = Revision::newFromTitle($title);
		$popts = $out->parserOptions();
		$popts->setTidy(true);
		$parserOutput = $out->parse(ContentHandler::getContentText( $revision->getContent() ), $title, $popts);

		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));

		$content['article'] = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads', 'ns' => NS_MAIN, 'magic-word' => $magic));
		$content['tip'] = $row->tpt_tip;
		$content['tipId'] = $row->tpt_id;

		$content['articleId'] = $row->tpt_page_id;
		$content['articleTitle'] = $title->getText();
		$content['articleUrl'] = $title->getPartialUrl();
		$content['tipCount'] = self::getCount();
		$content['coaching'] = true;
		$content['error'] = false;
		return true;
	}

	function setUserBlocked($userId, $block, &$result) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('tipspatrol_views',
					array("tpv_user_blocked" => $block),
					array("tpv_user_id" => $userId),
					__METHOD__,
					array('IGNORE'));
		$result['success'] = True;
	}

	function resetUserViews($userId, &$result) {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->update('tipspatrol_views',
					array("tpv_count" => 0),
					array("tpv_user_id"=>$userId),
					__METHOD__,
					array('IGNORE'));
		$dbw->delete('tipspatrol_completed_test', array("tpc_user_id"=>$userId), __METHOD__);
		$result['success'] = True;
	}

	private function saveTestResult($testId, $userId, $score) {
		$dbw = wfGetDB(DB_MASTER);
		$data = array("tpc_test_id"=> $testId,
					"tpc_user_id"=>$userId,
					"tpc_score"=>$score,
					"tpc_timestamp"=>wfTimestampNow());

		$dbw->insert('tipspatrol_completed_test', $data, __METHOD__, array('IGNORE'));
	}

	private function coachResult($tipId, $tip, &$content) {
		$user = $this->getUser();
		$req = $this->getRequest();

		$dbr = wfGetDB(DB_REPLICA);
		$where = array("tpt_id" => $tipId);

		$dbr = wfGetDB(DB_REPLICA);
		$row = $dbr->selectRow('tipspatrol_test', '*', $where, __METHOD__);
		$answer = $row->tpt_answer;
		$score = -1;
		if ($req->getVal('skipTip') ) {
			// nothing for now
		} elseif ($req->getVal('deleteTip')) {
			if ($answer == self::TIP_ACTION_KEEP) {
				$score = 0;
			}
			if ($answer == self::TIP_ACTION_DELETE) {
				$score = 1;
			}
		} elseif ($req->getVal('keepTip')) {
			if ($answer == self::TIP_ACTION_KEEP) {
				$score = 1;
			}
			if ($answer == self::TIP_ACTION_DELETE) {
				// only fail if they have not edited the original tip at all..
				$original = $row->tpt_tip;
				$dif = (strlen($tip) - similar_text($original, $tip));
				if ($dif > 10) {
					// for now just record this as a skip
					$score = -1;
				} else {
					$score = 0;
				}
			}
		}
		$content['coachResult'] = array("answer" => $answer,
										"score"=>$score,
										"fail_message"=>$row->tpt_fail_message,
										"success_message"=>$row->tpt_success_message,
										"difficulty"=>$row->tpt_difficulty,
										"userName"=>$user->getName());
		$userId = $user->getID();

		if ($score == 0 && $answer == self::TIP_ACTION_DELETE) {

			$exists = $dbr->selectField('tipspatrol_completed_test', 'tpc_user_id', array('tpc_user_id'=>$userId, 'tpc_score'=>0), __METHOD__);
			if (!$exists) {
				// send a talk message to the user telling them they failed
				$from_user = User::newFromName('Patrol-Coach');
				$comment = "Oops! It looks like you accidentally added a less helpful tip while working on Tips Patrol just now.
					That's okay though; you're still learning, and it can be tricky at first!\r\n
					If you haven't already, read our article on [[Use the Tips Patrol Tool on wikiHow|How to Use the Tips Patrol Tool on wikiHow]] and
					give it another try! If you have any questions about adding tips, don't hesitate to reach out to the [[wikiHow_talk:Help-Team|Help Team]].
					And remember, if you're not sure what to do, just press the \"skip\"
					button and you'll do fine :)\r\n
					The Patrol Coach";
				Misc::adminPostTalkMessage($user, $from_user, $comment);
			}
		}

		self::saveTestResult($tipId, $userId, $score);
	}

	function deleteTest($testId) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete('tipspatrol_test', array('tpt_id' => $testId));
	}

	function addTest($tip, $page, $failMessage, $successMessage, $answer, $difficulty, &$result=array()) {
		$user = $this->getUser();

		$t = null;
		if (!is_numeric($page)) {
			$t = Title::newFromText($page);
		} else {
			$t = Title::newFromID(intval($page));
		}

		if (!$t || !$t->isKnown()) {
			return;
		}

		$pageId = $t->getArticleID();

		if (!$tip) {
			return;
		}

		$answerId = null;
		switch (strtolower($answer)) {
			case "delete":
				$answerId = self::TIP_ACTION_DELETE;
				break;
			case "publish":
			case "keep":
				$answerId = self::TIP_ACTION_KEEP;
				break;
			default:
				break;
		}

		if (!$answerId) {
			return;
		}

		$data = array("tpt_tip"=> $tip,
					"tpt_page_id"=>$pageId,
					"tpt_user_id"=>$user->getID(),
					"tpt_answer"=>$answerId);

		if ($failMessage) {
			$data["tpt_fail_message"] = $failMessage;
		}
		if ($successMessage) {
			$data["tpt_success_message"] = $successMessage;
		}
		if ($difficulty) {
			$data["tpt_difficulty"] = $difficulty;
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('tipspatrol_test', $data, __METHOD__, array('IGNORE'));
		$result['success'] = true;
	}

	private function getNextTip(&$content) {
		$showCoachTip = $this->showCoachTip();

		$coachTip = null;
		if ($showCoachTip) {
			$coachTip = $this->getCoachTip($content);
			MWDebug::log("this will be a coach tip");
		}

		if (!$coachTip) {
			$content = $this->getDBTip($content);
		}
	}

	private function getDBTip($content) {
		$out = $this->getOutput();

		$dbw = wfGetDB(DB_MASTER);
		//$dbw->query( 'SET NAMES latin1', __METHOD__ );
		$expired = wfTimestamp(TS_MW, time() - TipsPatrol::TIP_EXPIRED);
		$i = 0;
		$content['error'] = true;
		$content['lasterror'] = 'no error';
		$goodRevision = false;
		do {
			$skippedIds = $this->skipTool->getSkipped();
			$where = array();
			$where[] = "tw_guarded = 1"; //already been through QG
			$where[] = "tw_checkout < '$expired'";
			$where[] = "NOT EXISTS (SELECT rc_id from recentchanges where rc_cur_id = tw_page and rc_patrolled = 0 LIMIT 1)";
			if ($skippedIds) {
				$where[] = "tw_id NOT IN ('" . implode("','", $skippedIds) . "')";
			}
			$row = $dbw->selectRow('tipsandwarnings', array('*'), $where, __METHOD__, array("LIMIT" => 1));
			//$content['sql' . $i] = $dbw->lastQuery();
			//$content['row'] = $row;

			if ($row !== false) {
				$title = Title::newFromID($row->tw_page);
				$isRedirect = false;
				if ($title) {
					$dbr = wfGetDB(DB_REPLICA);
					$isRedirect = (int)$dbr->selectField('page', 'page_is_redirect',
						array('page_id' => $row->tw_page), __METHOD__, array("LIMIT" => 1));
					if ($isRedirect) {
						$content['lasterror'] = 'title is redirect';
					}
				} else {
					$content['lasterror'] = 'no title for page id: ' . $row->tw_page;
				}

				if ($title && !$isRedirect) {
					$this->skipTool->useItem($row->tw_id);
					$revision = Revision::newFromTitle($title);
					$popts = $out->parserOptions();
					$popts->setTidy(true);
					$parserOutput = $out->parse(ContentHandler::getContentText( $revision->getContent() ), $title, $popts);
					$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));
					$content['article'] = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads', 'ns' => NS_MAIN, 'magic-word' => $magic));
					$content['tip'] = $row->tw_tip;
					$content['tipId'] = $row->tw_id;
					$content['articleId'] = $row->tw_page;
					$content['articleTitle'] = $title->getText();
					$content['articleUrl'] = $title->getPartialUrl();
					$content['error'] = false;
				} else {
					// article must no longer exist or be a redirect, so delete the tips associated with that article
					$dbw->delete('tipsandwarnings', array('tw_page' => $row->tw_page));
				}
			} else {
				$content['lasterror'] = 'no rows from query. skips:'
					. ($skippedIds ? join(',', $skippedIds) : 'none. Debugging note: maybe they are all checked out? See TipsPatrol::TIP_EXPIRED.');
			}
			$i++;
		// Check up to 5 titles.
		// If no good title then return an error message
		} while ($i <= 5 && !$title && $row !== false);

		$content['i'] = $i;
		$content['tipCount'] = self::getCount();
		return $content;

	}

	public static function getCount() {
		$dbr = wfGetDB(DB_REPLICA);
		// tw_guarded means it went through Quality Guardian (QG)
		$count = $dbr->selectField('tipsandwarnings', 'COUNT(*)', array( 'tw_guarded' => '1' ), __METHOD__);
		return $count;
	}

	// the quality guardian will delete the tip from the log.
	public function deleteTipFromLog($tipId) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete('tipsandwarnings_log', array('tw_id' => $tipId), __METHOD__);
	}

	// action called when a user deletes a tip from tips patrol
	public function deleteTip($tipId = null, $articleId, $tip) {
		if ($tipId != null) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete('tipsandwarnings', array('tw_id' => $tipId), __METHOD__);

			$title = Title::newFromID($articleId);
			if ($title) {
				$logPage = new LogPage('newtips', false);
				$logData = array();
				$logMsg = wfMessage('newtips-rejected-logentry', $title->getFullText(), $tip)->text();
				$logS = $logPage->addEntry("Rejected", $title, $logMsg);
			}
		}
	}

	/*
	* see if this tip is already in the tips section
	*
	*
	* $tipId - text of tip being submitted
	* $newTip - the text of the new tip
	* $tipsSection -  the current tips of this article
	*
	* returns - true if this tip has been added to this article already
	*/
	private function tipAlreadyAdded($tipId, $newTip, $tipsSection) {
		$tips = Wikitext::splitTips($tipsSection);
		$newTip = self::cleanTip($newTip);
		foreach ($tips as $tip) {
			$tip = self::cleanTip($tip);
			if ($tip == $newTip) {
				return true;
			}
		}
		return false;
	}

	public function keepTip($tipId, $articleId, $tip) {
		global $wgParser;

		$title = Title::newFromID($articleId);

		if ($title) {
			$revision = Revision::newFromTitle($title);
			$wikiPage = WikiPage::factory($title);
			if ($revision && $wikiPage) {
				$wikitext = ContentHandler::getContentText( $revision->getContent() );
				$section = Wikitext::getSection($wikitext, "Tips", true);

				// do not add the tip if the tips section does not exist.
				if ($section[1] == 0 || !$section[0] || $section[0] == "") {
					return false;
				}

				// see if this tip already exists...if it does do not add it
				if (self::tipAlreadyAdded($tipId, $tip, $section[0])) {
					return false;
				}

				//make a log for this
				$logPage = new LogPage('newtips', false);
				$logData = array($tipId);
				$logMsg = wfMessage('newtips-approved-logentry_tp', $title->getFullText(), $tip)->text();
				$logS = $logPage->addEntry("Approved", $title, $logMsg, $logData);

				$newSection = $section[0] . "\n* $tip";

				$newText = $wgParser->replaceSection($wikitext, $section[1], $newSection);

				// the save hook will log this tip being approved
				$content = ContentHandler::makeContent($newText, $title);
				$success = $wikiPage->doEditContent($content, wfMessage('newtips-article-edit-entry_tp')->text());

				return $success;
			}
		}
	}

	public function addToQG($tipId, $articleId, $tip) {
		$title = Title::newFromID($articleId);
		if ($title) {
			$article = new Article($title);
			if ($article) {
				//Add it to the QG queue
				$l = new QCRuleTip($article, $tipId);
				$qcId = $l->process();

				// //log it
				// $logPage = new LogPage('newtips', false);
				// $logData = array($tipId);
				// $logMsg = wfMessage('newtips-sentToQG-logentry', $title->getFullText(), $tip)->text();
				// $logS = $logPage->addEntry("Added", $title, $logMsg, $logData);

				//add to tips log table tool
				$this->logTip($tipId, self::TIP_ACTION_DEFAULT, $tip, $qcId);
				return true;
			}
		}
		//return $qcId;
		return false;
	}

	function displayLeaderboards() {
		$stats = new TipsPatrolStandingsIndividual();
		$stats->setContext($this->getContext());
		$stats->addStatsWidget();
		$standings = new TipsPatrolStandingsGroup();
		$standings->setContext($this->getContext());
		$standings->addStandingsWidget();
	}

	function logTip($tipId, $tipAction, $newtip=null, $qcId = null) {

		$userId = $this->getUser()->getID();

		$row = TipsPatrol::getTipRow($tipId);

		if (is_array($row)) {
			//remove guarded field
			unset($row['tw_guarded']);
			$row['tw_action'] = $tipAction;

			if ($qcId) {
				$row['tw_qc_id'] = $qcId;
			}

			$dbw = wfGetDB(DB_MASTER);

			// if it's a skip don't bother recording the the tipsandwarnings_log
			if ($tipAction != TipsPatrol::TIP_ACTION_SKIP) {
				$row['tw_tip'] = $newtip;
				$row['tw_user'] = $userId;
				$row['tw_timestamp'] = wfTimestampNow();
				$dbw->insert('tipsandwarnings_log', $row, __METHOD__, array('IGNORE'));
			}

			// only count views while tipspatrol is active
			if (self::isTPCoachEnabled()) {
				$dbw->query("INSERT INTO `tipspatrol_views` (`tpv_user_id`, `tpv_count`) VALUES ($userId, 1) ON DUPLICATE KEY UPDATE tpv_count = tpv_count + 1");
			}
		}
	}

	private function cleanTip($tip) {
		if ($tip[0] == "*") {
			$tip = substr($tip, 1);
		}
		return trim($tip);
	}

	/*
	* revert a revision in which a tip was added
	*
	* If the revision is undone, it will mark the recentchange as patrolled
	*
	* $pageId - page to act on
	* $revId - revision to undo
	*
	*/
	function revertTipOnArticle($pageId, $revId) {
		global $wgParser;

		// do not revert if no revId
		if ($revId <= 0 || $revId == null || $revId == "") {
			return false;
		}

		$undoRevision = Revision::newFromId($revId);
		$previousRevision = $undoRevision ? $undoRevision->getPrevious() : null;

		// do not revert if the page is wrong or changed..
		if ( is_null($undoRevision)
			|| is_null($previousRevision)
			|| $undoRevision->getPage() != $previousRevision->getPage()
			|| $undoRevision->getPage() != $pageId
		) {
			return false;
		}

		$title = Title::newFromID($pageId);
		$wikiPage = WikiPage::factory($title);

		$undoRevisionText = ContentHandler::getContentText( $undoRevision->getContent() );
		$currentText = $wikiPage->getContent();

		$undoTips = Wikitext::splitTips(reset(Wikitext::getSection($undoRevisionText, "Tips", true)));
		$previousRevisionText = ContentHandler::getContentText( $previousRevision->getContent() );
		$prevTips = Wikitext::splitTips(reset(Wikitext::getSection($previousRevisionText, "Tips", true)));
		$currentTipsSection = Wikitext::getSection($currentText, "Tips", true);
		$currentTips = Wikitext::splitTips($currentTipsSection[0]);
		$section = $currentTipsSection[1];

		$undoTipsFormatted = array();
		foreach ($undoTips as $tip) {
			$undoTipsFormatted[] = self::cleanTip($tip);
		}

		$prevTipsFormatted = array();
		foreach ($prevTips as $tip) {
			$prevTipsFormatted[] = self::cleanTip($tip);
		}

		$badTips = array_diff($undoTipsFormatted, $prevTipsFormatted);
		$resultTips = "== Tips ==";
		foreach ($currentTips as $currentTip) {
			$tip = self::cleanTip($currentTip);
			if (in_array($tip, $badTips)) {
				continue;
			}
			$resultTips .= "\n".$currentTip;
		}
		$newText = $wgParser->replaceSection($currentText, $section, $resultTips);
		$content = ContentHandler::makeContent($newText, $title);
		$success = $wikiPage->doEditContent($content, 'reverting tip from revision '.$revId, EDIT_UPDATE | EDIT_MINOR );

		// mark the recent change as patrolled
		if ($success) {
			// should be ok to read from slave here because the change has been done earlier.
			$dbr = wfGetDB(DB_REPLICA);
			$rcid = $dbr->selectField('recentchanges', 'rc_id', array("rc_this_oldid=$revId") );
			RecentChange::markPatrolled($rcid);
			PatrolLog::record($rcid, false);
		}

		return $success;
	}

	/*
	* Undo a tip that was added/removed via tips patrol
	*
	* It will put the tip back into tips patrol
	* if the tip was added to QQ it will try to remove it if not patrolled there
	* must havea tipid and qcid - this insures that the tip was approved
	* because it would not have a qcid if it was skipped
	*
	* $tipId - id of the tip in tipsandwarnings_log to undo (same id as the original tip)
	* $qcId - id of the data in quality guardian, which is set when the tip is sent there
	* $tipId - id of user who we are undoing their tip
	*
	* returns - the new tipId of the tip that was added back to tipsandwarnings
	*/
	static function undoTip($tipId, $qcId, $userId) {
		if (!$tipId || !$qcId) {
			return false;
		}

		// tip is is QC, remove it from there
		QCRuleTip::deleteIfNotPatrolled($qcId, $userId);

		// we just need to get the tip page and the tip from here
		$tipData = TipsPatrol::getTipLogRow($tipId);

		// tip data to add back to tipsandwarnings table
		$data = array("tw_page"=> $tipData['tw_page'],
					"tw_tip"=>$tipData['tw_tip'],
					"tw_user"=>$userId,
					"tw_timestamp"=>wfTimestampNow());

		// now delete the tips with this id from the log and put it back into the main queue
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('tipsandwarnings', $data, __METHOD__, array('IGNORE'));
		$dbw->delete('tipsandwarnings_log', array('tw_id' => $tipId));

		return true;
	}

	// this function will get tip data from the tips log
	// it will get it by user if it can, but if there is no data
	// with this username it will look for any tip text associated with this tipid
	static function getTipData($tipId, $userId) {
		$dbr = wfGetDB(DB_REPLICA);

		$row = $dbr->selectRow('tipsandwarnings_log', array('tw_tip', 'tw_page'), array('tw_id' => $tipId, 'tw_user'=>$userId), __METHOD__);
		if ($row) {
			$row = get_object_vars($row);
		}

		if (!$row || !$row['tw_tip']) {
			$row = $dbr->selectRow('tipsandwarnings_log', array('tw_tip', 'tw_page'), array("tw_id = $tipId", "tw_tip is not NULL"), __METHOD__);
			if ($row) {
				$row = get_object_vars($row);
			}
		}
		if (!$row) {
			return array();
		}
		return $row;
	}

	static function getTipLogRow($tipId) {
		$dbr = wfGetDB(DB_REPLICA);
		if ($row = $dbr->selectRow('tipsandwarnings_log', '*', array('tw_id' => $tipId), __METHOD__)) {
			$row = get_object_vars($row);
		} else {
			$row = null;
		}
		return $row;
	}

	function getTipRow($tipId) {
		$dbr = wfGetDB(DB_REPLICA);
		if ($row = $dbr->selectRow('tipsandwarnings', '*', array('tw_id' => $tipId), __METHOD__)) {
			$row = get_object_vars($row);
		} else {
			$row = null;
		}
		return $row;
	}

}
