<?php

class QA extends UnlistedSpecialPage {
	const ACTION_SUBMITTED_QUESTION = 'sq';
	const ACTION_FLAG_SUBMITTED_QUESTION = 'sq_flag';
	const ACTION_FLAG_ARTICLE_QUESTION = 'aq_flag';
	const ACTION_FLAG_DETAILS_ARTICLE_QUESTION = 'aq_flag_details';
	const ACTION_IGNORE_SUBMITTED_QUESTION = 'sq_ignore';
	const ACTION_ARTICLE_QUESTION = 'aq';
	const ACTION_GET_ARTICLE_QUESTIONS = 'gaqs';
	const ACTION_PROPOSED_ANSWER_SUBMISSION = 'pa';
	const ACTION_SUBMITTER_UPDATE = 'su';
	const ACTION_GET_SUBMITTED_QUESTIONS = 'get_submitted_questions';
	const ACTION_DELETE_ARTICLE_QUESTION = 'aq_delete';
	const ACTION_GET_VERIFIERS = 'get_verifiers';
	const ACTION_VOTE = 'vote';
	const ACTION_SIMILARITY_THRESHOLD = .6;
	const ACTION_GET_UNPATROLLED_QUESTIONS = 'gupqs';
	const ACTION_GET_TOP_ANSWERER_DATA = 'get_ta_data';
	const FROM_FIX_FLAGGED_ANSWERS_TOOL = 'FixFlaggedAnswers';
	var $isAdmin = false;
	var $isEditor = false;

	public function __construct() {
		global $wgHooks;
		parent::__construct('QA');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();
		if ($this->getLanguage()->getCode() != 'en') {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$this->isAdmin = QAWidget::isAdmin($user);
		$this->isEditor = QAWidget::isEditor($user);
		$this->handleRequest();
	}

	protected function handleRequest() {
		global $wgSquidMaxage;

		$request = $this->getRequest();
		$a = $request->getVal('a');
		$from = $request->getVal('from');

		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);

		// Return if the user is blocked
		if ($this->getUser()->isBlocked()) {
			Misc::jsonResponse(['userBlocked' => '1']);
			return;
		}

		// Return if a non-admin is requesting an admin action
		if (!$this->isAdmin && $this->isAdminAction($a,$from)) return;
		// Return if a non-editor is requesting an editor action
		if (!$this->isEditor && $this->isEditorAction($a,$from)) return;

		switch ($a) {
			case self::ACTION_FLAG_SUBMITTED_QUESTION:
				$this->handleFlagSubmittedQuestion();
				break;
			case self::ACTION_FLAG_ARTICLE_QUESTION:
				$this->handleFlagArticleQuestion();
				break;
			case self::ACTION_FLAG_DETAILS_ARTICLE_QUESTION:
				$this->handleFlagDetailsArticleQuestion();
				break;
			case self::ACTION_VOTE:
				$this->handleVote();
				break;
			case self::ACTION_PROPOSED_ANSWER_SUBMISSION:
				$this->handleProposedAnswerSubmission();
				break;
			case self::ACTION_SUBMITTER_UPDATE:
				$this->handleSubmitterUpdate();
				break;
			case self::ACTION_IGNORE_SUBMITTED_QUESTION:
				$this->handleIgnoreSubmittedQuestion();
				break;
			case self::ACTION_SUBMITTED_QUESTION:
				$this->handleSubmittedQuestion();
				break;
			case self::ACTION_ARTICLE_QUESTION:
				$this->handleArticleQuestion();
				break;
			case self::ACTION_GET_ARTICLE_QUESTIONS:
				$this->handleGetArticleQuestions();
				break;
			case self::ACTION_GET_SUBMITTED_QUESTIONS:
				$this->handleGetSubmittedQuestions();
				break;
			case self::ACTION_GET_VERIFIERS:
				$this->handleGetVerifiers();
				break;
			case self::ACTION_DELETE_ARTICLE_QUESTION:
				$this->handleDeleteArticleQuestion();
				break;
			case self::ACTION_GET_UNPATROLLED_QUESTIONS:
				$this->handleGetUnpatrolledQuestions();
				break;
			case self::ACTION_GET_TOP_ANSWERER_DATA:
				$out->setSquidMaxage($wgSquidMaxage); //make sure this caches
				$this->handleGetTopAnswererData();
				break;
		}
	}

	protected function isAdminAction($a,$from) {
		$adminActions = [
			self::ACTION_GET_VERIFIERS
		];

		if ($from != self::FROM_FIX_FLAGGED_ANSWERS_TOOL) {
			$adminActions[] = self::ACTION_DELETE_ARTICLE_QUESTION;
		}

		return in_array($a, $adminActions);
	}

	protected function isEditorAction($a,$from) {
		$editorActions = [
			self::ACTION_ARTICLE_QUESTION,
			self::ACTION_IGNORE_SUBMITTED_QUESTION
		];

		if ($from == self::FROM_FIX_FLAGGED_ANSWERS_TOOL) {
			$editorActions[] = self::ACTION_DELETE_ARTICLE_QUESTION;
		}

		return in_array($a, $editorActions);
	}

