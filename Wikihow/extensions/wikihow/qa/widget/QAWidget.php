<?php

class QAWidget {
	const QA_CATEGORY_BLACKLIST_KEY = 'qa_category_blacklist';
	const QA_WIDGET_TEMPLATE = 'qa_widget';
	const QA_WIDGET_TEMPLATE_PRINTABLE = 'qa_widget_simple';
	const MINIMUM_CONTRIBS = 50;
	const KEY_BLACKLISTED_AIDS = 'qa_blacklisted_article_ids';
	const KEY_SEARCH_AIDS = 'qa_search_enabled_articles';
	const LIMIT_MOBILE_ANSWERED_QUESTIONS = 10;
	const LIMIT_DESKTOP_ANSWERED_QUESTIONS = 10;
	const QA_EDITOR_USER_GROUP = 'qa_editors';
	const QA_ASKED_QUESTION_MAXLENGTH = 200;
	const LIMIT_UPATROLLED_QUESTIONS = 5;
	const FRESH_QA_PAGE_TAG = 'fresh_q&a_pages';
	const LIMIT_SUBMITTED_QUESTIONS = 5;

	var $t = null;

	function __construct($t = null) {
		$this->t = !is_null($t) ? $t : RequestContext::getMain()->getTitle();
	}

	//for embedding in the article page
	public function addWidget() {
		$html = $this->getWidgetHTML();

		if (pq('.steps:last')->length) {
			pq('.steps:last')->after($html);
		}
	}

	public function getWidgetHTML() {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__),
			new Mustache_Loader_FilesystemLoader(__DIR__ . "/../../ext-utils/thumbs_up_down"),
			new Mustache_Loader_FilesystemLoader(__DIR__ . "/../../TopAnswerers/templates")
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$html = $m->render($this->getTemplateName(), $this->getVars($loader));

