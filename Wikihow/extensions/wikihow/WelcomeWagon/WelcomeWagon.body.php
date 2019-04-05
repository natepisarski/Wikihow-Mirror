<?php

global $IP;
require_once("$IP/includes/specials/SpecialContributions.php");

class WelcomeWagonContribsPager extends ContribsPager {

	function formatRow( $row ) {
		global $wgContLang;

		$rev = new Revision( $row );

		$page = Title::makeTitle( $row->page_namespace, $row->page_title );
		$link = Linker::link( $page );
		$difftext = $topmarktext = '';
		if ( $row->rev_id == $row->page_latest ) {
			if ( !$row->page_is_new ) {
				$difftext .= '(' . Linker::link( $page, $this->messages['diff'], array(), 'diff=0' ) . ')';
			} else {
				$difftext .= $this->messages['newarticle'];
			}

		}
		if ( $rev->userCan( Revision::DELETED_TEXT ) ) {
			$difftext = '(' . Linker::link( $page, $this->messages['diff'], array(), 'diff=prev&oldid='.$row->rev_id ) . ')';
		} else {
			$difftext = '(' . $this->messages['diff'] . ')';
		}

		$comment = $wgContLang->getDirMark() . Linker::revComment( $rev );
		$d = RequestContext::getMain()->getLanguage()->timeanddate( wfTimestamp( TS_MW, $row->rev_timestamp ), true );

		if ( $this->target == 'newbies' ) {
			$userlink = ' . . ' . Linker::userLink( $row->rev_user, $row->rev_user_text );
			$userlink .= ' (' . Linker::userTalkLink( $row->rev_user, $row->rev_user_text ) . ') ';
		} else {
			$userlink = '';
		}

		if ( $rev->isDeleted( Revision::DELETED_TEXT ) ) {
			$d = '<span class="history-deleted">' . $d . '</span>';
		}

		if ( $row->rev_minor_edit ) {
			$mflag = '<span class="minor">' . $this->messages['minoreditletter'] . '</span> ';
		} else {
			$mflag = '';
		}

		$ret = "{$d} {$difftext} {$mflag} {$link}{$userlink}{$comment} {$topmarktext}";
		if ( $rev->isDeleted( Revision::DELETED_TEXT ) ) {
			$ret .= ' ' . wfMessage( 'deletedrev' )->escaped();
		}
		$ret = "<li>$ret</li>\n";
		return $ret;
	}
}

