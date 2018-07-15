<?php

// a special page for reviewing the user completed images uploaded from mobile
class UCIPatrol extends SpecialPage {

	const UCI_ACTION_GOOD = 1;
	const UCI_ACTION_BAD = 2;
	const UCI_ACTION_SKIP = 3;

	const PATROL_THUMB_WIDTH = 670;
	const PATROL_THUMB_HEIGHT = 350;

	const CACHE_TIME = 1209600;

	// how many votes does an admin vote count as?
	const UCI_ADMIN_VOTE_MULT = 2;

	var $skipTool;

	const TABLE_NAME = "user_completed_images";

	function __construct() {
		parent::__construct("PicturePatrol", "ucipatrol");
	}

	function printStatsUploads($uploads) {
		echo("User Completed Images Stats:<br>");
		foreach($uploads as $key => $val) {
			echo($key.", $val<br>");
		}
	}

	function printStatsAnon($anon) {
		echo("<br>Anon Votes:<br>");
		echo("Total Anon Upvotes, ".$anon['Anonymous Upvotes']."<br>");
		echo("Total Anon Downvotes, ".$anon['Anonymous Downvotes']."<br>");
	}

	function printStatsTitles($titles) {
		global $wgServer;
		echo("<br>Pages with User Completed Images (most recent first)<br>");
		echo("Titles, Number of Images<br>");

		if (count($titles) < 1) {
			echo("none, n/a<br>");
			return;
		}
		foreach($titles as $title => $data) {
			$tDisplay  = str_replace(' ', '-', $title);
			$link = "$wgServer/$tDisplay";
			$num = $data['number'];
			echo("<a href=$link>$link</a>, $num<br>");
		}
	}

	function printStatsFlagged($flagged) {
		global $wgServer;
		echo("<br>Flagged Removed Images (most recent first)<br>");
		echo("Image, From<br>");
		foreach($flagged as $image => $info) {
			$tDisplay  = str_replace(' ', '-', $image);
			$t = Title::newFromText($image);
			$link = Linker::link($t, $wgServer."/".$tDisplay);

			$fromDisplay  = str_replace(' ', '-', $info['from']);
			$from = Title::newFromText($info['from']);
			$fromLink = Linker::link($from, $wgServer."/".$fromDisplay);
			$date = $info['time'];

			echo("$link, $fromLink<br>");
		}
	}


	/**
	 * @param $out
	 */
	protected function addTemplateHtml() {
		$tmpl = new EasyTemplate(dirname(__FILE__));

		$out = $this->getOutput();
		$out->addHTML($tmpl->execute('UCIPatrol.tmpl.php'));
	}

	protected function addJSAndCSS() {
		$out = $this->getOutput();
		$out->addModules(array(
			'jquery.ui.dialog', 'ext.wikihow.UsageLogs', 'common.mousetrap', 'ext.wikihow.ucipatrol'
		));
	}

	protected function getArticleHtml($revision, $title) {
		$popts = $this->getOutput()->parserOptions();
		$popts->setTidy(true);
		$parserOutput = $this->getOutput()->parse($revision->getText(), $title, $popts);
		$magic = WikihowArticleHTML::grabTheMagic($revision->getText());
		$result = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
		return $result;
	}