		return $html;
	}

	protected function getTemplateName() {
		$out = RequestContext::getMain()->getOutput();

		return ($out && $out->isPrintable())
			? self::QA_WIDGET_TEMPLATE_PRINTABLE
			: self::QA_WIDGET_TEMPLATE;
	}

	protected function getOutput() {
		return RequestContext::getMain()->getOutput();
	}

	protected function getVars($loader) {
		$isMobile = Misc::isMobileMode();
		$u = $this->getUser();

		$vars['aid'] = $aid = $this->t->getArticleID();
		$vars['qa_admin'] = $isAdmin = self::isAdmin($u);
		$vars['qa_editor'] = $isEditor = self::isEditor($u);
		$vars['qa_staff'] = $isStaff = self::isStaff($u);
		$vars['qa_anon'] = $u->isAnon();
		$vars['qa_show_unanswered_questions'] = $showUnansweredQuestions =
			self::isUnansweredQuestionsTarget() || $isAdmin || $isEditor;
		$vars['qa_show_unpatrolled_questions'] = $showUnpatrolledQuestions = self::showUnpatrolledQuestions($u);

		$vars['qa_few_contribs'] = $u->getEditCount() < self::MINIMUM_CONTRIBS ? 1 : 0;
		$vars['qa_target_page'] = self::isTargetPage();
		$vars['qa_article_question_item'] = $loader->load('qa_article_question_item');
		$vars['qa_question_edit_form'] = $loader->load('qa_question_edit_form');

		if ($isMobile) {
			$vars['thumbs_up_down'] = $loader->load('thumbs_up_down');
			$vars['qa_done_edit_answered'] = wfMessage('qa_done_edit_answered_mobile')->text();
			$vars['qa_asked_question_placeholder'] = wfMessage('qa_asked_question_placeholder')->text();
			$vars['qa_default_prompt'] = '';
			$vars['qa_desktop'] = false;
			$vars['qa_answered_by'] = wfMessage('qa_answered_by')->text();
			$vars['top_answerers_qa_widget_mobile'] = $loader->load('top_answerers_qa_widget_mobile');
		} else {
			$vars['thumbs_qa_widget'] = $loader->load('thumbs_qa_widget');
			$vars['qa_done_edit_answered'] = wfMessage('qa_done_edit_answered_desktop')->text();
			$vars['qa_asked_question_placeholder'] = wfMessage('qa_asked_question_placeholder_d')->text();
			$vars['qa_default_prompt'] = wfMessage('qa_default_prompt')->text();
			$vars['qa_desktop'] = true;
			$vars['qa_answered_by'] = '';
			$vars['flag_options'] = $this->getFlagOptions();
			$vars['qa_expert_hover'] = $loader->load('qa_expert_hover');
			$vars['top_answerers_qa_widget_desktop'] = $loader->load('top_answerers_qa_widget_desktop');
			if (!$u->isAnon()) {
				$vars['answer_flag_options'] = $this->getAnswerFlagOptions();
			}
		}

		if ($showUnansweredQuestions) {
			$vars['qa_answer_confirmation'] = $loader->load('qa_answer_confirmation');
			$vars['qa_social_login_form'] = $loader->load('qa_social_login_form');
			$vars['qa_social_login_confirmation'] = $loader->load('qa_social_login_confirmation');

			if ($isMobile) {
				$vars['qa_curate'] = wfMessage('qa_curate_mobile')->text();
				$vars['qa_ignore'] = wfMessage('qa_ignore_mobile')->text();
				$vars['qa_flag'] = wfMessage('qa_flag_mobile')->text();
				$vars['mobile_anon_user'] = $u->isAnon();
			} else {
				$vars['qa_curate'] = wfMessage('qa_curate')->text();
				$vars['qa_ignore'] = wfMessage('qa_ignore')->text();
				$vars['qa_flag'] = wfMessage('qa_flag')->text();
				$vars['mobile_anon_user'] = false;
			}

			$msgKeys = [
				'qa_section_submitted',
				'qa_add_curated'
			];
			$vars = array_merge($vars, $this->getMWMessageVars($msgKeys));
		}

		$msgKeys = [
			'qa_section_curated',
			'qa_section_ask',
			'qa_thumbs_text',
			'qa_thumbs_yes',
			'qa_thumbs_no',
			'qa_thumbs_help',
			'qa_thumbs_nohelp',
			'qa_reviewed_by',
			'thumbs_default_prompt',
			'thumbs_response',
			'qa_section_title',
			'qa_email_placeholder',
			'qa_title',
			'qa_submit_button',
			'qa_submitted',
			'no_qa',
			'qa_generic_username',
			'qa_flag_duplicate',
			'qa_edit',
			'qa_edit_answered',
			'qa_email_prompt',
			'qa_question_label'
		];
		$vars = array_merge($vars, $this->getMWMessageVars($msgKeys));

		$vars['qa_asked_question_maxlength'] = self::QA_ASKED_QUESTION_MAXLENGTH;
		$vars['qa_asked_count'] = wfMessage('qa_asked_count', self::QA_ASKED_QUESTION_MAXLENGTH)->text();
		$vars['qa_submitted_question_item'] = $loader->load('qa_submitted_question_item');
		$vars['qa_fresh'] = $fresh_qa = ArticleTagList::hasTag(self::FRESH_QA_PAGE_TAG, $aid);

		$limit = $isMobile ? self::LIMIT_MOBILE_ANSWERED_QUESTIONS : self::LIMIT_DESKTOP_ANSWERED_QUESTIONS;
		$offset = $fresh_qa ? $limit : 0;
		$articleQuestions = self::getArticleQuestions($aid, $isEditor, $limit, $offset);
		WikihowToc::setQandA( $articleQuestions );
		if (count($articleQuestions) >= $limit) {
			$vars['qa_show_more_answered'] = wfMessage('qa_show_more_answered')->text();
		}
		if (class_exists("QADomain")) {
			$qa_whalink = QADomain::getRandomQADomainLinkFromWikihow($this->t->getArticleID());
			if ($qa_whalink !== false) {
				$vars['qa_whalink'] = $qa_whalink;
				$vars['qa_see_more_answered'] = wfMessage('qa_see_more_answered')->text();
			}
		}
		$vars['article_questions'] = $articleQuestions;
		$vars['qa_has_visible_answers'] = self::hasVisibleAnswers($isEditor, $articleQuestions);

		$vars['submitted_questions'] = $this->getSubmittedQuestions($aid, self::LIMIT_SUBMITTED_QUESTIONS);
		if (count($vars['submitted_questions']) == self::LIMIT_SUBMITTED_QUESTIONS) {
			$vars['qa_show_more_submitted'] = wfMessage('qa_show_more_submitted')->text();
		}
		$vars['search_enabled'] = $this->isSearchTarget() ? 1 : 0;

		if ($showUnpatrolledQuestions) {
			$msgKeys = [
				'qa_section_unpatrolled'
			];
			$vars = array_merge($vars, $this->getMWMessageVars($msgKeys));

			$vars['unpatrolled_questions'] = $this->getUnpatrolledQuestions($aid, self::LIMIT_UPATROLLED_QUESTIONS);
			$vars['qa_patrol_item'] = $loader->load('qa_patrol_item');
			if (count($vars['unpatrolled_questions']) == self::LIMIT_UPATROLLED_QUESTIONS) {
				$vars['qa_show_more_unpatrolled'] = wfMessage('qa_show_more_unpatrolled')->text();
			}
			$vars['qa_has_unpatrolled'] = (count($vars['unpatrolled_questions']) > 0);
			$vars['qa_unpatrolled_edit_form'] = $loader->load('qa_unpatrolled_edit_form');
		}

		return $vars;
	}

	protected function getMWMessageVars($keys) {
		$vars = [];
		foreach ($keys as $key) {
			$vars[$key] = wfMessage($key)->text();
		}
		return $vars;
	}

	protected function getUser() {
		return RequestContext::getMain()->getUser();
	}

	protected function isSearchTarget() {
		$t = RequestContext::getMain()->getTitle();
		$aid = $t->getArticleID();
		return ArticleTagList::hasTag(self::KEY_SEARCH_AIDS, $aid);
	}

	/**
	 * Determines whether a title can display the 'Unanswered Questions' section in the widget. Certain titles
	 * are blacklisted from displaying questions due to their sensitivity.  Other titles may not be indexed and we
	 * don't want to collect proposed answers
	 * @param null $t
	 * @return bool
	 */
	public static function isUnansweredQuestionsTarget($t = null) {
		global $wgLanguageCode;
		$showUnansweredQuestions = false;

		if (is_null($t)) {
			$t = RequestContext::getMain()->getTitle();
		}

		// No editing capabilities
		if (self::isQAPatrol() || self::isFixFlaggedAnswerTool()) {
			return false;
		}

		// Certain titles are blacklisted.  These titles we don't allow editing for non-admins
		if (self::isBlacklistedTitle($t)) {
			return false;
		}

		// As long as a title isn't in a blacklisted category, allow editing on the title
		if (RobotPolicy::isIndexable($t)
			&& $wgLanguageCode == 'en') {

			$blacklist = self::getCategoryBlacklist();
			if (!CategoryFilter::newInstance()->isTitleFiltered($t, $blacklist)) {
				$showUnansweredQuestions = true;
			}
		}

		return $showUnansweredQuestions;
	}

	//determine whether or not this is a valid user
	//(used in isAdmin() and isEditor() checks)
	private static function hasBasicUserRights(User $user) {
		return RequestContext::getMain()->getLanguage()->getCode() == 'en'
			&& !$user->isBlocked()
			&& !self::isQAPatrol(); //No admin capabilities for this tool
	}

	public static function isStaff(User $user) {
		if (!self::hasBasicUserRights($user)) return false;
		return $user->hasGroup('staff');
	}

	public static function isAdmin(User $user) {
		if (!self::hasBasicUserRights($user)) return false;
		return ($user->hasGroup('staff') || $user->hasGroup('staff_widget') || $user->hasGroup('editor_team'));
	}

	public static function isEditor(User $user) {
		if (!self::hasBasicUserRights($user)) return false;

		//all admins are editors
		if (self::isAdmin($user)) return true;

		$userGroups = $user->getGroups();
		return in_array(self::QA_EDITOR_USER_GROUP, $userGroups);
	}

	public static function showUnpatrolledQuestions(User $user) {
		if (!self::hasBasicUserRights($user)) return false;
		return $user->hasGroup('staff') && !Misc::isMobileMode();
	}

	public static function isBlacklistedTitle($t) {
		global $wgLanguageCode;

		$isBlacklisted = false;
		if ($wgLanguageCode == 'en'
			&& $t
			&& $t->exists()
			&& $t->inNamespace(NS_MAIN)) {

			$aid = $t->getArticleID();
			if ( ArticleTagList::hasTag(self::KEY_BLACKLISTED_AIDS, $aid) ) {
				$isBlacklisted = true;
			}
		}
		return $isBlacklisted;
	}

	public static function getCategoryBlacklist() {
		$blacklist = ConfigStorage::dbGetConfig(self::QA_CATEGORY_BLACKLIST_KEY);
		$blacklist = explode("\n", trim($blacklist));

		return $blacklist;
	}

	/**
	 * @param $aid
	 * @param $isEditor
	 * @param $limit
	 * @param int $offset
	 * @return ArticleQuestion[]
	 */
	public static function getArticleQuestions($aid, $isEditor, $limit, $offset = 0) {
		global $wgMemc;

		if (self::isQAPatrol() || self::isFixFlaggedAnswerTool()) {
			$limit = 0;
		}

		$key = QAWidgetCache::getArticleQuestionsPagingCacheKey($aid, $isEditor, $limit, $offset);
		$aqs = $wgMemc->get($key);

		// Always retrieve the latest for admins
		if (empty($aqs) || $isEditor) {
			$qadb = QADB::newInstance();
			// Anons only see active questions
			$includeInactive = $isEditor;
			$aqs = $qadb->getArticleQuestions([$aid], $includeInactive, $limit, $offset);
			$aqs = QAWidget::formatArticleQuestionsForArticlePage($aqs);
			$wgMemc->set($key, $aqs);
		}

		return $aqs;
	}

	public static function getUnpatrolledQuestions($aid, $limit = 0, $offset = 0) {
		$qadb = QADB::newInstance();
		$aqs = $qadb->getUnpatrolledQuestions($aid, $limit, $offset);
		$aqs = QAWidget::formatArticleQuestionsForArticlePage($aqs);
		return $aqs;
	}

	protected function getSubmittedQuestions($aid, $limit = 0) {
		$qadb = QADB::newInstance();
		$u = RequestContext::getMain()->getUser();
		// Anons only see approved questions
		return $qadb->getSubmittedQuestions($aid, 0, $limit, false, false, $u->isAnon(), false, true);
	}

	protected function getFlagOptions() {
		$flag_options = [
			[
				'name' => 'answered',
				'value' => wfMessage('qa_fo_answered')->text()
			],
			[
				'name' => 'not_question',
				'value' => wfMessage('qa_fo_not_question')->text()
			],
			[
				'name' => 'bad_question',
				'value' => wfMessage('qa_fo_bad_question')->text()
			],
			[
				'name' => 'other',
				'value' => wfMessage('qa_fo_other')->text()
			]
		];

		return $flag_options;
	}

	protected function getAnswerFlagOptions() {
		$flag_options = [
			[
				'name' => 'copy',
				'value' => wfMessage('qa_afo_copy')->text()
			],
			[
				'name' => 'incorrect',
				'value' => wfMessage('qa_afo_incorrect')->text()
			],
			[
				'name' => 'inappropriate',
				'value' => wfMessage('qa_afo_inappropriate')->text()
			],
			[
				'name' => 'wrong',
				'value' => wfMessage('qa_afo_wrong_topic')->text()
			],
			[
				'name' => 'duplicate',
				'value' => wfMessage('qa_afo_duplicate')->text()
			],
			[
				'name' => 'other',
				'value' => wfMessage('qa_afo_other')->text()
			]
		];

		return $flag_options;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::isTargetPage()) {
			if (Misc::isMobileMode()) {
				$moduleName = 'mobile.wikihow.qa_widget';
			}  else {
				$moduleName = 'ext.wikihow.qa_widget';
				$qa_toc = Misc::getEmbedFile('css', __DIR__ . '/qa_widget_desktop_top.less');
				$out->addHeadItem('qa_toc', HTML::inlineStyle($qa_toc));
			}

			$out->addModules($moduleName);
		}

		return true;
	}

	public static function onAddDesktopTOCItems($wgTitle, &$anchorList) {
		if (!Misc::isMobileMode() && QAWidget::isTargetPage()) {
			$pos = count($anchorList);
			$tocText = wfMessage('qa_toc_section')->text();
			array_splice($anchorList, $pos, 0, "<a id='qa_toc' href='#Questions_and_Answers_sub'>$tocText</a>");
		}
		return true;
	}

	public static function isQAPatrol() {
		$t = RequestContext::getMain()->getTitle();
		return $t && $t->getText() == 'QAPatrol';
	}

	public static function isFixFlaggedAnswerTool() {
		$t = RequestContext::getMain()->getTitle();
		return $t && $t->getText() == 'FixFlaggedAnswers';
	}

	public static function onAddMobileTOCItemData($wgTitle, &$extraTOCPreData, &$extraTOCPostData) {
		if (QAWidget::isTargetPage()) {
			$extraTOCPostData[] = [
				'anchor' => 'qa_headline',
				'name' => wfMessage('qa_toc_section')->text(),
				'priority' => 1000,
				'selector' => '.section.qa',
			];
		}
		return true;
	}

	public static function isTargetPage($t = null) {
		$isTarget = false;

		$request = RequestContext::getMain()->getRequest();
		$lang = RequestContext::getMain()->getLanguage()->getCode();
		$action =$request->getVal('action','view');
		if (count($request->getVal('diff')) > 0) $action = 'diff';

		if (is_null($t)) {
			$t = RequestContext::getMain()->getTitle();
		}

		if ($lang == 'en'
			&& $t
			&& $t->exists()
			&& $t->inNamespace(NS_MAIN)
			&& $t->getText() != wfMessage('mainpage')->inContentLanguage()->text()
			&& ($action == 'view' || $action == 'purge')) {
			$isTarget = true;
		}

		return $isTarget;
	}

	/**
	 * @param $aqs
	 */
	public static function formatArticleQuestionsForArticlePage($aqs) {
		$user = RequestContext::getMain()->getUser();
		$isEditor = self::isEditor($user);
		$isAdmin = self::isAdmin($user);

		$user_ids = [];
		foreach ($aqs as $q) {
			$user_ids[] = $q->getSubmitterUserId();
		}

		$dc = new UserDisplayCache($user_ids);
		$display_data = $dc->getData();

		foreach ($aqs as $q) {
			if ( isset( $display_data[$q->getSubmitterUserId()] ) ) {
				$q->setProfileDisplayData($display_data[$q->getSubmitterUserId()]);
			}
			$q->show_editor_tools = self::showEditorTools($q, $isEditor, $isAdmin);
			$q->qa_answerer_class = self::getAnswererClass($q);
			$q->qa_answerer_label = self::getAnswererLabel($q);
			if ( $q->verifierData ) {
				$q->verifierData->articleReviewersUrl = ArticleReviewers::getLinkToCoauthor($q->verifierData);
			}
		}

		return $aqs;
	}

	private static function hasVisibleAnswers($isEditor, $aqs) {
		$has_visible = false;

		if ($isEditor) {
			//for admins and qa_editors,
			//let's cycle through and see if there's at least 1 active
			foreach ($aqs as $q) {
				if ($q->getInactive() == 0) {
					$has_visible = true;
					break;
				}
			}
		}
		else {
			//for the rest, it's easy. just count
			$has_visible = count($aqs) > 0;
		}

		return $has_visible;
	}

	private static function showEditorTools($article_question, $isEditor, $isAdmin): bool {
		$show = false;

		if ($isAdmin) {
			$show = true;
		}
		elseif ($isEditor && empty($article_question->verifierId)) {
			$show = true;
		}

		return $show;
	}

	private static function getAnswererClass($article_question): string {
		if (!empty($article_question->verifierId))
			$class = 'qa_expert_area';
		elseif (!empty($article_question->isTopAnswerer))
			$class = 'qa_ta_area';
		else
			$class = 'qa_user_area';

		return $class;
	}

	private static function getAnswererLabel($article_question): string {
		if (!empty($article_question->verifierId))
			$label = wfMessage('qa_expert_answer')->text();
		elseif (!empty($article_question->isTopAnswerer))
			$label = wfMessage('ta_label')->text();
		elseif ($article_question->getSubmitterDisplayName() == wfMessage('qa_staff_editor')->text())
			$label = wfMessage('qa_staff_label')->text();
		else
			$label = wfMessage('qa_user_label_default')->text();

		return $label;
	}
}
