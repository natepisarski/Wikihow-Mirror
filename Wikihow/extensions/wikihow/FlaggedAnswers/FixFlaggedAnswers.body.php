<?php

class FixFlaggedAnswers extends UnlistedSpecialPage {

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'FixFlaggedAnswers');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		global $wgHooks;
		$wgHooks['ShowBreadCrumbs'][] = array($this, 'hideBreadcrumb');

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		if ($user && $user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		//logged in and desktop only
		if (!$user || $user->isAnon() || Misc::isMobileMode() || !self::approvedUser($user)) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$this->skipTool = new ToolSkip("FixFlaggedAnswers");

		$action = $request->getVal('action');
		if ($action) {
			$out->setArticleBodyOnly(true);

			if ($action == 'next') {
				if ($request->getVal('skip') > 0) $this->skip($request->getVal('skip'));

				$next = $this->nextFlaggedAnswer();
				print json_encode($next);
			}
			elseif ($action == 'remove_flag') {
				FlaggedAnswers::deactivateFlaggedAnswerById($request->getVal('qfa_id'));
			}
			elseif ($action == 'log') {
				$this->log($request->getValues());
			}

			return;
		}

		$out->addModules([
			'ext.wikihow.fix_flagged_answers',
			'ext.wikihow.fix_flagged_answers.styles'
		]);
		$out->setHTMLTitle(wfMessage('ffa_title')->text());
		$out->addHTML($this->toolHTML());
	}

	public static function approvedUser(User $user) {
		return QAWidget::isEditor($user);
	}

	private function toolHTML() {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates'),
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = $m->render('fix_flagged_answers.mustache', $this->toolVars($loader));
		return $html;
	}

	private function toolVars($loader) {
		$msgKeys = [
			'ffa_title',
			'ffa_prompt',
			'ffa_skip',
			'ffa_edit',
			'ffa_cancel',
			'ffa_save',
			'ffa_delete_pair',
			'ffa_delete_answer',
			'ffa_remaining',
			'ffa_perfect_text',
			'ffa_perfect_link',
			'ffa_eoq_msg'
		];
		$vars = $this->getMWMessageVars($msgKeys);
		$vars['tool_info'] = class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : '';
		$vars['fix_flagged_answers_qa_section'] = $loader->load('fix_flagged_answers_qa_section');
		$vars['ffa_staff'] = $this->getUser()->hasGroup('staff');

		return $vars;
	}

	private function nextFlaggedAnswer() {
		$next = [];

		$where = [
			'qfa_active' => 1,
			'qfa_expert' => 0
		];

		$skippedIds = $this->skipTool->getSkipped();
		if (is_array($skippedIds) && !empty($skippedIds)) {
			$where[] = "qfa_id NOT IN (" . implode(',', $skippedIds) . ")";
		}

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			FlaggedAnswers::TABLE,
			'*',
			$where,
			__METHOD__,
			[
				'ORDER BY' => 'qfa_created_at DESC',
				'LIMIT' => 1
			]
		);
		$row = $res->fetchObject();

		//object -> array
		$next = $row ? json_decode(json_encode($row), true) : [];

		if (!empty($next['qfa_aq_id'])) {
			$qadb = QADB::newInstance();
			$aq = $qadb->getArticleQuestionByArticleQuestionId($next['qfa_aq_id']);
			$aq = json_decode(json_encode($aq), true);
			$next = array_merge($next, $aq);

			$title = Title::newFromId($next['articleId']);
			if ($title && $title->exists()) {
				$next['article_title'] = wfMessage('howto', $title->getText())->text();
				$next['article_link'] = $title->getLocalUrl();
			}

			$page = WikiPage::newFromId($next['articleId']);
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
					$next['article_html'] = utf8_encode($widget->getWidgetHTML()) .
																	'<h2>'.wfMessage('qap_article_hdr')->text().'</h2>' .
																	$article_html;
				}
			}

			$submitter = $next['submitterDisplayName'];
			if (empty($submitter) && !empty($next['submitterUserId'])) {
				$u = User::newFromId($next['submitterUserId']);
				$submitter = $u ? $u->getName() : '';
			}
			$next['submitter_name'] = $submitter ?: wfMessage('ffa_anon')->text();

			$next['remaining'] = FlaggedAnswers::remaining();
		}

		$msgKeys = [
			'ffa_answer_by_label',
			'ffa_q_label',
			'ffa_a_label',
			'ffa_reason_label',
			'ffa_details_label',
			'ffa_q_cl',
			'ffa_a_cl',
			'ffa_a_url',
		];
		$next = array_merge($next, $this->getMWMessageVars($msgKeys));

		return $next;
	}

	private function skip($qfa_id) {
		if (empty($qfa_id)) return;
		$this->skipTool->skipItem($qfa_id);
	}

	private function log($data) {
		$title = Title::newFromId($data['aid']);
		if (!$title) return;

		$msg = 'ffa_logentry_'.$data['event'];
		$logMsg = wfMessage($msg, $title->getFullText(), $data['question'], $data['answer'], $data['reason'])->text();

		$logPage = new LogPage('fix_flagged_answers', false);
		$logPage->addEntry($data['event'], $title, $logMsg);
	}

	protected function getMWMessageVars($keys) {
		$vars = [];
		foreach ($keys as $key) {
			$vars[$key] = wfMessage($key)->text();
		}
		return $vars;
	}

	public static function hideBreadcrumb(&$breadcrumb) {
		$breadcrumb = false;
		return true;
	}
}