	function execute($par) {
		global $wgDebugToolbar;

		$request = $this->getRequest();
		$out = $this->getOutput();

		# Check blocks
		$user = $this->getUser();
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$this->checkPermissions();
		if ($request->getVal("stats")) {
			$out->disable();
			$uploads = $this->getStatsUploads();
			$titles = $this->getStatsTitles();
			if ($request->getVal("format") == "json") {
				echo json_encode($uploads);
				echo json_encode($titles);
				return;
			}

			UCIPatrol::printStatsUploads($uploads);
			UCIPatrol::printStatsTitles($titles);
			return;
		}

		if ($request->wasPosted()) {
			$out->setArticleBodyOnly(true);

			// wrapping it all in try catch to get any database errors
			try {
				$result = array();

				if ($request->getVal('next') ) {
					$result = $this->getNext();
				} elseif ($request->getVal('skip')) {
					$this->skip();
				} elseif ($request->getVal('bad')) {
					$this->downVote();
				} elseif ($request->getVal('good')) {
					$this->upVote();
				} elseif ($request->getVal('undo')) {
					$this->undo();
				} elseif ($request->getVal('error')) {
					$this->error();
					$result = $this->getNext();
				} elseif ($request->getVal('resetskip')) {
					$this->resetSkip();
					$result = $this->getNext();
				} elseif ($request->getVal('showmore')) {
					$offset = $request->getInt('offset');
					$limit = $request->getInt('limit');
					$title = $request->getVal('hostPage');
					$sidebar = $request->getBool('sidebar');
					$title = Title::newFromText($title);

					if ($request->getVal('domain') == 'mobile') {
						$width = UserCompletedImages::MOBILE_THUMB_WIDTH;
						$height = UserCompletedImages::MOBILE_THUMB_HEIGHT;
					} else {
						$width = UserCompletedImages::THUMB_WIDTH;
						$height = UserCompletedImages::THUMB_HEIGHT;
					}

					$result = UserCompletedImages::getUCIData($this->getContext(), $title, $offset, $limit, false, $width, $height);

				} elseif ($request->getVal('flag')) {
					$this->flag();
				}

				// if debug toolbar is active, pass logs back in json response
				if ($wgDebugToolbar) {
					$info =  MWDebug::getDebugInfo($this->getContext());
					$result['debug']['log'] = $info['log'];
					$result['debug']['queries'] = $info['queries'];
				}

				echo json_encode($result);

			} catch (MWException $e) {
				$result = $result ?: array();
				$result['error'][] = $e->getText();
				echo json_encode($result);
				throw $e;
			}
		} else {
			//do mobile-y stuff if we're mobile-izing
			//do desktop-y stuff if we're im-mobile-ized
			$isMobile = MobileContext::singleton()->shouldDisplayMobileView();
			if ($isMobile) {
				$out->setPageTitle(''); //making our own header;
			}
			else {
				$out->setPageTitle(wfMessage('ucipatrol')->text());
				$bubbleText = "Help us pick the best user submitted photos to match the article.";
				InterfaceElements::addBubbleTipToElement('uci', 'ucitp', $bubbleText);
			}

			$out->setHTMLTitle(wfMessage('ucipatrol')->text());

			$this->addJSAndCSS();

			WikihowSkinHelper::maybeAddDebugToolbar($out);

			$this->addTemplateHtml();
			$this->displayLeaderboards();
		}
	}

	// guest id only used if user is anon
	private function getUserAvatar($user, $guestId) {
		if ($user->isAnon()) {
			// look for the guest_id cookie value to
			// give them the right avatar image
			$userAvatar = Avatar::getAnonAvatar($guestId);
			return $userAvatar;
		}

		$avatar = Avatar::getPicture($user->getName(), false);

		if ($avatar == '') {
			$avatar = Avatar::getDefaultPicture();
		}

		$userName = Linker::linkKnown($user->getUserPage(), $user->getName());

		$userAvatar = array("name"=>$userName, "image"=>$avatar);

		return $userAvatar;
	}

	private function resetSkip() {
		$cache = wfGetMainCache();
		$key = $this->getSkipCacheKey();
		$cache->delete($key);
	}

	private function undo() {

		if ($this->getUser()->isAnon()) {
			// since anon cannot vote they cannot undo vote either..
			$this->unSkip();
		}


		$pageId = $this->getRequest()->getInt('pageId');
		$articleTitle = $this->getRequest()->getVal('articleTitle');

		$action = $this->getRequest()->getVal("action");

		$dbw = wfGetDB(DB_MASTER);
		$conds = array('uci_article_id' => $pageId);

		$values = array();
		$val = $this->getVoteMultiplier();
		if ($action == "good") {
			$votes = $dbw->selectField(self::TABLE_NAME, "uci_upvotes", $conds);

			if ($votes == UserCompletedImages::UPVOTES) {
				UserCompletedImages::removeImageFromPage($articleTitle, $pageId);
			}

			$values[] = "uci_upvotes = uci_upvotes - $val";
		} else {
			$values[] = "uci_downvotes = uci_downvotes - $val";
		}

		$dbw->update(self::TABLE_NAME, $values, $conds);

		// record a vote of zero that is equivalent to no vote
		$this->recordImageVote($this->getUser(), $pageId, 0);

		$this->unSkip();
	}

