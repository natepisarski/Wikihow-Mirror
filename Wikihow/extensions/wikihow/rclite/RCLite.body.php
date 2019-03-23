<?php
/*
* A "lite" version of RC Patrol which handles 3 types of edits
 *
 * 1) New Talk page messages
 * 2) Spellchecker edits
 * 3) New tips that are added
*/
class RCLite extends MobileSpecialPage {
	const EDIT_SPELLCHECKER = 'spelling';
	const EDIT_USER_TALK = 'talk';
	const EDIT_TIP = 'tip';
	var $data = null;

	function __construct() {
		global $wgHooks;

		parent::__construct("RCPatrol", "rcpatrol");
		$wgHooks['getMobileToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	function executeWhenAvailable($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		$out->setRobotPolicy("noindex,follow");
		$request = $this->getRequest();

		# Check blocks
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ($request->wasPosted()) {
			$out->setArticleBodyOnly(true);

			// Checks if the user has a throttle in a place and if they reached their limit for the day
			// Requires PatrolThrottle extension
			if ( class_exists( 'PatrolUser' ) ) { // you can safely disable this extension by simply commenting it out of imports.php
				$patroller = PatrolUser::newFromUser($this->getUser());
				if ( !$patroller->canUseRCPatrol(false) ) {
					$result['error'] = PatrolUser::getThrottleMessageHTML(false);
					print(json_encode($result));
					return;
				}
			}

			// Show an error message for users who don't have patrol permissions.
			// This is usually because they have been patrol blocked.
			// Anons don't have permissions to patrol but they also don't have votes
			// that count so we can ignore them at this juncture.
			if (!$user->isAnon() && !$user->isAllowed('patrol')) {
				$result['error'] = wfMessage('rcl-error-patrolblocked')->text();
				print(json_encode($result));
				return;
			}

			$this->data = $request->getArray('data');
			$rcid = $this->data['rc_id'];
			$action = $request->getVal('a', 'getNext');

			// Anons only have the illusion of their patrol
			// votes counting.  Just discard and give them the next
			// recent change.
			if ($this->getUser()->isAnon()) {
				$action = 'getNext';
			}

			switch ($action) {
				case 'patrol':
					$this->markPatrolled($rcid);
					$result = $this->getNextRC();
					break;
				case 'skip':
				case 'getNext':
					$result = $this->getNextRC();
					break;
				case 'rollback':
					$rollbackDetails = $this->rollback();
					$result = $this->getNextRC();
					break;
				default:
					$result['error'] = wfMessage('rcl-invalid-action')->text();
			}

			print(json_encode($result));
			return;
		}

		$out->setPageTitle(wfMessage('rclite_title')->text());
		$out->setHTMLTitle(wfMessage('rclite_title')->text());
		$this->addModules();
		//$this->setHeaders();
		$tmpl = new EasyTemplate(__DIR__);
		$vars['anon'] = $this->getUser()->isAnon();
		$tmpl->set_vars($vars);
		$out->addHTML($tmpl->execute('rclite.tmpl.php'));

	}

	protected function getDisplayHtml($lRev, $rRev) {
		$txt = $this->modifyWikiText($lRev, $rRev);

		// Special case to weed out user talk edits that aren't comments
		if ($txt['type'] == self::EDIT_USER_TALK
			&& !preg_match("@{{comment_(header|footer)@mi", $txt['txt'])) {
			throw new Exception(wfMessage('rcl-error-unknown-edit')->text());
		}

		$html = $this->getArticleHtml($rRev->getTitle(), $rRev, $txt['txt']);
		if ($txt['type'] == self::EDIT_USER_TALK) {
			$html = Avatar::insertAvatarIntoDiscussion($html);
		}

		return array('html' => $html, 'type' => $txt['type']);
	}

	protected function modifyWikiText($lRev, $rRev) {
		global $wgContLang;

		$lTxt = $lRev->getText();
		$rTxt = $rRev->getText();
		$rTitle = $rRev->getTitle();
		$newTxt = $lTxt;

		$lTxt = str_replace( "\r\n", "\n", $lTxt );
		$rTxt = str_replace( "\r\n", "\n", $rTxt );
		$lta = explode( "\n", $wgContLang->segmentForDiff( $lTxt ) );
		$rta = explode( "\n", $wgContLang->segmentForDiff( $rTxt ) );
		$diffs = new Diff( $lta, $rta );

		$formatter = new ArrayDiffFormatter();
		$diffs = $wgContLang->unsegmentForDiff( $formatter->format( $diffs ) );
		foreach($diffs as $d) {
			if ($rTitle->inNamespace(NS_MAIN) && $d['action'] == 'change') {
				// Spellchecker edits only.  Limit to main namespace to get rid of user_talk messsages
				$type = self::EDIT_SPELLCHECKER;
				$lta = explode( "\n", $wgContLang->segmentForDiff( $d['old'] ) );
				$rta = explode( "\n", $wgContLang->segmentForDiff( $d['new'] ) );
				$diff = new WordLevelDiff($lta, $rta);
				$old = "";
				$new = "";


				foreach ($diff->edits as $change) {
					$change = get_object_vars($change);
					if ($change['type'] == 'copy') {
						$old .= implode("", $change['orig']);
						$new .= implode("", $change['closing']);
					} elseif ($change['type'] == 'change') {
						$old .= implode("", $change['orig']);
						$new .= $this->formatOldChange(implode("", $change['orig']));
						$new .= $this->formatNewChange(implode("", $change['closing']));
					} elseif ($change['type'] == 'add') {
						$new .= $this->formatNewChange(implode("", $change['closing']));
					} else {
						throw new Exception(wfMessage("rcl-bad-rc")->text());
					}
				}
				$newTxt = str_replace($old, $new, $newTxt);
				$txt = $newTxt;

			} elseif ($d['action'] == 'add') {
				if ($rRev->getTitle()->inNamespace(NS_USER_TALK)) {
					// New Talk page messages
					$type = self::EDIT_USER_TALK;
					$txt  .= $d['new'];
				} else {
					// New Tips
					$type = self::EDIT_TIP;
					$newTxtLines = explode("\n", $newTxt);
					$tip = preg_replace("@^ *\* *@", "", $d['new']);
					$tip = "* " . $this->formatNewChange($tip);
					array_splice($newTxtLines, $d['newline'] - 1, 0, $tip);
					$txt = implode("\n", $newTxtLines);
					break; // There should only be one
				}
			} else {
				// Couldn't recognize the edit type.  Throw exception to be handled
				// somewhere up the stack.
				throw new Exception(wfMessage('rcl-error-unknown-edit')->text());
			}
		}

		return array("type" => $type, "txt" => $txt);
	}

	protected function getArticleHtml($title, $revision, $wikitext) {
		$config = WikihowMobileTools::getToolArticleConfig();
		$html = WikihowMobileTools::getToolArticleHtml($title, $config, $revision, $wikitext);
		return $html;
	}

	private function formatOldChange($change) {
		return "<span class='rcl_old_change'>$change</span>";
	}

	private function formatNewChange($change) {
		return "<span class='rcl_new_change'>$change</span>";
	}

	public function isMobileCapable() {
		return true;
	}

	protected function markPatrolled($rcid) {
		$rc = RecentChange::newFromId( $rcid );
		if (!is_null($rc)) {
			RecentChange::markPatrolled($rcid, false);
			$t = $rc->getTitle();
			$a = new Article($t);
			$rcids = array($rcid);
			$user = $this->getUser();
			Hooks::run( 'MarkPatrolledBatchComplete', array(&$a, &$rcids, &$user));
		}
	}


	protected function getNextRC() {
		// Don't loop more than 5 times
		$i = 0;
		while ($i < 5 && $result = $this->getNext()) {
			$i++;
			//var_dump($i,$result);
			if (!empty($result['error'])) {
				$aid = $result['rc_cur_id'];
				if (!empty($aid) && $aid > 0) {
					$this->skipArticle($aid);
				}
				// For unknown edits we'll try to fetch another few to find a good edit
				// since these cases should be rare
				if ($result['error'] !== wfMessage('rcl-error-unknown-edit')->text()) {
					//var_dump('breaking');
					break;
				}
			} elseif (!isset($result['title']) || !$result['title']) {
				if (isset($result['rc_cur_id'])) {
					self::skipArticle($result['rc_cur_id']);
				}
			} else {
				$aid = $result['rc_cur_id'];
				if (!empty($aid) && $aid > 0) {
					$this->skipArticle($aid);
				}
				break;
			}
		}
		return $result;
	}

	/**
	 * @return String
	 */
	protected function getNext() {
		$result = array();

		$dbw = wfGetDB(DB_MASTER); // Use master to prevent propagation delay

		$where = array(
			"(rc_namespace = " . NS_USER_TALK . " and rc_old_len < rc_new_len and rc_last_oldid != 0) or (rc_namespace = " . NS_MAIN . " and (rc_comment IN ('New tip approved in Tips Patrol','New tip approved in QG','Fixing misspellings via the Spellchecker tool')))",
			"rc_patrolled" => 0,
			"rc_user_text != '" . $this->getUser()->getName() . "'");

		$skippedIds = $this->getSkippedIds();
		if (is_array($skippedIds) && !empty($skippedIds)) {
			$where[] = "rc_cur_id not in (" . implode(',', $skippedIds) . ")";
		}

		$res = $dbw->select(
			'recentchanges',
			array(
				'rc_id',
				'rc_namespace',
				'rc_cur_id',
				'rc_last_oldid',
				'rc_this_oldid',
				'rc_user_text',
				'rc_title',
			),
			$where,
			__METHOD__,
			array("ORDER BY" => "rc_timestamp DESC", "LIMIT" => 1)
		);

		if ($row = $dbw->fetchObject($res)) {
			$result = get_object_vars($row);
			$lRev = Revision::newFromId($row->rc_last_oldid);
			$rRev = Revision::newFromId($row->rc_this_oldid);
			if (empty($lRev) || empty($rRev)) {
				$result['error'] = wfMessage('rcl-error-missing-revision')->text();
			} else {
				// Sometimes we can't handle certain types of edits in talk page messages. If we can't
				// and exception is thrown and we return as an error to getNextRC
				try {
					$html = $this->getDisplayHtml($lRev, $rRev);
					$result['html'] = $html['html'];
					$result['type'] = $html['type'];
					$result['title'] = $result['rc_title'];
					$result['rollback_token'] = $this->getRollbackToken($row);
				} catch(Exception $e) {
					$result['error'] = $e->getMessage();
				}
			}
		} else {
			$eoq = new EndOfQueue();
			$result['error'] = $eoq->getMessage('rc');
		}
		return $result;
	}

	protected function addModules() {
		$out = $this->getOutput();
		$out->addModules(array(
			'mobile.rclite',
			'ext.wikihow.UsageLogs'
		));
	}

	/*
	 * Get the article ids in the rc patrol skip cookie.
	 * NOTE: This list is shared between RCLite and RCPatrol
	 */
	protected function getSkippedIds() {
		global $wgCookiePrefix;

		// has the user skipped any articles?
		$cookiename = $wgCookiePrefix."Rcskip";
		$ids = array();
		if (isset($_COOKIE[$cookiename])) {
			$cookie_ids = array_unique(explode(",", $_COOKIE[$cookiename]));
			foreach ($cookie_ids as $id) {
				$id = intval($id);
				if ($id > 0) $ids[] = $id;
			}
		}
		return $ids;
	}

	/*
	 * Add an article id to the skip list cookie
	 * NOTE: This list is shared between RCLite and RCPatrol
	 */
	protected function skipArticle($aid) {
		RCPatrol::skipArticle($aid);
	}

	protected function rollback() {
		$data = $this->data;
		$details = array();
		$p = WikiPage::newFromID($data['rc_cur_id']);
		$currentRevId= $p->getRevision()->getId();
		if ($currentRevId == $data['rc_this_oldid'] && !empty($p) && $p->exists()) {
			$u = $this->getUser();
			$p->doRollback(
				$data['rc_user_text'],
				'',
				$data['rollback_token'],
				false,
				$details,
				$u
			);
		}

		return $details;
	}

	protected function getRollbackToken($data) {
		$t = Title::newFromID($data->rc_cur_id);
		$token = "";
		if ($t && $t->exists()) {
			$u = $this->getUser();
			if ($u->isAnon()) {
				$u = User::newFromName('rclite_bot');
			}
			$token = $u->getEditToken(array($t->getPrefixedText(), $data->rc_user_text));
		}
		return $token;
	}
}