/*
	welcome wagon DB Tables

	CREATE TABLE `welcome_wagon_skips` (
	`wws_to_user_id` int(8) unsigned NOT NULL,
	`wws_from_user_id` int(8) unsigned NOT NULL,
	`wws_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
	KEY `wws_to_user_id` (`wws_to_user_id`),
	KEY `wws_from_user_id` (`wws_from_user_id`),
	UNIQUE KEY `wws_from_to` (`wws_to_user_id`,`wws_from_user_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	CREATE TABLE `welcome_wagon_messages` (
	`ww_id` int(8) unsigned NOT NULL auto_increment,
	`ww_from_user_id` int(8) unsigned NOT NULL,
	`ww_to_user_id` int(8) unsigned NOT NULL,
	`ww_revision_id` int(8) unsigned NOT NULL,
	`ww_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
	PRIMARY KEY  (`ww_id`),
	KEY `ww_to_user_id` (`ww_to_user_id`),
	KEY `ww_from_user_id` (`ww_from_user_id`),
	KEY `ww_timestamp` (`ww_timestamp`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/

class WelcomeWagon extends UnlistedSpecialPage {

	var $noMoreUsersKey = null;
	var $logTable = 'welcome_wagon_messages';
	var $usersCount = null;

	const WW_COUNT_KEY = "welcomewagon_count";

	public function __construct() {
		parent::__construct('WelcomeWagon');

		global $wgHooks;
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');

		$this->maxMessagesPerUser = 2;
		$this->maxSkipsPerUser = 3;

		//set up the cache variables
		$this->userIdsKey = wfMemcKey("welcomewagon_userids");
		$this->userMessagesKey = wfMemcKey("welcomewagon_usermessages");
		$this->userSkipsKey = wfMemcKey("welcomewagon_userskips");
		$this->cacheOk = wfMemcKey("welcomewagon_cacheok");
	}

	private function userAllowed() {
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ($user->isBlocked()
			|| !( in_array('staff', $userGroups) ||
				  in_array('staff_widget', $userGroups) ||
				  in_array('welcome_wagon', $userGroups))
		) {
			return false;
		}

		return true;
	}

	public function writeDiff(&$dbr, $target) {
		global $out;
		$out = $this->getOutput();

		$out->addHTML( "<table width='100%' align='center' class='bunchtable'><tr>" );

		$opts = array ('rc_user_text' =>$target);
		$opts[] = ' (rc_namespace = 0) ';

		$res = $dbr->select ( 'recentchanges',
				array ('rc_id', 'rc_title', 'rc_namespace', 'rc_this_oldid', 'rc_cur_id', 'rc_last_oldid'),
				$opts,
				__METHOD__,
				array ('LIMIT' => 15)
			);

		$count = 0;
		foreach ($res as $row) {
			$t = Title::makeTitle($row->rc_namespace, $row->rc_title);
			$diff = $row->rc_this_oldid;
			$rcid = $row->rc_id;
			$oldid = $row->rc_last_oldid;
			$de = new DifferenceEngine( $t, $oldid, $diff, $rcid );
			$out->addHTML( "<tr>" );
			$out->addHTML( "<td>" );
			$out->addHTML( Linker::link($t) );
			$de->showDiffPage(true);
			$out->addHTML("</td></tr>");
			$count++;
		}

		$out->addHTML( "</table><br/><br/>" );
		return $count;
	}

	private function addUserTalkHTML($targetUser) {
		global $wgTitle, $wgArticle;
		$out = $this->getOutput();
		// the joys of globals...got this idea from docs/globals.txt
		$oldTitle = $wgTitle;
		$oldArticle = $wgArticle;
		$wgTitle = Title::makeTitle(NS_USER_TALK, $targetUser->getName());
		$wgArticle = new Article($wgTitle);
		if ($wgArticle) {
			$out->addHTML("<div id='content-talkpage' class='ww_content wh_block'> ");
			$out->addHTML($wgArticle->view());
		} else {
			$out->addHTML("no user talk info");
		}

		$wgTitle = $oldTitle;
		$wgArticle = $oldArticle;

		$out->addHTML("</div>");
	}

	private function addSummary($targetUser) {
		$out = $this->getOutput();
		$out->addHTML("<div id='content-summary' class='ww_content wh_block'>");

		$target = $targetUser->getName();
		$pager = new WelcomeWagonContribsPager($this->getContext(), array("target" => $target));
		if ( !$pager->getNumRows() ) {
			$out->addWikiMsg( 'nocontribs' );
			$out->addHTML("</div>");
			return;
		}
		$out->addHTML($pager->getBody());

		$out->addHTML("</div>");

	}

	private function addRecentContributionsHTML($targetUser) {
		$out = $this->getOutput();
		$out->addHTML("<div id='content-contributions' class='wh_block'>");

		$target = $targetUser->getName();
		$dbr = wfGetDB(DB_REPLICA);
		$this->writeDiff($dbr, $target);

		$out->addHTML("</div>");
	}

	private function addContentProfileHTML($targetUser) {
		$userWikiPage = WikiPage::factory($targetUser->getUserPage());

		$out = $this->getOutput();
		$out->addHTML("<div id='content-profile' class='ww_content wh_block'>");

		$userPage = WikihowUserPage::newFromTitle( $targetUser->getUserPage(), $this->getContext() );
		$userPage->view($targetUser);

		if ($userWikiPage->getId() > 0) {
			$wikitext = ContentHandler::getContentText( $userWikiPage->getContent() );
			$out->addHTML($wikitext);
		}

		$out->addHTML("</div>");
	}

	private function getMessagesSentForUserIds($userIds) {
		global $wgSharedDB;

		if (count($userIds) < 1) {
			return array();
		}
		$dbr = wfGetDB(DB_REPLICA);
		$result = $dbr->query("SELECT user_id, count(ww_to_user_id) as ww_messages
								FROM $wgSharedDB.user
								LEFT OUTER JOIN welcome_wagon_messages ON user_id = ww_to_user_id
								WHERE user_id in (" . $dbr->makeList(array_keys($userIds)) . ")
								GROUP BY user_id;");
		$messages = array();
		foreach ($result as $row) {
			$messages[$row->user_id] = $row->ww_messages;
		}

		return $messages;
	}

	private function getSkipsForUserIds($userIds) {
		global $wgSharedDB;

		if (count($userIds) < 1) {
			return array();
		}
		$dbr = wfGetDB(DB_REPLICA);
		$result = $dbr->query("SELECT user_id, count(wws_to_user_id) as skips
								FROM $wgSharedDB.user
								LEFT OUTER JOIN welcome_wagon_skips ON user_id = wws_to_user_id
								WHERE user_id in (" . $dbr->makeList(array_keys($userIds)) . ")
								GROUP BY user_id;");
		$skips = array();
		foreach ($result as $row) {
			$skips[$row->user_id] = $row->skips;
		}

		return $skips;
	}

	private function getUserIds() {
		global $wgSharedDB;

		$dbr = wfGetDB(DB_REPLICA);
		$beginTime = wfTimestamp( TS_MW, time() - 60 * 60 * 24 * 7 );

		$initialUserIds = array();

		$done = false;
		$batchSize = 100;
		$offset = 0;
		while ($done == false) {
			$sql = "SELECT user_id, user_registration as registration
					FROM $wgSharedDB.user
					ORDER BY user_id DESC limit $batchSize OFFSET $offset";
			$result = $dbr->query($sql, __METHOD__);

			// don't query forever
			if ($result->numRows() < 1) {
					$done = true;
					break;
			}

			foreach ($result as $row) {
				if ((int)$row->registration < (int)$beginTime) {
					$done = true;
					break;
				}
				$initialUserIds[] = $row->user_id;
			}
			$offset+=$batchSize;
		}

		if (count($initialUserIds) < 1) {
			return array();
		}
		$userIds = array();
		$namespaces = array("0");
		$revision = $dbr->tableName('revision');
		$sql = "SELECT t1.rev_user, count(*) as numedits
				FROM $revision t1
				LEFT JOIN page t2 ON t1.rev_page = t2.page_id
				WHERE rev_user IN (" . $dbr->makeList($initialUserIds) . ") AND t2.page_namespace IN (" . $dbr->makeList($namespaces) . ")
				GROUP BY rev_user HAVING COUNT(*) > 4;";

		$res = $dbr->query($sql, __METHOD__);
		$edits = array();
		while ($row = $res->next()) {
			$edits[$row->rev_user] = $row->numedits;
		}
		foreach ($initialUserIds as $i => $userId) {
			if (!isset($edits[$userId])) {
				unset($initialUserIds[$i]);
			}
		}
		foreach ($initialUserIds as $i => $userId) {
			$userIds[$userId]  = true;
		}

		return $userIds;
	}

	private function skipUser($userId) {
		global $wgMemc;
		$user = $this->getUser();
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert("welcome_wagon_skips",
			array("wws_from_user_id" => $user->getId(), "wws_to_user_id" => $userId),
			__METHOD__,
			array('IGNORE'));

		$skips = $wgMemc->get($this->userSkipsKey);
		$key = (int)$userId;
		if (is_array($skips) && $skips[$key] != null) {
			$skips[$key] = $skips[$key] + 1;
			$wgMemc->set($this->userSkipsKey, $skips);

			if ($skips[$key] > $this->maxSkipPerUser) {
				$userIds = $wgMemc->get($this->userIdsKey);
				if (is_array($userIds) && $userIds[$key] == 1) {
					$userIds[$key] = 0;
					$wgMemc->set($this->userIdsKey, $userIds);
				}
			}
		}
	}

	private function messagedUser($toId) {
		global $wgMemc;
		$key = (int)$toId;
		$sent = $wgMemc->get($this->userMessagesKey);
		if (is_array($sent) && $sent[$key] != null) {
			$sent[$key] = $sent[$key] + 1;
			$wgMemc->set($this->userMessagesKey, $sent);

			if ($sent[$key] > $this->maxMessagesPerUser) {
				$userIds = $wgMemc->get($this->userIdsKey);
				if (is_array($userIds) && $userIds[$key] == 1) {
					$userIds[$key] = 0;
					$wgMemc->set($this->userIdsKey, $userIds);
				}
			}
		}
	}

	private function resetCache() {
		global $wgMemc;
		$userIds = $this->getUserIds();
		$messagesSent = $this->getMessagesSentForUserIds($userIds);
		$skips = $this->getSkipsForUserIds($userIds);

		foreach ($userIds as $id => $val) {
			if ($messagesSent[$id] > $this->maxMessagesPerUser) {
				$userIds[$id] = false;
			}
			if ($skips[$id] > $this->maxSkipsPerUser) {
				$userIds[$id] = false;
			}
		}

		$wgMemc->set($this->userMessagesKey, $messagesSent);
		$wgMemc->set($this->userSkipsKey, $skips);
		$wgMemc->set($this->userIdsKey, $userIds);
		$wgMemc->set($this->cacheOk, true, 60*15);
	}

	private function isMessaged($userId) {
		$user = $this->getUser();
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField("welcome_wagon_messages",
									array('count(*)'),
									array('ww_from_user_id'=>$user->getId(), 'ww_to_user_id'=>$userId),
									__METHOD__);
		if ($count > 0) {
			return true;
		} else {
			return false;
		}
	}

	private function isSkipped($userId) {
		$user = $this->getUser();

		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField("welcome_wagon_skips",
									array('count(*)'),
									array('wws_from_user_id'=>$user->getId(), 'wws_to_user_id'=>$userId),
									__METHOD__);
		if ($count > 0) {
			return true;
		} else {
			return false;
		}
	}

	public static function getCount() {
		global $wgMemc;

		$count = $wgMemc->get(self::WW_COUNT_KEY);
		if ($count == null) {
			// calling this function should set up the count
			$ww = new WelcomeWagon();
			$ww->getNextUserId();
		}

		// should be set up now
		$count = $wgMemc->get(self::WW_COUNT_KEY);

		// at the very least return 0 if it's still not set
		if ($count == null) {
			$count = 0;
		}

		return $count;
	}

	private function getNextUserId() {
		global $wgMemc;

		$userIds = $wgMemc->get($this->userIdsKey);
		if (!is_array($userIds)) $userIds = array();
		$messages = $wgMemc->get($this->userMessagesKey);
		if (!is_array($messages)) $messages = array();
		$skips = $wgMemc->get($this->userSkipsKey);
		if (!is_array($skips)) $skips = array();

		// reorganize the list of ids by messages sent
		$idsByMessages = array();
		for ($i = 0; $i < $this->maxMessagesPerUser; $i++) {
			$idsByMessages[$i] = array();
		}

		$this->usersCount = 0;
		foreach ($userIds as $id => $val) {
			$messageCount = $messages[$id];
			$skipsCount = $skips[$id];
			if ($val == true && ($skipsCount == 0 || $this->isSkipped($id) == false) && ($messageCount == 0 || $this->isMessaged($id) == false)) {
				$this->usersCount++;
				$idsByMessages[$messageCount][] = $id;
			}
		}

		// store the count in memcache
		$wgMemc->set(self::WW_COUNT_KEY, $this->usersCount);

		// return first result
		foreach ($idsByMessages as $idsArray) {
			foreach ($idsArray as $id) {
				return $id;
			}
		}
	}

	private function getStats() {
		$stats = new WelcomeWagonStandingsIndividual();
		return $stats->getStandingsTable();

	}

	private function displayLeaderboards() {
		$stats = new WelcomeWagonStandingsIndividual();
		$stats->addStatsWidget();
		$standings = new WelcomeWagonStandingsGroup();
		$standings->addStandingsWidget();
	}

	private function getLastArticle($userName) {
		$lastArticle = null;
		$res = ProfileBox::fetchEditedData($userName, 1);
		foreach ($res as $row) {
			$t = Title::newFromId($row->page_id);
			if ($t && $t->exists()) {
				$lastArticle = '[['.$t->getFullText().']]';
			}
		}
		return $lastArticle;
	}

	private function tabSwitch() {
		// We use this method instead of $out->disable() so that we can
		// see and debug MWExceptions
		$this->getOutput()->setArticleBodyOnly(true);

		$target = $this->getRequest()->getVal('userName');
		$user = User::newFromName($target);

		$tab = $this->getRequest()->getVal('tabName');
		switch ($tab) {
			case 'contributions':
				$this->addRecentContributionsHTML($user);
				break;

			case 'summary':
				$this->addSummary($user);
				break;

			case 'profile':
				$this->addContentProfileHTML($user);
				break;

			case 'talkpage':
				$this->addUserTalkHTML($user);
				break;

			default:
				break;
		}

		print json_encode(array('html' => $this->getOutput()->getHTML()));
		$this->getOutput()->clearHTML();
	}

	private function nextUser() {
		$req = $this->getRequest();
		$out = $this->getOutput();

		// We use this method instead of $out->disable() so that we can
		// see and debug MWExceptions
		$out->setArticleBodyOnly(true);

		// first remove or skip the user
		$id = $req->getVal('userId');
		if ($id) {
			if ($req->getVal('skip') == 'true') {
				$this->skipUser($id);
			} else {
				$this->messagedUser($id);
			}
		}

		$userId = $this->getNextUserId();
		if ($userId == null) {
			return; // Nothing to do
		} else {
			$user = User::newFromId($userId);
			print json_encode($this->getOutputVariables($user));
		}
	}

	private function getOutputVariables($user) {
		$userLink = Linker::link($user->getUserPage(), $user->getName());
		$lastArticle = $this->getLastArticle($user->getName());

		$output = array( 'userLink' => $userLink,
						'userName' => $user->getName(),
						'userRealName' => $user->getRealName(),
						'userId' => $user->getId(),
						'lastArticleLink' => $lastArticle);

		if ($this->usersCount) {
			$output['usersCount'] = $this->usersCount;
		}
		return $output;
	}

	private function logMessage($fromId, $toId, $revId, $message) {
		$dbw = wfGetDB(DB_MASTER);
		$rev = Revision::newFromId($revId);
		if ($rev) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert($this->logTable, array("ww_from_user_id" => $fromId, "ww_to_user_id" => $toId, "ww_revision_id" => $revId), __METHOD__);

			$log = new LogPage( 'welcomewag', false );
			$fromUser = User::newFromId($fromId);
			$toUser = User::newFromId($toId);
			if (strlen($message) > 150) {
				$message = substr($message, 0, 149);
			}
			$msg = wfMessage( "welcomewag_log_message",
							"[[User:{$fromUser->getName()}|{$fromUser->getName()}]]",
							"[[User:{$toUser->getName()}|{$toUser->getName()}]]",
							$message )->text();
			$log->addEntry('message', $rev->getTitle(), $msg, array("fromId"=>$fromId, "toId"=>$toId, "revId"=>$revId));

			Hooks::run( 'WelcomeWagonMessageSent' );
		}
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgMemc;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if (!$this->userAllowed()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted() || $req->getVal('show')) {
			switch ($req->getVal('action')) {
				case 'nextUser':
					$this->nextUser();
					break;
				case 'logMessage':
					// We use this method instead of $out->disable() so that we can
					// see and debug MWExceptions
					$out->setArticleBodyOnly(true);
					$toId = $req->getVal('toId');
					$revId = $req->getVal('revId');
					$message = $req->getVal('message');
					$this->logMessage($user->getId(), $toId, $revId, $message);
					print json_encode(array('stats'=>$this->getStats()));
					break;
				case 'switchTab':
					$this->tabSwitch();
					break;
				default:
					break;

			}
			return;
		}

		$target = $req->getVal('target');
		if ($target) {
			$targetUser = User::newFromName($target);
			if ($targetUser->getId() > 0) {
				InterfaceElements::addJSVars($this->getOutputVariables($targetUser));
			}
		}
		if (!$wgMemc->get($this->cacheOk)) {
			$this->resetCache();
		}
		$out->setPageTitle('Welcome Wagon');
		$out->setHTMLTitle('Welcome Wagon');

		$tmpl = new EasyTemplate(__DIR__);

		$out->addHTML($tmpl->execute('WelcomeWagon.tmpl.php'));
		$out->addModules('ext.wikihow.diff_styles');
		$out->addModules('ext.wikihow.welcome_wagon');

		InterfaceElements::addBubbleTipToElement('form-header', 'wwagon', 'No matter what happens keep the message positive and personalized.');
		$this->displayLeaderboards();
	}
}