	private function upVote() {
		$request = $this->getRequest();

		$pqu_id = $request->getVal("pqu_id");
		if ($pqu_id) {
			$plant = new UCIPlants();
			$plant->savePlantAnswer($pqu_id, 1);
			return;
		}

		$pageId = $request->getVal('pageId');

		if (!$pageId) {
			MWDebug::warning("no pageId to upvote");
			return;
		}

		if ($this->getUser()->isAnon()) {
			$title = Title::newFromText($this->getRequest()->getVal('articleTitle'));
			UCIPatrol::logUCIAnonUpVote($title, $pageId);
			$this->skip();
			return;
		}

		$conds = array('uci_article_id' => $pageId);
		$val = $this->getVoteMultiplier();
		$values = array("uci_upvotes = uci_upvotes + $val");

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(self::TABLE_NAME, $values, $conds);

		$this->recordImageVote($this->getUser(), $pageId, 1);

		// check if the image has enough votes that it will be added to the host page
		$row = $dbw->selectRow(
			self::TABLE_NAME,
			array("uci_image_name", "uci_article_name", "uci_upvotes"),
			array("uci_article_id" => $pageId),
			__METHOD__
		);

		// title is used for logging purposes
		$title = Title::newFromText($row->uci_article_name);

		if ($row->uci_upvotes < UserCompletedImages::UPVOTES) {
			UCIPatrol::logUCIUpVote($title, $pageId);
		} else {
			// UsageLogs::saveEvent(
				// array(
					// 'event_type' => 'picture_patrol',
					// 'event_action' => 'approved',
					// 'article_id' => $pageId
				// )
			// );

			UserCompletedImages::addImageToPage($pageId, $row->uci_article_name, UserCompletedImages::fileFromRow($row));
			UCIPatrol::logUCIAdded($title, $pageId);

			wfRunHooks( "PicturePatrolResolved" , [$row->uci_image_name, true]);
		}

		wfRunHooks( 'PicturePatrolled' );
		$this->skip();
	}

	private static function logUCIError($title, $pageId) {
		UCIPatrol::logUCI($title, $pageId, "error");
	}

	private static function logUCIFlagRemoved($title, $pageId) {
		UCIPatrol::logUCI($title, $pageId, "flagremoved");
	}

	private static function logUCIFlagged($title, $pageId) {
		UCIPatrol::logUCI($title, $pageId, "flagged");
	}

	private static function logUCIRejected($title, $pageId) {
		UsageLogs::saveEvent(array(
			'event_type' => 'picture_patrol',
			'event_action' => 'rejected',
			'article_id' => $pageId
		));
		UCIPatrol::logUCI($title, $pageId, "rejected");
	}

	private static function logUCIAdded($title, $pageId) {
		UsageLogs::saveEvent(array(
			'event_type' => 'picture_patrol',
			'event_action' => 'approved',
			'article_id' => $pageId
		));
		UCIPatrol::logUCI($title, $pageId, "approved");
	}

	private static function logUCIAnonUpVote($title, $pageId) {
		UCIPatrol::logUCI($title, $pageId, "anonupvote");
	}

	private static function logUCIAnonDownVote($title, $pageId) {
		UCIPatrol::logUCI($title, $pageId, "anondownvote");
	}

	private static function logUCIUpVote($title, $pageId) {
		UCIPatrol::logUCI($title, $pageId, "upvote");
	}

	private static function logUCIDownVote($title, $pageId) {
		UCIPatrol::logUCI($title, $pageId, "downvote");
	}

	private static function logUCI($title, $pageId, $type) {
		$logPage = new LogPage('ucipatrol', false);
		$logData = array();
		$imageTitle = Title::newFromID($pageId);
		$logMsg = wfMessage("newuci-$type-logentry", $title, $imageTitle)->text();
		$logPage->addEntry($type, $title, $logMsg);
	}

	private function recordImageVote($user, $imagePageId, $vote) {
		$dbw = wfGetDB(DB_MASTER);
		$userId = intval($user->getID());
		if ($userId == 0) {
			$userId = WikihowUser::getVisitorId();
		}
		$imagePageId = intval($imagePageId);
		$vote = intval($vote);
		$timestamp = wfTimestampNow();
		$dbw->query("INSERT INTO `image_votes` (`iv_userid`, `iv_pageid`, `iv_vote`) VALUES ($userId, $imagePageId, $vote) ON DUPLICATE KEY UPDATE iv_vote = ".$vote.", iv_added = ".$timestamp);
	}

	private function error() {
		//don't need this for plants
		if (class_exists('Plants') && Plants::usesPlants('PicturePatrol') ) return;

		$pageId = $this->getRequest()->getVal('pageId');

		UCIPatrol::fullDownVote($pageId);
		$this->skip();
		$title = Title::newFromText($this->getRequest()->getVal("articleTitle"));
		UCIPatrol::logUCIError($title, $pageId);
	}

	private function fullDownVote($pageId) {
		UCIPatrol::downVoteItem($pageId, UserCompletedImages::DOWNVOTES, false);
	}

