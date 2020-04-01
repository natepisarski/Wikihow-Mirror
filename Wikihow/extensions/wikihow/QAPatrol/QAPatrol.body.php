<?php
/*
CREATE TABLE `qa_patrol` (
	`qap_id` int(10) PRIMARY KEY AUTO_INCREMENT,
	`qap_sqid` int(10) NOT NULL DEFAULT 0,
	`qap_submitter_name` varbinary(255) NOT NULL DEFAULT '',
	`qap_submitter_user_id` int(10) NOT NULL DEFAULT 0,
	`qap_submitter_email` blob NOT NULL,
	`qap_page_id` int(10) NOT NULL DEFAULT 0,
	`qap_user_id` int(10) NOT NULL DEFAULT 0,
	`qap_visitor_id` varbinary(20) NOT NULL DEFAULT '',
	`qap_question` blob NOT NULL,
	`qap_answer` blob NOT NULL,
	`qap_timestamp` varbinary(14) NOT NULL DEFAULT '',
	`qap_vote_yes` int(4) NOT NULL DEFAULT 0,
	`qap_vote_no` int(4) NOT NULL DEFAULT 0,
	`qap_vote_total` int(4) NOT NULL DEFAULT 0,
	`qap_skip` int(4) NOT NULL DEFAULT 0,
	`qap_aqid` int(10),
	`qap_checkout_time` varbinary(14) NOT NULL DEFAULT '',
	`qap_copycheck` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
	`qap_articles_questions` int(4) NOT NULL DEFAULT 0,
	UNIQUE KEY (`qap_sqid`),
	KEY (`qap_timestamp`),
	KEY (`qap_vote_total`),
	KEY (`qap_articles_questions`),
	UNIQUE KEY (`qap_aqid`),
	KEY `qap_submitter_email` (`qap_submitter_email`(16))
);
CREATE TABLE `qap_vote` (
	`qapv_qapid` int(10) NOT NULL DEFAULT 0,
	`qapv_user_id` int(10) NOT NULL DEFAULT 0,
	`qapv_visitor_id` varbinary(20) NOT NULL DEFAULT '',
	`qapv_vote` tinyint(3) NOT NULL DEFAULT 0,
	`qapv_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	KEY (`qapv_user_id`),
	KEY (`qapv_visitor_id`),
	KEY (`qapv_timestamp`)
);
*/

/*
temp table for logging answer emails

CREATE TABLE `qap_answer_emails` (
	`qae_id` int(10) PRIMARY KEY AUTO_INCREMENT,
	`qae_sqid` int(10) NOT NULL DEFAULT 0,
	`qae_email` blob NOT NULL,
	`qae_submit_time` varbinary(14) NOT NULL DEFAULT '',
	`qae_check_time` varbinary(14) NOT NULL DEFAULT '',
	`qae_good_date` int(4) NOT NULL DEFAULT 0
);
*/

class QAPatrol extends UnlistedSpecialPage {

	const QAPATROL_MAX_YES = 3;
	const QAPATROL_MAX_NO = 2;
	const QAPATROL_BAD_ARTICLE = 'bad_article';
	const QAPATROL_NO_ARTICLES = 'no_articles';
	const QAPATROL_ATTEMPTS = 5;
	const QAPATROL_SIMILARITY_THRESHOLD = .6;

	var $qap_use_article_grouping = true;
	var $qap_from_article = false; //we treat article unpatrolled actions differently

	var $qap_expert_mode = false;
	var $qap_top_answerer_mode = false;

	var $qa_edited = false;

