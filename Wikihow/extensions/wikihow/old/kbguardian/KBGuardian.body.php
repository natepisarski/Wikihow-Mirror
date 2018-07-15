<?php

/*
* A tool to review and vote on submitted knowledge box content
*/
class KBGuardian extends SpecialPage {
	var $data = null;
	var $skipTool = null;
	var $trustScore = 0;
	const TABLE_NAME = "knowledgebox_contents";
	const UP_VOTE = "kbc_up_votes";
	const DOWN_VOTE = "kbc_down_votes";
	const MAYBE_VOTE = "kbc_maybe"; //really only used for the planted questions
	const NOTSURE_VOTE = "kbc_notsure"; //really only used for the planted questions
	const VOTES_TO_PATROL = 1.5;	// +/- vote difference before removing entry from queue
	const MAX_VOTES = 5.5;	//Max number of votes on an entry before removing it from queue

	function __construct() {
		global $wgHooks;

		parent::__construct("KBGuardian", "KBGuardian");
		$this->skipTool = new ToolSkip("kbguardian", self::TABLE_NAME);
		$wgHooks['getMobileToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	function execute($par) {
		# Check blocks
		$user = $this->getUser();
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$out = $this->getOutput();
		$out->setRobotPolicy("noindex,follow");
		$request = $this->getRequest();

		if ($request->getVal('cc')) {
			$this->clearSkip();
		}

		if ($request->wasPosted()) {
			$userTrust = new UserTrustScore('kb_guardian');
			$this->trustScore = $userTrust->getScore();
			
			$out->setArticleBodyOnly(true);

			$this->data = $request->getArray('data');
			$action = $request->getVal('a', 'getNext');

			if ($this->data['kbc_id'] == -1 && $action != 'getNext') {
				$this->savePlantVote($action);
				$action = 'getNext';
			}

			// Anons only have the illusion of their patrol
			// votes counting.  Just discard and give them the next
			// recent change.
			// if ($this->getUser()->isAnon()) {
			// 	$action = 'getNext';
			// }

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
					$result['error'] = wfMessage('kbg-invalid-action');
			}

			print_r(json_encode($result));
			return;
		}

		$out->setPageTitle(wfMessage('kbguardian_title'));
		$out->addSubtitle("Blah");
		$out->setHTMLTitle(wfMessage('kbguardian_title'));
		$this->addModules();

		$tmpl = new EasyTemplate(dirname(__FILE__));
		if (Misc::isMobileMode()) {
			$tmplName = 'kbguardian.tmpl.php';
			$vars = $this->getTemplateVars();
			$vars['tool_info'] = class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : '';
			$tmpl->set_vars($vars);
		} else {
			$tmplName = 'kbguardian_desktop.tmpl.php';
		}
		$out->addHTML($tmpl->execute($tmplName));

		$this->addStandingGroups();
	}

	protected function getTemplateVars() {
		$adw = new ArticleDisplayWidget();
		return $adw->addTemplateVars();
	}

	protected function addStandingGroups() {
		if (!Misc::isMobileMode()) {
			if ($this->getUser()->isLoggedIn()) {
				$indi = new KBGuardianStandingsIndividual();
				$indi->addStatsWidget();
			}

			$group = new KBGuardianStandingsGroup();
			$group->addStandingsWidget();
		}
	}