	private function fullDownVoteImg($img_name) {
		$conds = array("uci_image_name" => $img_name);
		$values = array("uci_downvotes" => UserCompletedImages::DOWNVOTES);

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(self::TABLE_NAME, $values, $conds, __METHOD__);

		return $dbw->affectedRows();
	}

	// returns affected rows
	private function downVoteItem($pageId, $amount=1, $useMultiplier=true) {
		$conds = array("uci_article_id = $pageId");
		if ($useMultiplier) {
			$amount = $amount * $this->getVoteMultiplier();
		}
		$values = array("uci_downvotes = uci_downvotes + $amount");

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(self::TABLE_NAME, $values, $conds, __METHOD__);

		return $dbw->affectedRows();
	}

	private function downVote() {
		$request = $this->getRequest();

		$pqu_id = $request->getVal("pqu_id");
		if ($pqu_id) {
			$plant = new UCIPlants();
			$plant->savePlantAnswer($pqu_id, 0);
			return;
		}

		$pageId = $request->getVal('pageId');
		if (!$pageId) {
			MWDebug::warning("no pageId to downvote");
			return;
		}

		if ($this->getUser()->isAnon()) {
			$title = Title::newFromText($this->getRequest()->getVal('articleTitle'));
			UCIPatrol::logUCIAnonDownVote($title, $pageId);
			$this->skip();
			return;
		}

		$this->downVoteItem($pageId);
		$this->recordImageVote($this->getUser(), $pageId, -1);

		// check if the image has enough votes that it will be removed from queue
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow(
			self::TABLE_NAME,
			array("uci_image_name", "uci_article_name", "uci_downvotes"),
			array("uci_article_id" => $pageId),
			__METHOD__
		);

		$title = Title::newFromText($this->getRequest()->getVal("articleTitle"));

		if ($row->uci_downvotes < UserCompletedImages::DOWNVOTES) {
			UCIPatrol::logUCIDownVote($title, $pageId);
		} else {
			UCIPatrol::logUCIRejected($title, $pageId);
			// UsageLogs::saveEvent(
				// array(
					// 'event_type' => 'picture_patrol',
					// 'event_action' => 'rejected',
					// 'article_id' => $pageId
				// )
			// );
			wfRunHooks( "PicturePatrolResolved" , [$row->uci_image_name, false]);
		}

		wfRunHooks( 'PicturePatrolled' );
		$this->skip();
	}
	private function flag() {
		global $wgMemc;

		$request = $this->getRequest();
		$id = $request->getInt('pageId');
		$hostPageTitle = $request->getVal('hostPage');
		if (!$id) {
			return;
		}
		MWDebug::log("will flag $id");

		if ($this->getUser()->isAnon()) {
			$title = Title::newFromText($hostPageTitle);
			UCIPatrol::logUCIAnonDownVote($title, $id);
			return;
		}


		// make sure this user didn't already flag this image
		$voters = UCIPatrol::getVoters($id);
		foreach($voters as $voter) {
			if ($voter['id'] == $this->getUser()->getID() && $voter['vote'] < 0) {
				return;
			}
		}

		$this->downVoteItem($id);
		$this->recordImageVote($this->getUser(), $id, -1);

		// check if the image has enough votes that it will be removed from the page
		$dbw = wfGetDB(DB_MASTER); //using master to make sure we are including the vote that just happened
		$row = $dbw->selectRow(
			self::TABLE_NAME,
			array("uci_image_name", "uci_article_name", "uci_downvotes"),
			array("uci_article_id" => $id),
			__METHOD__
		);

		$title = Title::newFromText($hostPageTitle);
		if ($row->uci_downvotes < UserCompletedImages::DOWNVOTES) {
			//log the action
			UCIPatrol::logUCIFlagged($title, $id);
		} else {
			if (UserCompletedImages::UCI_CACHE) {
				UserCompletedImages::removeImageFromPage($hostPageTitle, $id);

				//purge from cache since we want these images to go away
				if ($title && $title->exists()) {
					$title->purgeSquid();
				}

				//this effectively remotes it from the page but keeps the down votes so it won't go back into the queue
				$dbw->update( self::TABLE_NAME, ['uci_upvotes' => 0], ['uci_article_id' => $id], __METHOD__ );
				wfRunHooks( "PicturePatrolResolved" , [$row->uci_image_name, false]);
			}
			UCIPatrol::logUCIFlagRemoved($title, $id);
		}
	}