	public function __construct() {
		parent::__construct( 'QAPatrol');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		//logged in and desktop only
		if (!$user || $user->isAnon() || Misc::isMobileMode() || !self::isAllowed($user)) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$this->skipTool = new ToolSkip("QAPatrol");
		$action = $request->getVal('action');

		//EXPERT or TOP ANSWERERS MODE?
		$userGroups = $user->getGroups();
		$this->qap_expert_mode = $request->getVal('expert') == 'true' && $this->canReviewExperts($userGroups);
		$this->qap_top_answerer_mode = $request->getVal('ta') == 'true' && $this->canReviewTopAnswerers($userGroups);

		if ($action == 'getQA') {
			$out->setArticleBodyOnly(true);

			//try this only QAPATROL_ATTEMPTS times
			for ($i=0; $i < self::QAPATROL_ATTEMPTS; $i++) {
				$qa = $this->getNextQA();
				if ($qa['title'] != self::QAPATROL_BAD_ARTICLE && $qa['title'] != self::QAPATROL_NO_ARTICLES) break;
			}

			//still a bad article? bah, forget it
			if ($qa['title'] == self::QAPATROL_BAD_ARTICLE || $qa['title'] == self::QAPATROL_NO_ARTICLES) $qa['title'] = '';

			if ($qa['title'] == '') {
				// $eoq = new EndOfQueue();
				$qa['remaining'] = 0;
				// $qa['eoq'] = $eoq->getMessage('qap');
				$qa['eoq'] = '<div class="qap_eoq">'.wfMessage('qap_eoq')->text().'</div>';
				print json_encode($qa);
				return;
			}

			$qa['remaining'] = self::getRemaining('',$this->qap_expert_mode, $this->qap_top_answerer_mode);
			$qa['votes_left'] = $this->getVotesLeft($qa['vote_yes'], $qa['vote_no']);
			if ($this->canAutoApprove()) $qa['user_data'] = $this->getUserHtml($qa);

			$this->checkoutQA($qa['qap_id']);

			print json_encode($qa);
			return;
		}
		elseif ($action == 'save') {
			$out->setArticleBodyOnly(true);

			//are we saving this from the article or from QAPatrol?
			if (!empty($request->getVal('from_article'))) $this->qap_from_article = true;

			$qap_id = $request->getVal('id');
			$question = $request->getVal('question');
			$answer = $request->getVal('answer');
			$expertId = $request->getInt('expert', 0); //need this now for qapatrol on article pages
			$removeSubmitter = $request->getInt('remove_submitter', 0) == 1; //need this now for qapatrol on article pages

			$res = $this->saveEdit($qap_id, $question, $answer, $expertId, $removeSubmitter);

			print json_encode($res);
			return;
		}
		elseif ($action == 'skip') {
			$out->setArticleBodyOnly(true);

			$this->skip($request->getVal('id'));
			return;
		}
		elseif ($action == 'vote') {
			$out->setArticleBodyOnly(true);

			$vote = $request->getVal('vote');
			$qap_id = $request->getVal('id');
			$res = $this->vote($vote, $qap_id);

			print json_encode($res);
			return;
		}
		elseif ($action == 'delete_question') {
			$out->setArticleBodyOnly(true);

			$qap_id = $request->getVal('id');
			$this->deleteQuestion($qap_id);
			return;
		}
		elseif ($action == 'stats') {
			$out->setPageTitle(wfMessage('cd-qap-title')->text());

			if (in_array('sysop',$user->getGroups())) {
				$out->addModules([
					'ext.wikihow.qa_patrol_stats',
					'ext.wikihow.qa_patrol_stats.style'
				]);

				$qap_stats = new QAPatrolStats(
					$username = $request->getVal('user'),
					$this->qap_expert_mode,
					$this->qap_top_answerer_mode
				);
				$html = $qap_stats->getStatsHTML();
				$out->addHTML($html);
			}

			return;
		} elseif ($action == 'checkout') { //need this now for qapatrol on article pages
			$out->setArticleBodyOnly(true);
			$qap_id = $request->getVal('id');
			$this->tryCheckoutQA($qap_id);
			return;
		} elseif ($action == 'uncheckout') { //need this now for qapatrol on article pages
			$out->setArticleBodyOnly(true);
			$qap_id = $request->getVal('id');
			$this->unCheckoutQA($qap_id);
			return;
		} elseif ($action == 'export') {
			$from = $request->getVal('from');
			$to = $request->getVal('to');
			$user = $request->getVal('user');

			if (!empty($from) && !empty($to)) {
				$this->exportSummaryCSV($from, $to, $user);
			}
			return;
		}

		$out->addModules('ext.wikihow.qa_patrol');
		$out->setHTMLTitle(wfMessage('cd-qap-title')->text());

		if ($this->qap_expert_mode)
			$remain_msg = 'qap_remaining_exp';
		elseif ($this->qap_top_answerer_mode)
			$remain_msg = 'qap_remaining_ta';
		else
			$remain_msg = 'qap_remaining';

		$vars['tool_info'] = class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : '';
		$vars['isPowerVoter'] = $this->isPowerVoter();
		$vars['expert_mode'] = $this->qap_expert_mode;
		$vars['top_answerer_mode'] = $this->qap_top_answerer_mode;
		$vars['remaining_text'] = wfMessage($remain_msg)->text();

		EasyTemplate::set_path( __DIR__.'/templates' );
		$html = EasyTemplate::html('qa_patrol.tmpl.php', $vars);

		$out->addHTML($html);
	}

