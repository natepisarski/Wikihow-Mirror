<?php

/*
CREATE TABLE `sort_questions_vote` (
	`sqv_qs_id` int(10) NOT NULL DEFAULT 0,
	`sqv_page_id` int(10) NOT NULL DEFAULT 0,
	`sqv_user_id` int(10) NOT NULL DEFAULT 0,
	`sqv_visitor_id` varbinary(20) NOT NULL DEFAULT '',
	`sqv_vote` tinyint(3) NOT NULL DEFAULT 0,
	`sqv_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	KEY (`sqv_user_id`),
	KEY (`sqv_visitor_id`),
	KEY (`sqv_page_id`),
	KEY (`sqv_timestamp`)
);

CREATE TABLE `sort_questions_queue` (
	`sqq_id` int(10) PRIMARY KEY AUTO_INCREMENT,
	`sqq_page_id` int(10) NOT NULL DEFAULT 0,
	UNIQUE KEY (`sqq_page_id`)
);
*/

class SortQuestions extends UnlistedSpecialPage {

	const SQ_MAX_VOTES = 2;
	const SQ_QUEUE_TABLE = 'sort_questions_queue'; //generated nightly
	const SQ_VOTE_TABLE = 'sort_questions_vote';
	const MIN_NUM_QUESTIONS = 3;
	const MAX_NUM_QUESTIONS = 5;
	const MAX_NUM_ARTICLES = 5;

	function __construct() {
		parent::__construct('SortQuestions');
		$this->out = $this->getContext()->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();
	}