	private function unSkip() {
		$request = $this->getRequest();
		$id = $request->getVal('pageId');
		if (!$id) {
			return;
		}
		MWDebug::log("will unskip $id");
		$key = $this->getSkipCacheKey();

		$cache = wfGetMainCache();
		$skipped = $cache->get($key);

		if (!$skipped || count($skipped) == 0) {
			// nothing to unskip we are done
			return;
		}

		$newSkipped = array();
		foreach($skipped as $skip) {
			if ($skip == $id) {
				continue;
			}
			$newSkipped[] = $skip;
		}
		$cache->set($key, $newSkipped, self::CACHE_TIME);
	}

	private function skip() {
		$id = $this->getRequest()->getVal('pageId');
		if (!$id) {
			return;
		}
		$this->skipById($id);
	}

	private function skipById($id) {
		$request = $this->getRequest();

		$pqu_id = $request->getVal("pqu_id");
		if ($pqu_id) {
			$plant = new UCIPlants();
			$plant->savePlantAnswer($pqu_id, -1);
			return;
		}

		MWDebug::log("will skip $id");
		$key = $this->getSkipCacheKey();
		$cache = wfGetMainCache();
		$val = $cache->get($key) ?: array();
		$val[] = $id;
		$cache->set($key, $val, self::CACHE_TIME);
	}

	private function getSkipCacheKey() {
		if ($this->getUser()->isAnon()) {
			//$name = $this->getRequest()->getInt('guestId');
			$name =  WikihowUser::getVisitorId();
		} else {
			$name = $this->getUser()->getName();
		}
		return "UCIPatrol_".$name."_skipped";
	}

	private function getSkipList() {
		$cache = wfGetMainCache();
		$key = $this->getSkipCacheKey();
		$oldSkipped = $cache->get($key);
		if (!$oldSkipped || count($oldSkipped) == 0) {
			return array();
		}

		$where = array();
		$where[] = "uci_downvotes < ".UserCompletedImages::DOWNVOTES;
		$where[] = "uci_upvotes < ".UserCompletedImages::UPVOTES;
		$where["uci_on_whitelist"] = 1;

		$newSkipped = array();
		foreach($oldSkipped as $skippedId) {
			$where["uci_article_id"] = $skippedId;
			$dbr = wfGetDB(DB_SLAVE);
			$count = $dbr->selectField(self::TABLE_NAME, 'count(*)', $where, __METHOD__);
			if ($count == 0) {
				continue;
			}
			$newSkipped[] = $skippedId;
		}

		$cache->set($key, $newSkipped, self::CACHE_TIME);
		return $newSkipped;
	}

	private function getVoteMultiplier() {
		$value = 1;
		if (UCIPatrol::userInUCIAdminGroup($this->getUser())) {
			$value = 2;
		}

		if ($this->getUser()->isAnon()) {
			$value = 0;
		}
		return $value;
	}

	private function getStatsUploads() {
		global $wgMemc;

		if(!in_array('sysop',$this->getUser()->getGroups())) {
			return;
		}

		$key = wfMemcKey('ucistats', 'uploads');
		$uploads = $wgMemc->get($key);
		if (!$uploads) {
			$dbr = wfGetDB(DB_SLAVE);
			$uploads = array();

			$day = wfTimestamp(TS_MW, time() - 1 * 24 * 3600);
			$where = "uci_timestamp > $day";
			$count = $dbr->selectField(self::TABLE_NAME, array('count(*)'), $where, __METHOD__);
			$uploads["last 24 hours"] = $count;

			$week = wfTimestamp(TS_MW, time() - 7 * 24 * 3600);
			$where = "uci_timestamp > $week";
			$count = $dbr->selectField(self::TABLE_NAME, array('count(*)'), $where, __METHOD__);
			$uploads["last 7 days"] = $count;

			$month = wfTimestamp(TS_MW, time() - 30 * 24 * 3600);
			$where = "uci_timestamp > $month";
			$count = $dbr->selectField(self::TABLE_NAME, array('count(*)'), $where, __METHOD__);
			$uploads["last 30 days"] = $count;

			$count = $dbr->selectField(self::TABLE_NAME, array('count(*)'));
			$uploads["allTime"] = $count;

			$averageSelect = "count(*) / count(distinct date(uci_timestamp))";
			$perDay = $dbr->selectField(self::TABLE_NAME, array($averageSelect));
			$uploads["average uploads per day"] = $perDay;

			$wlCount = substr_count(ConfigStorage::dbGetConfig(UserCompletedImages::CONFIG_KEY), "\n");
			$uploads["number of articles on whitelist"] = $wlCount + 1;

			$ppCount = $dbr->selectField(self::TABLE_NAME, array('count(distinct uci_article_name)'), '', __METHOD__);
			$uploads["articles with submitted uci"] = $ppCount;
			$ppCount = $dbr->selectField(self::TABLE_NAME, array('count(distinct uci_article_name)'), 'uci_upvotes > 2 and uci_downvotes < 2 and uci_copyright_error < 1 and uci_copyright_violates < 1', __METHOD__);
			$uploads["articles with visible uci"] = $ppCount;
			$dvCount = $dbr->selectField(self::TABLE_NAME, array('count(*)'), 'uci_downvotes >= 2', __METHOD__);
			$uploads["images with 2 or more downvotes"] = $dvCount;
			$dvCount = $dbr->selectField(self::TABLE_NAME, array('count(*)'), 'uci_upvotes > 2 and uci_downvotes < 2', __METHOD__);
			$uploads["total live picture patrol images"] = $dvCount;
			$dvCount = $dbr->selectField(self::TABLE_NAME, array('count(*)'), array(), __METHOD__);
			$uploads["total number of submitted images"] = $dvCount;
		}

		$wgMemc->set($key, $uploads, strtotime("+1 day"));

		return $uploads;
	}