	/*
	 * Get the next Q&A pair (and related data)
	 * First, grab Q&A pairs for articles w/o any published Q&As
	 * Second, (if none of those are found) grab Q&A pairs for articles w/ the fewest # of questions
	 */
	private function getNextQA() {
		$nextQA = [];
		$dbr = wfGetDB(DB_REPLICA);
		$row = '';
		$submitter_name = '';
		$submitter_link = '';
		$last_patroller_name = '';
		$last_patroller_link = '';

		//1) set up the basics...
		$tables = [
			QADB::TABLE_SUBMITTED_QUESTIONS,
			QADB::TABLE_QA_PATROL
		];

		$expired = $this->getExpiryTime();

		$where = [
			'qap_aqid IS NULL',
			"qap_checkout_time < '$expired'",
			'qap_copycheck' => 1,
			'qap_sqid = qs_id'
		];

		$options = [
			'USE INDEX' => [QADB::TABLE_QA_PATROL => 'qap_aqid'],
			'ORDER BY' => [
				"qap_submitter_email != '' desc",
				'qs_submitted_timestamp desc'
			],
			'LIMIT' => 1
		];

		//only show expert ones for a users w/ special perms
		if ($this->qap_expert_mode) {
			$where[] = 'qap_verifier_id > 0';
		}
		else {
			$where['qap_verifier_id'] = 0;
		}

		//top answerer mode
		if ($this->qap_top_answerer_mode) {
			$tables[] = TopAnswerers::TABLE_TOP_ANSWERERS;
			$where[] = 'qap_submitter_user_id = ta_user_id';
		}
		else {
			$tas = TopAnswerers::getAllTaUserIds();
			$where[] = 'qap_submitter_user_id NOT IN ('.implode(',',$tas).')';
		}

		$skippedIds = $this->skipTool->getSkipped();
		if (is_array($skippedIds) && !empty($skippedIds)) {
			//ignore skipped
			$where[] = "qap_id not in (" . implode(',', $skippedIds) . ")";
			//ignore last page
			if (!$this->qap_expert_mode && !$this->qap_top_answerer_mode) {
				$last_page = $dbr->selectField(
					QADB::TABLE_QA_PATROL,
					'qap_page_id',
					['qap_id = '.end($skippedIds)],
					__METHOD__
				);
				if ($last_page) $where[] = 'qap_page_id != '.$last_page;
			}
		}

		if ($this->qap_use_article_grouping && !$this->qap_expert_mode && !$this->qap_top_answerer_mode) {
			$where[] = 'qap_articles_questions < 3';
		}

		$res = $dbr->select($tables, '*', $where, __METHOD__, $options);
		$row = $dbr->fetchObject($res);

		//for debugging
		// $lastQuery = $dbr->lastQuery();

		if (is_null($row->qap_page_id)) {
			//none found
			$this->qap_use_article_grouping = false;
			$nextQA = [
				'title' => self::QAPATROL_BAD_ARTICLE,
				'aid' => $row->qap_page_id
			];
			return $nextQA;
		}

		$page = WikiPage::newFromId($row->qap_page_id);
		if ($page) {
			$out = $this->getOutput();
			$popts = $out->parserOptions();
			$popts->setTidy(true);
			$content = $page->getContent();
			if ($content) {
				$parserOutput = $content->getParserOutput($page->getTitle(), null, $popts, false)->getText();
				$article_html = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN));
				//add Q&A widget at the top
				$widget = new QAWidget($page->getTitle());
				$article_html = $widget->getWidgetHTML() . '<h2>'.wfMessage('qap_article_hdr')->text().'</h2>' . $article_html;
			}

			$sub_user = User::newFromId($row->qap_submitter_user_id);
			if ($sub_user) {
				$submitter_name = $sub_user->isAnon() ? wfMessage('qap_anon')->text() : $sub_user->getName();
				$submitter_link = $sub_user->isAnon() ? '' : '/'.$sub_user->getUserPage();
			}