	protected function getArticleHtml($title) {
		$html = "";
		if (!Misc::isMobileMode()) {
			$out = $this->getOutput();
			$revision = Revision::newFromTitle($title);
			$popts = $out->parserOptions();
			$popts->setTidy(true);
			$parserOutput = $out->parse($revision->getText(), $title, $popts);
			$magic = WikihowArticleHTML::grabTheMagic($revision->getText());
			$html = WikihowArticleHTML::processArticleHTML(
				$parserOutput,
				array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
		}
		return $html;
	}

	public function isMobileCapable() {
		return true;
	}

	/*
	 * Get a piece of knowledgebox content.  Try a few times
	 * if we get back an error
	 */
	protected function getNextContent() {
		// Don't loop more than 5 times
		$i = 0;
		while ($i < 5 && $result = $this->getNext()) {
			$i++;
			if (!empty($result['error'])
				&& $result['error'] != wfMessage('kbg-queue-end')->text()) {
				$id = $result['kbc_id'];
				if (!empty($id) && $id > 0) {
					$this->skipId($id);
				}
			} else if (!isset($result['title']) || !$result['title']) {
				if (isset($result['kbc_aid'])) {
					$this->skipId($result['kbc_id']);
				}
			} else {
				$id = $result['kbc_id'];
				if (!empty($id) && $id > 0) {
					$this->skipId($id);
				}
				break;
			}
		}
		return $result;
	}

	/**
	 * @return Get the next piece of knowledge
	 *
	 * Note: to decrease number of db queries we don't "check out"
	 * an item like we do in other tools.  The trade-off being that more
	 * votes may be recorded for a a piece of knowledge than are necessary
	 * to patrol if there is a high volume of concurrent activity for the
	 * tool.
	 *
	 * We don't expect there to be high concurrency and are comfortable
	 * having more votes than necessary in that case, which is why we have taken
	 * this approach
	 */
	protected function getNext() {
		$result = array();

		$dbr = wfGetDB(DB_SLAVE);

		$next = null;
		if(class_exists('Plants') && Plants::usesPlants('KBGuardian') ) {
			$plants = new KnowledgePlants();
			if($this->data['kbc_id'] == 0) {
				//this is only called on the very first load of the tool
				$next = $plants->getNextPlant(1);
			} else {
				//this is for preloading of the next question so we want 2 after the last question answered
				$next = $plants->getNextPlant(2);
			}
			if ($next != null) {
				$row = $next;
			}
		}

		if ($next == null) {
			$where = array('kbc_patrolled' => 0);
			$skippedIds = $this->getSkippedIds();
			if (is_array($skippedIds) && !empty($skippedIds)) {
				$where[] = "kbc_id not in (" . implode(',', $skippedIds) . ")";
			}
		
			// Don't show plagiarsm
			$where[] = "(kbc_plagiarism_ignore = '0' and kbc_plagiarism_checked != '' and kbc_plagiarized = '0') or kbc_plagiarism_ignore = '1'";


			$res = $dbr->select(
				self::TABLE_NAME,
				array(
					'kbc_id',
					'kbc_aid',
					'kbc_up_votes',
					'kbc_down_votes',
					'kbc_content',
					'kbc_total_votes'
				),
				$where,
				__METHOD__,
				//Display KBG entries with the most votes first. Resolve ties by displaying newer entries first.
				array("ORDER BY" => "kbc_total_votes desc, kbc_timestamp desc", "LIMIT" => 1)
			);
		}

		if ($next || $row = $dbr->fetchObject($res)) {
			$result = get_object_vars($row);
			$t = Title::newFromId($result['kbc_aid']);
			if ($t && $t->exists()) {
				$result['html'] = $this->getArticleHtml($t);
				$result['title'] = $t->getDBkey();
			} else {
				$result['error'] = wfMessage('kbg-error-missing-title')->text();
			}
		} else {
			$result['error'] = wfMessage('kbg-queue-end')->parse();
		}
		return $result;
	}

	protected function addModules() {
		$out = $this->getOutput();
		if (Misc::isMobileMode()) {
			$out->addModules(array(
				'ext.wikihow.UsageLogs',
				'ext.wikihow.mobile.kbguardian.scripts'
			));	// KBGuardian js and mw messages
			$out->addModuleStyles('ext.wikihow.kbguardian.styles');	// KBGuardian css
		} else {
			$out->addModules(array(
				'ext.wikihow.UsageLogs',
				'ext.wikihow.kbguardian.scripts',
				'ext.wikihow.desktop.kbguardian.styles'
			));	// KBGuardian js and mw messages
		}

	}

	protected function savePlantVote($voteType) {
		if ($this->data['kbc_id'] == -1) {
			//double check to make sure this is really a planted question
			$plant = new KnowledgePlants();
			switch ($voteType) {
				case "vote_up":
					$answer = 1;
					break;
				case "vote_down":
					$answer = 0;
					break;
				case "maybe":
					$answer = -1;
					break;
				case "not_sure":
				default:
					$answer = -2;
					break;
			}
			$plant->savePlantAnswer($this->data['pqk_id'], $answer);
		}
	}

	/*
	 * Record an up or down vote.  Mark the row patrolled if the number of
	 * votes needed for patrolling has been met
	 *
	 */
	protected function vote($voteType) {
		$data = $this->data;
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			self::TABLE_NAME,
			array(
				"$voteType"  => $data[$voteType] + $this->trustScore,
				"kbc_total_votes" => $data["kbc_total_votes"]+$this->trustScore, //Update the total number of votes in the DB
				"kbc_last_vote_timestamp" => wfTimestampNow(),
				'kbc_patrolled' => $this->isResolved($voteType) ? 1 : 0
			),
			array(
				'kbc_id' => $data['kbc_id']
			),
			__METHOD__
		);
	}

	protected function isResolved($voteType) {
		$data = $this->data;
		
		if ($voteType == self::UP_VOTE) {
			$resolved = (($data[self::UP_VOTE] + $this->trustScore) - $data[self::DOWN_VOTE]) >= self::VOTES_TO_PATROL;
		} else {
			$resolved = (($data[self::DOWN_VOTE] + $this->trustScore) - $data[self::UP_VOTE]) >= self::VOTES_TO_PATROL;
		}

		//Calculate the total number of votes, and remove entry for KBG queue if it's greater or equal to MAX_VOTES
		$totalVotes = $data[self::UP_VOTE] + $data[self::DOWN_VOTE];
		if($totalVotes >= self::MAX_VOTES){
			$resolved = TRUE;
		}
		return $resolved;
	}

	protected function getSkippedIds() {
		return $this->skipTool->getSkipped();
	}

	protected function skipId($id) {
		$this->skipTool->skipItem($id);
	}

	protected function clearSkip() {
		$this->skipTool->clearSkipCache();
	}

	protected function logAction($action) {
		if ($this->data['kbc_id'] != -1) {
			// Add a log entry, only if not a planted question
			$t = Title::newFromId($this->data['kbc_aid']);
			if ($t && $t->exists()) {
				$log = new LogPage( 'kbguardian', false );
				$logType = Misc::isMobileMode() ? "m" : "d";
				$msg = wfMessage("kbg-edit-message-$logType")->rawParams("[[{$t->getText()}]]")->escaped();
				$log->addEntry($action, $t, $msg, null);
			}
		}
	}

	/*
	 * We no longer want to show knowledgebox submissions for articles that have been
	 * completed by our editors in the Knowledge Guardian. This hook patrols
	 * submissions of that article that haven't yet been patrolled
	 */
	public static function  onEditfishArticlesCompleted($aids, $langCode, $wu) {
		if (!empty($aids)) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(
				self::TABLE_NAME,
				array('kbc_patrolled' => 1),
				array(
					'kbc_aid in (' . implode(",", $aids) . ')',
					'kbc_patrolled' => 0),
				__METHOD__
			);
		}
		return true;
	}

}