	private function getStatsTitles() {
		global $wgMemc;
		if(!in_array('sysop',$this->getUser()->getGroups())) {
			return;
		}
		$rev = "1";
		$key = wfMemcKey('ucistats', 'titles', $rev);
		$titles = $wgMemc->get($key);
		if (!$titles) {
			$dbr = wfGetDB(DB_SLAVE);
			$where = array("uci_upvotes > 2 and uci_downvotes < 2");
			$options = array("GROUP BY" => "uci_article_name");

			$res = $dbr->select(self::TABLE_NAME, array('uci_article_name as title', 'count(uci_article_name) as count'), $where, __METHOD__, $options);

			$titles = array();
			foreach($res as $row) {
				$title = $row->title;
				$number = $row->count;
				$titles[$title]['number'] = $number;;
			}
		}

		$wgMemc->set($key, $titles, strtotime("+1 day"));

		return $titles;
	}

	// this function uses very expensive queries and is now not called
	private function getStatsAnonAndFlagged() {
		if(!in_array('sysop',$this->getUser()->getGroups())) {
			return;
		}

		$result = array();
		$dbr = wfGetDB(DB_SLAVE);
		$anonupvotes = $dbr->selectField('logging', 'count(*)', array("log_type = 'ucipatrol'", "log_comment like 'Anon Upvoted%'"));
		$anondownvotes = $dbr->selectField('logging', 'count(*)', array("log_type = 'ucipatrol'", "log_comment like 'Anon Downvoted%'"));
		$anon = array("Anonymous Upvotes"=>$anonupvotes, "Anonymous Downvotes" => $anondownvotes);
		$result['anon'] = $anon;

		$where = array();
		$where[] = "log_type = 'ucipatrol'";
		$where[] = "log_comment LIKE 'Flag%'";
		$options = array("ORDER BY" => "log_timestamp DESC");
		$res = $dbr->select('logging', array('log_title', 'log_comment', 'log_timestamp'), $where, __METHOD__, $options);

		$removed = array();
		foreach($res as $row) {
			$comment = $row->log_comment;

			$image = explode("]]", $comment);
			$image = explode("[[", $image[0]);
			$image = $image[1];

			if (isset($removed[$image])) {
				continue;
			}

			$t = Title::newFromText($image);
			$id = $t->getArticleID();
			$downvotes = $dbr->selectField(self::TABLE_NAME,
					'uci_downvotes',
					array("uci_article_id" => $id));

			if ($downvotes < UserCompletedImages::DOWNVOTES) {
				continue;
			}

			$time = $row->log_timestamp;

			$removed[$image] = array(
							   'time' => $time,
							   'id' => $id,
							   'from' => $row->log_title);
		}

		$result['flagged'] = $removed;

		return $result;
	}