	protected function handleDeleteArticleQuestion() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();
		$formData = [
			'aid' => $request->getVal('aid'),
			'aqid' => $request->getVal('aqid'),
			'sqid'=> $request->getVal('sqid'),
			'cqid' => $request->getVal('cqid'),
			'caid' => $request->getVal('caid'),
		];

		$answerOnly = filter_var($request->getVal('answer_only'),FILTER_VALIDATE_BOOLEAN);

		$this->getOutput()->addHTML( json_encode($qadb->deleteArticleQuestion($formData, $answerOnly)) );
	}

	protected function handleIgnoreSubmittedQuestion() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();
		$sqid = $request->getVal('sqid');
		$qadb->ignoreSubmittedQuestion($sqid);
	}

	protected function handleFlagSubmittedQuestion() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();

		$sqid = $request->getVal('sqid');
		$sq = $qadb->getSubmittedQuestion($sqid);
		if ($sq) {
			$u = $this->getUser();
			// Admins and boosters votes count as 2 votes
			$isPrivileged = in_array('newarticlepatrol', $u->getRights()) || in_array( 'sysop', $u->getGroups());
			$voteCount = $isPrivileged ? 2 : 1;

			$flagCount = $sq->getFlagged() + $voteCount;
			$markIgnored = false;
			if ($flagCount  >= SubmittedQuestion::MAX_FLAGGED_COUNT) {
				$markIgnored = true;
			}
			$qadb->flagSubmittedQuestion($sqid, $flagCount, $markIgnored);
		}
	}

	protected function handleFlagArticleQuestion() {
		$request = $this->getRequest();

		$aq_id = $request->getVal('aq_id');
		$reason = $request->getVal('reason');
		$expert = filter_var($request->getVal('expert'),FILTER_VALIDATE_BOOLEAN);

		$res = FlaggedAnswers::addFFA($aq_id, $reason, $expert);
		echo json_encode($res);
	}

	protected function handleFlagDetailsArticleQuestion() {
		$request = $this->getRequest();

		$qfa_id = $request->getVal('qfa_id');
		$details = $request->getVal('details');

		FlaggedAnswers::addDetails($qfa_id, $details);
	}

	protected function handleGetSubmittedQuestions() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();
		$aid = $request->getVal('aid');
		$lastSubmittedId = $request->getVal('last_sqid', 0);
		// Anons only see approved questions
		$sqs = $qadb->getSubmittedQuestions($aid, $lastSubmittedId, 5, false, false, $this->getUser()->isAnon(), false, true);

		echo json_encode($sqs);
	}

	protected function handleGetArticleQuestions() {
		$request = $this->getRequest();
		$aid = $request->getVal('aid');
		$offset = $request->getVal('offset', 0);
		$limit = $request->getVal('limit', 0);

		$aqs = QAWidget::getArticleQuestions($aid, $this->isAdmin, $limit, $offset);

		echo json_encode($aqs);
	}

	protected function handleGetUnpatrolledQuestions() {
		$request = $this->getRequest();
		$aid = $request->getVal('aid');
		$offset = $request->getVal('offset', 0);
		$limit = $request->getVal('limit', 0);

		$upqs = QAWidget::getUnpatrolledQuestions($aid, $limit, $offset);

		echo json_encode($upqs);
	}

	protected function handleSubmittedQuestion() {
		$add_question = true;
		$request = $this->getRequest();

		$question = $request->getVal('q', '');
		// Fail silently if there are bad words in the submission
		if (QAUtil::hasBadWord($question)) {
			$add_question = false;
		}

		$aid = $request->getInt('aid', 0);
		$email = $request->getVal('email', '');

		// Fail silently if this email is on our blacklist
		if (SubmittedQuestion::isBlacklistedQuestionSubmitterEmail($email)) {
			$add_question = false;
		}

		if ($add_question) {
			$qadb = QADB::newInstance();
			$qadb->addSubmittedQuestion($aid, $question, $email);
		}

		//add related suggestions
		$t = Title::newFromId($aid);
		if ($t) {
			$user = RequestContext::getMain()->getUser();
			$title_context = RequestContext::newExtraneousContext($t);
			$relateds = new RelatedWikihows($title_context, $user, '');
			$res = $relateds->getRelatedHtml();
			$res = !empty($res) ? '<br /><br />'.wfMessage('qa_submitted_related').$res : '';
		}

		$res = wfMessage('qa_submitted').$res;
		echo $res;
	}

	protected function handleArticleQuestion() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();

		$formData = [
			'aid' => $request->getInt('aid'),
			'aqid' => $request->getInt('aqid'),
			'sqid'=> $request->getInt('sqid'),
			'cqid' => $request->getInt('cqid'),
			'caid' => $request->getInt('caid'),
			'question' => $request->getVal('question'),
			'answer' => $request->getVal('answer'),
			'inactive' => $request->getInt('inactive'),
			'vid' => $request->getInt('vid')
		];

		$isNew = empty($formData['aqid']);
		if ($isNew) {
			$user = RequestContext::getMain()->getUser();
			if ($user && ($user->hasGroup('editor_team') || $user->hasGroup('staff'))) {
				$formData['submitter_user_id'] = $user->getId();
			}
		}

		//get the OG article question so we can run some checks
		$includeInactive = true;
		$aq_og = $qadb->getArticleQuestionByArticleQuestionId($request->getInt('aqid'), $includeInactive);

		//get the similarity score of the answer to the previous answer
		$different_answer = false;
		if ($aq_og && $aq_og->getCuratedQuestion() && $aq_og->getCuratedQuestion()->getCuratedAnswer()) {
			$orig_answer = $aq_og->getCuratedQuestion()->getCuratedAnswer()->getText();

			if ($orig_answer) {
				$similarity_score = Misc::cosineSimilarity($orig_answer, $request->getVal('answer'));
				$different_answer = $similarity_score < self::ACTION_SIMILARITY_THRESHOLD;
			}
		}

		//if it's not too similar, reset the helpfulness
		if ($different_answer) {
			$formData['votes_up'] = 0;
			$formData['votes_down'] = 0;
			$formData['score'] = 0;
		}

		//are we removing the submitter?
		//1) someone checked the "remove submitter id" checkbox?
		//2) passed the similarity check
		if ($request->getInt('remove_submitter') == 1 || $different_answer) {
			$formData['submitter_user_id'] = 0;
			$formData['submitter_name'] = '';
		}

		//are going to run a copy check? Not if we just flipped from inactive to active
		$doCopyCheck = $aq_og && $aq_og->getInactive() && !$formData['inactive'] ? false : true;

		$result = $qadb->insertArticleQuestion($formData, $doCopyCheck);

		if (!empty($result->getAqid())) {
			$aqs = $qadb->getArticleQuestionsByArticleQuestionIds([$result->getAqid()], true);
			$aq = $aqs[0];
			$result->setAq($aq);
		}

		echo json_encode($result);
	}

	protected function handleProposedAnswerSubmission() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();

		// No bad words in the question please!
		$question = QAUtil::sanitizeSubmittedInput($request->getVal('question'));
		if (QAUtil::hasBadWord($question)) {
			return;
		}

		// No bad words in the answer please!
		$answer = QAUtil::sanitizeSubmittedInput($request->getVal('answer'));
		if (QAUtil::hasBadWord($answer)) {
			return;
		}

		$formData = [
			'aid' => $request->getInt('aid'),
			'sqid'=> $request->getInt('sqid'),
			'answer' => $answer,
			'question' => $question,
			'email' => $request->getVal('email'),
			'submitter_user_id' => $request->getVal('submitter_user_id', false) ? $this->getUser()->getId() : 0,
			'submitter_name' => $request->getVal('submitter_name', ''),
			'verifier_id' => $request->getVal('verifier_id', 0)
		];

		$qadb->insertProposedAnswerSubmission($formData);

		// Reset the article cache so that new questions can be loaded for anons
		$t = Title::newFromId($formData['aid']);
		if ($t && $t->exists()) {
			$t->purgeSquid();
		}

		Misc::jsonResponse(['isAnon' => $this->getUser()->isAnon()]);
	}

	protected function handleSubmitterUpdate() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();

		$sqids = $request->getArray('sqids');
		$userId = $this->getUser()->getId();

		$qadb->updateProposedAnswersSubmitter($sqids, $userId);
	}

	protected function handleVote() {
		$qadb = QADB::newInstance();
		$request = $this->getRequest();

		$aqid = $request->getInt('aqid');
		$type = $request->getVal('type');

		$qadb->vote($aqid, $type);
	}

	protected function handleGetVerifiers() {
		$verifiers = [['id' => 0, 'name' => '--None--']];

		$verifierData = VerifyData::getAllVerifierInfo();
		foreach ($verifierData as $datum) {
			$verifiers []= ['id' => $datum->verifierId, 'name' => $datum->name];
		}

		$buildSorter = function($key) {
			return function ($a, $b) use ($key) {
				return strnatcmp($a[$key], $b[$key]);
			};
		};
		usort($verifiers, $buildSorter('name'));

		echo json_encode($verifiers);
	}

	/**
	 * handleGetTopAnswererData()
	 * - grabs all the data for the detailed Top Answerer data on hover/click
	 */
	protected function handleGetTopAnswererData() {
		$request = $this->getRequest();
		$user_id = $request->getInt('user_id');
		$article_id = $request->getInt('aid');

		$ta = new TopAnswerers();
		$ta->cat_limit = 3; //only show 3 categories
		$ta->setCurrentCat($article_id); //ignore this category when listing
		$ta->loadByUserId($user_id);

		echo json_encode($ta->toJSON());
	}

	public function isMobileCapable() {
		return true;
	}
}