			if ($row->qap_user_id) {
				$last_patroller = User::newFromId($row->qap_user_id);
				if ($last_patroller) {
					$last_patroller_name = $last_patroller->isAnon() ? wfMessage('qap_anon')->text() : $last_patroller->getName();
					$last_patroller_link = '/'.$last_patroller->getUserPage();
				}
			}

			$nextQA = [
				// 'sql' => $lastQuery,
				'title' => $page->getTitle()->getText(),
				'link' => $page->getTitle()->getDBkey(),
				'aid' => $page->getTitle()->getArticleId(),
				'question' => $row->qap_question,
				'answer' => $row->qap_answer,
				'answer_formatted' => $this->formatAnswer($row->qap_answer),
				'qap_id' => $row->qap_id,
				'vote_yes' => $row->qap_vote_yes,
				'vote_no' => $row->qap_vote_no,
				'qap_user' => $row->qap_user_id,
				'article' => $article_html,
				'qap_sqid' => $row->qap_sqid,
				'submit_time' => $row->qap_timestamp,
				'patroller_name' => $this->getUser()->getName(),
				'submitter_name' => $submitter_name,
				'submitter_link' => $submitter_link,
				'last_patroller_name' => $last_patroller_name,
				'last_patroller_link' => $last_patroller_link,
				'verifier_id' => $row->qap_verifier_id
			];
		}
		else {
			//bad article
			//remove this row (try this QAPATROL_ATTEMPTS times total)
			self::removeRow($row->qap_id, $row->qap_sqid);
			$nextQA = [
				'title' => self::QAPATROL_BAD_ARTICLE,
				'aid' => $row->qap_page_id
			];
		}

		return $nextQA;
	}

	private function getExpiryTime() {
		return wfTimestamp(TS_MW, time() - 3600); //expires in 1 hour
	}

	//get the count of Q&As left on which to vote
	public static function getRemaining($dbr = '', $expert_mode = false, $top_answerer_mode = false) {
		$dbr = $dbr ?: wfGetDB(DB_REPLICA);

		$tables = [ QADB::TABLE_QA_PATROL ];

		$where = [
			'qap_aqid IS NULL',
			'qap_copycheck' => 1
		];

		if ($expert_mode) {
			$where[] = 'qap_verifier_id > 0';
		}
		elseif ($top_answerer_mode) {
			$tables[] = TopAnswerers::TABLE_TOP_ANSWERERS;
			$where[] = 'qap_submitter_user_id = ta_user_id';
		}
		else {
			$tas = TopAnswerers::getAllTaUserIds();
			$where[] = 'qap_submitter_user_id NOT IN ('.implode(',',$tas).')';
		}

		$remaining = $dbr->selectField($tables, 'count(*)', $where, __METHOD__);
		return $remaining;
	}

	//returns array of result and if removed from/approved by tool
	private function vote($vote, $qap_id) {
		$dbw = wfGetDB(DB_MASTER);

		$vote = (int)$vote;
		$vote_str = $vote ? 'yes' : 'no';

		$qap_vote = array(
			'qap_vote_'.$vote_str.' = qap_vote_'.$vote_str.'+1',
			'qap_vote_total = qap_vote_total+1'
		);

		$res = $dbw->update(
			QADB::TABLE_QA_PATROL,
			$qap_vote,
			[
				'qap_id' => $qap_id,
				'qap_aqid IS NULL'
			],
			__METHOD__
		);
		$voted = array('voted' => $res);

		if ($res) {
			//vote table
			$vote_res = $dbw->insert('qap_vote',
			[
				'qapv_qapid' => $qap_id,
				'qapv_user_id' => $this->getUser()->getID(),
				'qapv_visitor_id' => WikihowUser::getVisitorId(),
				'qapv_vote' => $vote,
				'qapv_timestamp' => wfTimestampNow()
			], __METHOD__);

			$qa_res = $dbw->select(
				QADB::TABLE_QA_PATROL,
				['qap_page_id', 'qap_question', 'qap_answer'],
				['qap_id' => $qap_id],
				__METHOD__
			);
			$row = $dbw->fetchObject($qa_res);

			$title = Title::newFromId($row->qap_page_id);

			//log
			$logPage = new LogPage('qa_patrol', false);
			$logData = array($qap_id);
			$logAction = $vote ? 'vote_approve' : 'vote_delete';
			if ($title) {
				$logMsg = wfMessage('qap-logentry-vote', $title->getFullText(), $vote_str, $row->qap_question, $row->qap_answer)->text();
				$logS = $logPage->addEntry($logAction, $title, $logMsg, $logData);
			}

			//usage log
			UsageLogs::saveEvent(
				array(
					'event_type' => 'qa_patrol',
					'event_action' => $vote ? 'vote_up' : 'vote_down',
					'article_id' => $row->qap_page_id,
					'assoc_id' => $qap_id
				)
			);

			//mark if the person is a power voter
			$pv = $this->isPowerVoter();
			if ($pv) $voted['power_voter'] = 1;

			//check to see if it's reached the max votes
			$max = $vote ? self::QAPATROL_MAX_YES : self::QAPATROL_MAX_NO;
			$left = $dbw->selectField(
				QADB::TABLE_QA_PATROL,
				'qap_vote_'.$vote_str,
				['qap_id' => $qap_id],
				__METHOD__
			);

			if ($pv || (int)$left >= $max) {
				list($max_res, $result) = $this->voteMaxxed($vote, $qap_id);
				if (!empty($max_res)) $voted[$max_res] = 1;
			}

			//lastly, let's make sure they don't see this again
			$this->skip($qap_id);

			$this->releaseQA($qap_id);
		}

		return $voted;
	}

	//update q&a & reset votes
	private function saveEdit($qap_id, $question, $answer, $expertId, $removeSubmitter) {
		$edited = [];
		$dbw = wfGetDB(DB_MASTER);

		//come on now, people...
		if (QAUtil::hasBadWord($question.' '.$answer)) return false;

		$question = QAUtil::sanitizeSubmittedInput($question);
		$answer = QAUtil::sanitizeSubmittedInput($answer);

		$update = array(
			'qap_question' => $question,
			'qap_answer' => $answer,
			'qap_vote_yes' => 0,
			'qap_vote_no' => 0,
			'qap_user_id' => $this->getUser()->getId() ?: 0,
			'qap_visitor_id' => WikihowUser::getVisitorId()
		);

		if ($expertId != 0) {
			$update['qap_verifier_id'] = $expertId;
		}

		if (!$removeSubmitter) {
			//grab the old answer for comparison
			$res = $dbw->select(
				QADB::TABLE_QA_PATROL,
				[
					'qap_answer',
					'qap_submitter_user_id',
					'qap_sqid'
				],
				[
					'qap_id' => $qap_id,
					'qap_aqid IS NULL'
				],
				__METHOD__
			);
			$row = $dbw->fetchObject($res);

			$similarity_score = Misc::cosineSimilarity($row->qap_answer, $answer);
			$edited['similarity_score'] = $similarity_score;
			$removeSubmitter = $similarity_score < self::QAPATROL_SIMILARITY_THRESHOLD;

			if (!empty($row->qap_submitter_user_id)) {
				TopAnswerers::addNewSimilarityScore($row->qap_submitter_user_id, $row->qap_sqid, $similarity_score);

				//we're losing the submitter so let's mark this as approved (Alissa request)
				if ($removeSubmitter) {
					$approved = 1;
					TopAnswerers::addNewApprovalRating($row->qap_submitter_user_id,	$row->qap_sqid, $approved);
				}
			}
		}

		//if the difference between the old and new is too much, drop the submitter info
		if ($removeSubmitter) {
			$update['qap_submitter_user_id'] = 0;
			$update['qap_submitter_name'] = '';
		}

		$res = $dbw->update(
			QADB::TABLE_QA_PATROL,
			$update,
			[
				'qap_id' => $qap_id,
				'qap_aqid IS NULL'
			],
			__METHOD__
		);

		if ($res) {
			$this->qa_edited = true;

			$page_id = $dbw->selectField(
				QADB::TABLE_QA_PATROL,
				'qap_page_id',
				['qap_id' => $qap_id],
				__METHOD__
			);
			$title = Title::newFromId($page_id);

			//log
			$logPage = new LogPage('qa_patrol', false);
			$logData = array($qap_id);
			if ($title) {
				$logMsg = wfMessage('qap-logentry-edit', $title->getFullText(), $question, $answer)->text();
				$logS = $logPage->addEntry("edit", $title, $logMsg, $logData);
			}

			if ($this->canAutoApprove()) {
				list($max_res, $result) = $this->voteMaxxed(1, $qap_id);
				if (!empty($max_res)) $edited[$max_res] = 1;
				if (!empty($result)) $edited['result'] = $result;
			}
			else {
				//every non-special person just skips this
				$this->skip($qap_id);
			}

			$this->releaseQA($qap_id);
		}

		return $edited;
	}

	private function getVotesLeft($yes, $no) {
		$votes_left = '';

		//irrelevant data for power voters
		if ($this->isPowerVoter()) return '';

		if ($yes > 0 && $yes < self::QAPATROL_MAX_YES) {
			$yes_left = self::QAPATROL_MAX_YES - $yes;
			$votes_left .= '<p>'.wfMessage('qap_votesto_yes',$yes_left)->text().'</p>';
		}

		if ($no > 0 && $no < self::QAPATROL_MAX_NO) {
			$no_left = self::QAPATROL_MAX_NO - $no;
			$votes_left .= '<p>'.wfMessage('qap_votesto_no',$no_left)->text().'</p>';
		}

		return $votes_left;
	}

	private function voteMaxxed($vote, $qap_id) {
		$action = '';
		$dbw = wfGetDB(DB_MASTER);

		$qa_res = $dbw->select(
			QADB::TABLE_QA_PATROL,
			'*',
			['qap_id' => $qap_id],
			__METHOD__,
			['limit' => 1]
		);
		$row = $dbw->fetchObject($qa_res);
		$aid = $row->qap_page_id;

		$title = Title::newFromId($aid);
		// 20160322 Jordan - Debugging blank article questions appearing. Suspect multiple ajax requests occurring for
		// the same action causing the second requests query to return a null row
		if (!$row) {
			return [$action];
		}

		if ($vote) {
			//APPROVED!
			//finalize it and grab the aq_id
			$formData = [
				'aid' => $row->qap_page_id,
				'sqid'=> $row->qap_sqid,
				'question' => $row->qap_question,
				'answer' => $this->formatAnswer($row->qap_answer),
				'submitter_user_id' => $row->qap_submitter_user_id,
				'submitter_name' => $row->qap_submitter_name,
				'vid' => $row->qap_verifier_id,
				'priority' => -1
			];

			//does it need a copycheck? (if there's an expert attached & it's from the article, then yes)
			$doCopyCheck = $formData['vid'] && $this->qap_from_article ? true : false;

			$qadb = QADB::newInstance();
			$res = $qadb->insertArticleQuestion($formData, $doCopyCheck);

			$aq_id = $res->getAqid();
			if (empty($aq_id)) {
				//something went wrong
				self::removeRow($qap_id, $row->qap_sqid);
				return [''];
			} else {
				$aqs = $qadb->getArticleQuestionsByArticleQuestionIds([$aq_id], true);
				$aq = $aqs[0];
				$res->setAq($aq);
			}

			$dbw->update(QADB::TABLE_QA_PATROL, array('qap_aqid' => $aq_id), array('qap_id' => $qap_id), __METHOD__);

			$msg = 'qap-logentry-approved';
			$action = 'approved';
		}
		else {
			//DENIED!
			self::removeRow($qap_id, $row->qap_sqid);

			$msg = 'qap-logentry-denied';
			$action = 'deleted';
		}

		if (!empty($row->qap_submitter_user_id)) {
			$approved = $action == 'approved' ? 1 : 0;
			TopAnswerers::addNewApprovalRating($row->qap_submitter_user_id,	$row->qap_sqid, $approved);

			if ($approved && !$this->qa_edited) {
				$similarity_score = 1;
				TopAnswerers::addNewSimilarityScore($row->qap_submitter_user_id, $row->qap_sqid, $similarity_score);
			}
		}

		//log it
		$logPage = new LogPage('qa_patrol', false);
		$logData = array($qap_id);
		if ($title) {
			$logMsg = wfMessage($msg, $title->getFullText(), $row->qap_question, $row->qap_answer)->text();
			$logS = $logPage->addEntry($action, $title, $logMsg, $logData);
		}

		//usage log
		UsageLogs::saveEvent(
			array(
				'event_type' => 'qa_patrol',
				'event_action' => $action,
				'article_id' => $aid,
				'assoc_id' => $qap_id
			)
		);

		//we need to return $res for the article page use of QAPatrol
		return [$action, $res];
	}

	//remove this row & unpropose question
	public static function removeRow($qap_id, $sqid = null) {
		if (empty($qap_id)) return false;

		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(QADB::TABLE_QA_PATROL, ['qap_id' => $qap_id], __METHOD__);

		if (is_null($sqid)) {
			$sqid = $dbw->selectField(QADB::TABLE_QA_PATROL, 'qap_sqid', ['qap_id' => $qap_id], __METHOD__);
		}

		//unpropose from qa_submitted_questions
		$qadb = QADB::newInstance();
		$qadb->unProposeSubmittedQuestion($sqid);

		return;
	}

	private function skip($qap_id) {
		//add to skip tool
		if (empty($qap_id)) return;

		$this->skipTool->skipItem($qap_id);

		$dbr = wfGetDB(DB_REPLICA);
		$page_id = $dbr->selectField(
			QADB::TABLE_QA_PATROL,
			'qap_page_id',
			['qap_id' => $qap_id],
			__METHOD__
		);

		$this->releaseQA($qap_id);

		//usage log
		UsageLogs::saveEvent(
			array(
				'event_type' => 'qa_patrol',
				'event_action' => 'skip',
				'article_id' => $page_id,
				'assoc_id' => $qap_id
			)
		);
	}

	private function getUserHtml($data) {
		$html = '';

		if ($data['verifier_id'] && $this->qap_expert_mode) {
			$vd = VerifyData::getVerifierInfoById($data['verifier_id']);
			if (!empty($vd)) {
				$link = ArticleReviewers::getLinkToCoauthor($vd);
				$html .= wfMessage('qap_user_sub_exp', $vd->name, $link)->text();
			}
		}
		elseif ($this->qap_top_answerer_mode) {
			$html .= wfMessage('qap_user_sub_ta', $data['submitter_name'], $data['submitter_link'])->text();
		}
		elseif ($data['submitter_name'] && $data['submitter_link']) {
			$html .= wfMessage('qap_user_sub', $data['submitter_name'], $data['submitter_link'])->text();
		}
		elseif ($data['submitter_name']) {
			$html .= wfMessage('qap_user_sub_nolink', $data['submitter_name'])->text();
		}

		if ($data['last_patroller_name'] && $data['submitter_name'] != $data['last_patroller_name']) {
			$html .= ' '.wfMessage('qap_user_last', $data['last_patroller_name'], $data['last_patroller_link'])->text();
		}

		return $html;
	}

	//what you've just asked is one of the most insanely idiotic things I have ever heard.
	//At no point in your rambling, incoherent question were you even close to anything that
	//could be considered a rational thought. Everyone in this room is now dumber for having
	//listened to it. I award you no points, and may God have mercy on your soul.
	private function deleteQuestion($qap_id) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			QADB::TABLE_QA_PATROL,
			[
				'qap_sqid',
				'qap_page_id',
				'qap_question',
				'qap_submitter_user_id'
			],
			['qap_id' => $qap_id],
			__METHOD__
		);

		$row = $dbr->fetchObject($res);

		//first, set it to ignore in suggested questions
		if ($row->qap_sqid) {
			$qadb = QADB::newInstance();
			$qadb->ignoreSubmittedQuestion($row->qap_sqid); //walk away...
		}

		//second, remove it from Q&A Patrol
		self::removeRow($qap_id, $row->qap_sqid);

		//third, add that rating
		if (!empty($row->qap_submitter_user_id)) {
			$approved = 0;
			TopAnswerers::addNewApprovalRating($row->qap_submitter_user_id, $row->qap_sqid, $approved);
		}

		//log
		$title = Title::newFromId($row->qap_page_id);
		if ($title) {
			$logPage = new LogPage('qa_patrol', false);
			$logData = array($qap_id);
			$logMsg = wfMessage('qap-logentry-delete-question', $title->getFullText(), $row->qap_question)->text();
			$logS = $logPage->addEntry("deleted question", $title, $logMsg, $logData);
		}
	}

	public static function isAllowed($user) {
		$permittedGroups = [
			'staff',
			'answer_proofreader',
			'expert_answer_proofreader',
			'qa_editors'
		];

		return $user &&
					!$user->isBlocked() &&
					!$user->isAnon() &&
					count(array_intersect($permittedGroups, $user->getGroups())) > 0;
	}

	//power voters can approve/reject w/ a single button click
	//staff, admin, nabbers, answer patrollers
	private function isPowerVoter() {
		$userGroups = $this->getUser()->getGroups();
		$permittedGroups = ['staff', 'answer_proofreader', 'expert_answer_proofreader'];
		if (count(array_intersect($permittedGroups, $userGroups)) > 0) {
			return true;
		}
		return false;
	}

	private function canAutoApprove() {
		$permittedGroups = [
			'staff',
			'answer_proofreader',
			'expert_answer_proofreader',
			'qa_editors'
		];

		return $this->getUser() &&
			count(array_intersect($permittedGroups, $this->getUser()->getGroups())) > 0;
	}

	//perms for using experts=true (Expert Mode)
	private function canReviewExperts($userGroups) {
		$permittedGroups = ['staff', 'expert_answer_proofreader'];
		return count(array_intersect($permittedGroups, $userGroups)) > 0;
	}

	//perms for using ta=true (Top Answerers Mode)
	private function canReviewTopAnswerers($userGroups) {
		$permittedGroups = ['staff', 'expert_answer_proofreader'];
		return count(array_intersect($permittedGroups, $userGroups)) > 0;
	}

	private function tryCheckoutQA($qap_id) {
		$dbw = wfGetDB(DB_MASTER);
		$checkout = $dbw->selectField(
			QADB::TABLE_QA_PATROL,
			'qap_checkout_time',
			['qap_id' => $qap_id],
			__METHOD__
		);
		$expired = $this->getExpiryTime();
		if ($checkout === false || $checkout >= $expired) {
			print json_encode(["success" => false]);
			return;
		} else {
			$this->checkoutQA($qap_id);
			print json_encode(["success" => true]);
			return;
		}
	}

	private function checkoutQA($qap_id) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->update(
			QADB::TABLE_QA_PATROL,
			['qap_checkout_time' => wfTimestampNow()],
			['qap_id' => $qap_id],
			__METHOD__
		);
		return $res;
	}

	private function unCheckoutQA($qap_id) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->update(
			QADB::TABLE_QA_PATROL,
			['qap_checkout_time' => ""],
			['qap_id' => $qap_id],
			__METHOD__
		);
		return $res;
	}

	private function releaseQA($qap_id) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->update(
			QADB::TABLE_QA_PATROL,
			['qap_checkout_time' => ''],
			['qap_id' => $qap_id],
			__METHOD__
		);
		return $res;
	}

	// format internal links (and only internal links) in the answer
	public function formatAnswer($answer) {

		//only parse internal links
		$regex = '/\[\[.+?\]\]/';
		if ( preg_match_all($regex,$answer,$m) ) {

			foreach ($m[0] as $match) {
				$link = $this->getOutput()->parse($match); //format link
				$link = preg_replace('/<\/?p>/','',$link); //remove the <p> tags parsing added
				$link = trim($link, " \t\n\r\0\x0B"); //remove the line breaks & white space parsing added
				//$wikilink = str_replace('[','\[',$match); //make it regex-friendly
				//$wikilink = str_replace(']','\]',$wikilink); // ^^^
				//$wikilink = str_replace('|','\|',$wikilink); // ^^^
				$answer = preg_replace('/' . preg_quote($match) . '/', $link, $answer);
			}
		}

		return $answer;
	}

	private function exportSummaryCSV($from, $to, $user) {
		global $wgCanonicalServer;

		$filename = "patroller_stats_{$from}_{$to}.csv";

		// NOTE: must use disable() to be able to set these headers
		$this->getOutput()->disable();
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="'.$filename.'"');

		$qap_stats = new QAPatrolStats(
			$user,
			$this->qap_expert_mode,
			$this->qap_top_answerer_mode
		);
		$pat_stats = $qap_stats->recentPatrollerStatsForExport($from, $to);

		$headers = [
			'Patroller',
			'Q&As approved'
		];

		$lines[] = implode(",", $headers);

		foreach ($pat_stats as $pats) {

			$this_line = [
				$pats['patroller'],
				$pats['approved_count']
			];

			$lines[] = implode(",", $this_line);
		}

		print(implode("\n", $lines));
	}
}