	private function getNext() {
		$content = array();

		$guestId = WikihowUser::getVisitorId();
		$content['user_voter'] = UCIPatrol::getUserAvatar($this->getUser(), $guestId);

		$content['required_upvotes'] = UserCompletedImages::UPVOTES;
		$content['required_downvotes'] = UserCompletedImages::DOWNVOTES;
		$content['vote_mult'] = $this->getVoteMultiplier();

		if (class_exists('Plants') && Plants::usesPlants('PicturePatrol') ) {
			$plants = new UCIPlants();
			$row = $plants->getNextPlant();
			$content['pqu_id'] = $row->pqu_id;
		}

		if ( $row == null) {
			$skipped = UCIPatrol::getSkipList();
			MWDebug::log("skip list is " . implode(", ", $skipped));

			$count = UCIPatrol::getCount() - count($skipped);
			MWDebug::log("count is $count");
			$content['uciCount'] = $count;

			$where = array();
			$where[] = "uci_downvotes < " . UserCompletedImages::DOWNVOTES;
			$where[] = "uci_upvotes < " . UserCompletedImages::UPVOTES;
			$where[] = "uci_copyright_violates = 0";
			$where[] = "uci_copyright_error = 0";
			$where[] = "uci_copyright_checked = 1";
			$where[] = "uci_article_id > 0";
			$where[] = "uci_on_whitelist = 1";

			if ( $skipped ) {
				$where[] = "uci_article_id NOT IN ('" . implode("','", $skipped) . "')";
			}

			$options = array("ORDER BY" => "uci_article_id", "LIMIT" => 1);

			$dbr = wfGetDB(DB_SLAVE);
			$row = $dbr->selectRow(self::TABLE_NAME, array('*'), $where, __METHOD__, $options);
		}

		$content['pageId'] = $row->uci_article_id;
		$content['upvotes'] = $row->uci_upvotes;
		$content['downvotes'] = $row->uci_downvotes;
		// $content['sql' . $i] = $dbr->lastQuery();
		// $content['row'] = $row;

		if($row === false) {
			MWDebug::log("no more images to patrol");
			return $content;
		}

		$title = Title::newFromText($row->uci_article_name);

		$content['articleTitle'] = $title->getText();
		$content['articleId'] = $title->getArticleId();
		$content['articleDisplayTitle'] = wfMessage("Howto", $title->getText())->text();
		$content['articleURL'] = $title->getPartialUrl();

		if(!$title) {
			MWDebug::log("no title: ".$title);
			$content['error'] = "notitle";
			$this->fullDownVote($content['pageId']);
			return $content;
		}

		// get data about the completion image
		$file = UserCompletedImages::fileFromRow($row);
		if (!$file) {
			MWDebug::warning("no file with image name ".$row->uci_image_name);
			$content['error'] = "filenotfound";
			$this->fullDownVoteImg($row->uci_image_name);
			return $content;
		}
		$content['uci_image_name'] = $row->uci_image_name;

		// get info about the originating page the image was added for
		$revision = Revision::newFromTitle($title);
		if(!$revision) {
			MWDebug::log("no revision");
			$content['error'] = "norevision";
			$this->fullDownVote($content['pageId']);
			return $content;
		}

		if ($title->isRedirect()) {
			MWDebug::log("is a redirect: ".$title);
			$wtContent = $revision->getContent();
			$title = $wtContent->getUltimateRedirectTarget();

			// edge case if there are just too many redirects, just skip this
			if ($title->isRedirect()) {
				MWDebug::log("too many redirects..skipping".$title);
				$content['error'] = "redirect";
				return $content;
			}

			$revision = Revision::newFromTitle($title);
			$content['articleTitle'] = $title->getText();
			$content['articleDisplayTitle'] = wfMessage("Howto", $title->getText())->text();
			$content['articleURL'] = $title->getPartialUrl();

			UCIPatrol::updateArticleName($row, $title->getText());
		}

		$isMobile = MobileContext::singleton()->shouldDisplayMobileView();
		if ($isMobile) {
			$content['article'] = MobileUCIPatrol::getArticleHtml($revision, $title);
		}
		else {
			$content['article'] = $this->getArticleHtml($revision, $title);
		}

		$width = $file->getWidth();

		// scale width so that the height is no greater than PATROL_THUMB_HEIGHT
		if ($file->getHeight() > self::PATROL_THUMB_HEIGHT) {
			$ratio = self::PATROL_THUMB_HEIGHT / $file->getHeight();
			$width = floor($width * $ratio);
		}

		// now that we have possibly scaled the width down to fit our max height..
		// we also will potentially scale down the width if it is still larger
		// than will fit on the patrol page
		$width = $width < self::PATROL_THUMB_WIDTH ? $width : self::PATROL_THUMB_WIDTH;
		$thumb = $file->getThumbnail($width);
		$content['thumb_url'] = $thumb->getUrl();
		$content['width'] = $thumb->getWidth();
		$content['height'] = $thumb->getHeight();

		// this is the page id of the image file itself not the same as articleTitle
		// used for skipping

		$voters = UCIPatrol::getVoters($row->uci_article_id);

		$content['voters'] = $voters;
		foreach($voters as $voter) {
			$id = $this->getUser()->isAnon() ? $guestId : $this->getUser()->getID();

			if ($voter['id'] == $id && $voter['vote'] != 0) {
				MWDebug::log("duplicate voter".$id);
				$content['error'] = "alreadyvoted";
				return $content;
			}
		}

		return $content;
	}