	function execute($par) {
		$this->out->setRobotPolicy("noindex,follow");

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		if ($this->getLanguage()->getCode() != 'en' || !Misc::isMobileMode()) {
			$this->out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$this->skipTool = new ToolSkip("SortQuestionsTool");

		if ($this->request->getVal('getQs')) {
			$this->out->setArticleBodyOnly(true);

			//mark the last article as skipped
			$last_aid = $this->request->getVal('last_article');
			if (!empty($last_aid)) {
				$this->skipTool->skipItem($last_aid);

				//usage log
				UsageLogs::saveEvent(
					array(
						'event_type' => 'sort_questions_tool',
						'event_action' => 'next',
						'article_id' => $last_aid
					)
				);
			}

			//grab the next one
			$html = self::getQuestionHTML();

			print json_encode(array('html' => $html));
			return;
		}
		else if ($this->request->wasPosted() && XSSFilter::isValidRequest()) {
			$this->out->setArticleBodyOnly(true);
			self::saveVotes($this->request->getValues());
			return;
		}

		$this->out->setPageTitle(wfMessage('sort_questions')->text());
		$this->out->addModuleStyles('ext.wikihow.sort_questions.styles');
		$this->out->addModules('ext.wikihow.sort_questions');

		$html = $this->getMainHTML();
		$this->out->addHTML($html);
	}

	public function isMobileCapable() {
		return true;
	}

	private function getMainHTML() {
		$vars = [
			'get_next_msg' => wfMessage('sqt_get_next')->text(),
			'tool_info' => class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : ''
		];

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(dirname(__FILE__)),
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$html = $m->render('sort_questions', $vars);

		return $html;
	}

	/*
	 * get article ids from submitted questions
	 * (get SQ_GRAB_LIMIT, but we'll only need 1)
	 */
	private function getNextArticle($dbr) {
		$aids = [];

		//first, bubble up articles w/ questions that already have votes
		$aids = self::getNextArticleByVotedQuestions($dbr);

		// we want at least MAX_NUM_ARTICLES to cycle through
		// if we don't have enough,
		// grab ones w/ the fewest approved questions (from our nightly table)
		if (count($aids) < self::MAX_NUM_ARTICLES) {
			$aids2 = self::getNextArticleFromQueue($dbr);
			$aids = array_merge($aids, $aids2);
		}

		return $aids;
	}

	/*
	 * get article ids from submitted questions
	 * that already have votes on them
	 */
	private function getNextArticleByVotedQuestions($dbr) {
		$aids = [];

		$where = [
			'sqq_page_id = sqv_page_id'
		];

		//ignore skipped
		$skippedIds = $this->skipTool->getSkipped();
		if (is_array($skippedIds) && !empty($skippedIds)) {
			$where[] = "sqq_page_id not in (" . implode(',', $skippedIds) . ")";
		}

		$res = $dbr->select(
			[
				self::SQ_QUEUE_TABLE,
				self::SQ_VOTE_TABLE
			],
			'DISTINCT sqq_page_id',
			$where,
			__METHOD__,
			[
				'ORDER BY' => 'sqq_id',
				'LIMIT' => self::MAX_NUM_ARTICLES
			]
		);

		foreach($res as $row) {
			$aids[] = $row->sqq_page_id;
		}

		return $aids;
	}

	/*
	 * get article ids from submitted questions
	 * that have the fewest approved questions on the article
	 */
	private function getNextArticleFromQueue($dbr) {
		$aids = [];
		$where = [];

		//ignore skipped
		$skippedIds = $this->skipTool->getSkipped();
		if (is_array($skippedIds) && !empty($skippedIds)) {
			$where[] = "sqq_page_id not in (" . implode(',', $skippedIds) . ")";
		}

		$res = $dbr->select(
			[
				self::SQ_QUEUE_TABLE
			],
			'DISTINCT sqq_page_id',
			$where,
			__METHOD__,
			[
				'ORDER BY' => 'sqq_id',
				'LIMIT' => self::MAX_NUM_ARTICLES
			]
		);

		foreach($res as $row) {
			$aids[] = $row->sqq_page_id;
		}

		return $aids;
	}

	private function getQuestions($aids) {
		$sqs = [];
		$qs = [];
		$qadb = QADB::newInstance();
		$count = 0;

		foreach ($aids as $aid) {
			$sqs = $qadb->getSubmittedQuestions($aid, 0, self::MAX_NUM_QUESTIONS, false, false, false, true, true);

			//make sure it's real and has enough questions
			$t = Title::newFromId($aid);
			if ($t && $t->exists() && count($sqs) >= self::MIN_NUM_QUESTIONS) break; //got one

			//uh oh, let's remove that from our queue...
			$this->removeFromQueue($aid);

			if ($count > self::MAX_NUM_ARTICLES) break;
			$count++;
		}

		foreach($sqs as $sq) {
			$qs[] = [
				'question' => $sq->getText(),
				'question_id' => $sq->getId()
			];
		}

		return array($qs, $t);
	}

	private function getQuestionHTML() {
		$dbr = wfGetDb(DB_SLAVE);

		//grab the next article(s)
		$aids = $this->getNextArticle($dbr);
		if (empty($aids)) return '';

		//get the questions from that article
		list($qs, $t) = $this->getQuestions($aids);
		if (empty($qs)) return '';

		$article_msg = wfMessage('howto',$t->getText())->text();
		$article_title = Linker::link($t, $article_msg, ['target'=>'_blank']);

		$vars = [
			'questions' => $qs,
			'title' => wfMessage('sqt_txt',$article_title)->text(),
			'article_id' => $t->getArticleId()
		];

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(dirname(__FILE__)),
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$html = $m->render('sort_questions_inner', $vars);

		return $html;
	}

	private function saveVotes($votes) {
		$dbw = wfGetDB(DB_MASTER);

		foreach($votes['questions'] as $qv) {
			$vote = $qv['dir'] == 'up' ? 1 : 0;

			$dbw->insert(self::SQ_VOTE_TABLE,
				[
				'sqv_qs_id' => $qv['qs_id'],
				'sqv_page_id' => $qv['page_id'],
				'sqv_user_id' => $this->user->getID(),
				'sqv_visitor_id' => WikihowUser::getVisitorId(),
				'sqv_vote' => $vote
				], __METHOD__);

			//check total number of votes for this kind of vote
			$count = $dbw->selectField(self::SQ_VOTE_TABLE,
				'count(*)',
				[
				'sqv_qs_id' => $qv['qs_id'],
				'sqv_vote' => $vote
				],
				__METHOD__
			);

			// if we've reached the max or the user is a power voter...
			// (•_•)
			// ( •_•)>⌐■-■
			// (⌐■_■)
			// deal with it
			if ($count >= self::SQ_MAX_VOTES || $this->isPowerVoter()) {
				$this->dealWithIt($qv['qs_id'], $vote);
			}

			$title = Title::newFromId($qv['page_id']);
			$vote_str = $vote ? 'Yes' : 'No';

			//log
			$logPage = new LogPage('sort_questions_tool', false);
			$logData = array($qv['qs_id']);
			$logAction = $vote ? 'vote_approve' : 'vote_delete';
			$logMsg = wfMessage('sqt-logentry-vote', $title->getFullText(), $vote_str, $qv['question'])->text();
			$logS = $logPage->addEntry($logAction, $title, $logMsg, $logData);

			//usage log
			UsageLogs::saveEvent(
				array(
					'event_type' => 'sort_questions_tool',
					'event_action' => $vote ? 'vote_up' : 'vote_down',
					'article_id' => $qv['page_id'],
					'assoc_id' => $qv['qs_id']
				)
			);
		}

		return;
	}

	/**
	 *
	 *
	 *
	 *
	 * ⌐■-■
	*/
	private function dealWithIt($qs_id, $vote) {
		if (empty($qs_id)) return;

		$qadb = QADB::newInstance();

		if ($vote) {
			//YES!!!
			$qadb->markSubmittedQuestionSorted($qs_id);
		}
		else {
			//nope; ignore it
			$qadb->ignoreSubmittedQuestion($qs_id);
		}
	}

	//remove row from our queue table because
	//it doesn't have enough questions or it's bad*
	// *bad meaning bad; not bad meaning good
	private function removeFromQueue($aid) {
		$dbw = wfGetDb(DB_MASTER);
		$dbw->delete(
			self::SQ_QUEUE_TABLE,
			[ 'sqq_page_id' => $aid	],
			__METHOD__
		);
	}

	//power voters can approve/reject w/ a single button click
	//staff, admin, nabbers
	private function isPowerVoter() {
		//not so fast, anons...
		if ($this->user->isAnon()) return false;

		//check groups
		$userGroups = $this->user->getGroups();
		if (empty($userGroups) || !is_array($userGroups)) return false;
		return (in_array('staff', $userGroups) || in_array('admin', $userGroups) || in_array('newarticlepatrol', $userGroups));
	}
}