	private function updateArticleName($row, $newArticleName) {

		$dbw = wfGetDB(DB_MASTER);
		$conds = array("uci_article_id" => $row->uci_article_id);
		$values = array("uci_article_name" => $newArticleName);
		MWDebug::log("will update ".$row->uci_article_id . " with ". $newArticleName);
		$dbw->update(self::TABLE_NAME, $values, $conds);
	}

	private function userInUCIAdminGroup($user) {
		$groups = $user->getGroups();

		if(in_array('sysop',$groups) || in_array('staff', $groups) || in_array('newarticlepatrol', $groups)) {
			return true;
		}
		return false;
	}

	private function getVoters($pageId) {
		$result = array();

		$dbr = wfGetDB(DB_SLAVE);
		$table = "image_votes";
		$vars = array("iv_userid", "iv_vote");
		$where = array("iv_pageid"=>$pageId);

		$res = $dbr->select($table, $vars, $where);
		foreach ($res as $row) {

			// special case for anons that have voted..we stored their 'id' as negative
			if ($row->iv_userid < 0) {
				$voter = User::newFromId(0);
			} else {
				$voter = User::newFromId($row->iv_userid);
			}

			$admin = UCIPatrol::userInUCIAdminGroup($voter);
			$avatar = UCIPatrol::getUserAvatar($voter, $row->iv_userid);
			$result[] = array(
					"name"=>$avatar['name'],
					"vote"=>$row->iv_vote,
					"image"=>$avatar['image'],
					"admin_vote"=>$admin,
					"id"=>$row->iv_userid
					);
		}
		return $result;
	}

	public static function getCount() {
		$dbr = wfGetDB(DB_SLAVE);
		$where = array();
		$where[] = "uci_downvotes < ".UserCompletedImages::DOWNVOTES;
		$where[] = "uci_upvotes < ".UserCompletedImages::UPVOTES;
		$where[] = "uci_copyright_violates = 0";
		$where[] = "uci_copyright_error = 0";
		$where[] = "uci_copyright_checked = 1";
		$where[] = "uci_on_whitelist = 1";
		$where[] = "uci_article_id > 0";
		return $dbr->selectField(self::TABLE_NAME, 'count(*) as count', $where);
	}

	function displayLeaderboards() {
		$stats = new UCIPatrolStandingsIndividual();
		$stats->setContext($this->getContext());
		$stats->addStatsWidget();
		$standings = $stats->getGroupStandings();
		$standings->setContext($this->getContext());
		$standings->addStandingsWidget();
	}

	// get the number of UCI images showing on a page
	public static function getNumUCIForPage($pageTitle) {
		if (!$pageTitle) {
			return 0;
		}
		$pageTitle = str_replace( ' ', '-', $pageTitle );

		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(
			self::TABLE_NAME,
			'count(*) as ct',
			UserCompletedImages::getUCIQuery($pageTitle),
			__METHOD__
		);
		foreach ( $res as $row ) {
			return($row->ct);
		}

	}

	public function isMobileCapable() {
		return true;
	}

	public static function getImagesToBeCopyrightChecked($limit = null) {
		$dbr = wfGetDB(DB_SLAVE);
		$options = [];
		if($limit) {
			$options['LIMIT'] = $limit;
		}
		$res = $dbr->select(self::TABLE_NAME, ['uci_article_id'], ['uci_copyright_checked' => 0], __METHOD__, $options);

		$ids = [];
		foreach($res as $row) {
			$ids[] = $row->uci_article_id;
		}

		return $ids;
	}

	public static function markCopyright($pageId, $violates, $matches = 0, $error = 0) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(self::TABLE_NAME, 
			['uci_copyright_checked' => 1, 'uci_copyright_error' => $error, 'uci_copyright_violates' => $violates, 'uci_copyright_matches' => $matches],
			['uci_article_id' => $pageId],
			__METHOD__
		);
		if($violates == 1) {
			//first get the image name
			$dbr = wfGetDB(DB_SLAVE);
			$imageName = $dbr->selectField(self::TABLE_NAME, 'uci_image_name', ['uci_article_id' => $pageId], __METHOD__);
			if($imageName !== false) {
				wfRunHooks("PicturePatrolResolved", [$imageName, false]);
			}
		}
	}
}
