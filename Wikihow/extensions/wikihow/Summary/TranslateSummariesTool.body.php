<?php

class TranslateSummariesTool extends UnlistedSpecialPage {
	private $specialPage;
	private $secondAttempt = false;

	private static $authorizedGroups = [
		'staff',
		'staff_widget',
		'translator',
		'sysop'
	];

	public function __construct() {
		$this->specialPage = 'TranslateSummaries';
		parent::__construct($this->specialPage);
	}

	public function execute($par) {
		$out = $this->getOutput();

		if (!self::allowedUser()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$this->skipTool = new ToolSkip("TranslateSummaries");

		$action = $this->getRequest()->getText('action', '');

		if ($action != '') {
			global $wgMimeType;
			$wgMimeType = 'application/json';
			$out->setArticleBodyOnly(true);

			if ($action == 'get_next_summary')
				$result = $this->getNextSummary();
			elseif ($action == 'save')
				$result = $this->saveSummary();
			elseif ($action == 'skip')
				$result = $this->skipSummary();
			else
				$result = [];

			print json_encode($result);
			return;
		}

		$out->setPageTitle(wfMessage('translate_summaries_tool')->text());
		$out->addHTML($this->getToolHtml());
		$out->addModules(['ext.wikihow.translate_summaries']);
	}

	public static function allowedUser(): bool {
		$user = RequestContext::getMain()->getUser();
		return !Misc::isMobileMode() &&
			$user &&
			!$user->isAnon() &&
			Misc::isUserInGroups($user, self::$authorizedGroups);
	}

	private function renderTemplate(string $template, array $vars): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);
		return $m->render($template, $vars);
	}

	private function getToolHtml(): string {
		$user_name = wfMessage('translate_summaries_tool_username', $this->getUser()->getName())->inLanguage('en')->text();
		return $this->renderTemplate('translate_summaries_tool', [ 'user_name' => $user_name ]);
	}

	private function getNextSummary(): array {
		$ts = new TranslateSummaries();

		$forced_intl_page_id = $this->getRequest()->getInt('forced_page_id', 0);
		if ($forced_intl_page_id) {
			$ts->loadFromIntlArticleId($forced_intl_page_id);
			if (!$ts->exists() || $ts->translated) return $this->eoq();
		}
		else {
			$skippedIds = $this->skipTool->getSkipped();
			if (empty($skippedIds)) $skippedIds = [];
			$ts->getNextToTranslate($skippedIds);
		}

		if (!$ts->exists()) {
			if ($this->secondAttempt) {
				return $this->eoq();
			}
			else {
				//clear skips and try again
				$this->skipTool->clearSkipCache();
				$this->secondAttempt = true;
				return $this->getNextSummary();
			}
		}

		$summary_data = TranslateSummaries::getENSummaryData($ts->page_id_en);

		$page_title_en = str_replace('-', ' ', $ts->page_title_en);
		$page_title_intl = str_replace('-', ' ', $ts->page_title_intl);
		$intl_domain = wfCanonicalDomain($ts->language_code);

		$vars = [
			'skip' => wfMessage('skip')->inLanguage('en')->text(),
			'title_en_text' => wfMessage('howto', $page_title_en)->inLanguage('en')->text(),
			'article_en_link' => 'https://www.wikihow.com/'.$ts->page_title_en,
			'title_intl_text' => wfMessage('howto', $page_title_intl)->text(),
			'article_intl_link' => 'https://'.$intl_domain.'/'.$ts->page_title_intl,
			'summary_label' => wfMessage('translate_summaries_tool_summary_label')->inLanguage('en')->text(),
			'sentence_label' => wfMessage('translate_summaries_tool_sentence_label')->inLanguage('en')->text(),
			'to_language' => Language::fetchLanguageName( $ts->language_code, 'en' ),
			'summary_content' => $summary_data['content'],
			'summary_last_sentence' => $summary_data['last_sentence'],
			'error' => wfMessage('translate_summaries_tool_error')->inLanguage('en')->text(),
			'publish' => wfMessage('publish')->inLanguage('en')->text(),
			'placeholder' => wfMessage('translate_summaries_tool_placeholder')->inLanguage('en')->text()
		];

		return [
			'html' => $this->renderTemplate('translate_summaries_tool_item', $vars),
			'page_id_intl' => $ts->page_id_intl
		];
	}

	private function saveSummary(): array {
		$aid = $this->getRequest()->getInt('page_id_intl', 0);
		$summary_content = trim(strip_tags($this->getRequest()->getText('content', ''),'<br>'));
		$last_sentence = trim(strip_tags($this->getRequest()->getText('last_sentence', ''),'<br>'));

		if (!$aid || $summary_content == '') return [];

		$ts = new TranslateSummaries();
		$ts->loadFromIntlArticleId($aid);
		if (!$ts->exists() || $ts->translated) return [];

		$summarized = $this->makeSummary($ts->page_title_intl, $summary_content, $last_sentence);
		if (!$summarized) return [];

		$ts->translated = 1;
		if (!$ts->save()) return [];

		$res = TranslateSummariesAdmin::logSummarySave($ts);

		return ['html' => $this->successResult($ts->language_code, $ts->page_title_intl)];
	}

	private function makeSummary(string $page_title, string $summary_content, string $last_sentence): bool {
		$summary = Title::newFromText($page_title, NS_SUMMARY);
		if (!$summary) return false;

		$summary_position = 'bottom';
		$quicksummary_template = 	'{{'.SummarySection::QUICKSUMMARY_TEMPLATE_PREFIX.
															$summary_position.'|'.
															$last_sentence.'|'.
															$summary_content.'}}';

		$content = ContentHandler::makeContent($quicksummary_template, $summary);
		$comment = wfMessage('summary_edit_log')->text();

		$page = WikiPage::factory($summary);
		$status = $page->doEditContent($content, $comment);

		if ($status->isOK()) {
			return $this->addSummaryTemplateToWikiTextIfNeeded($page_title);
		}
		else {
			return false;
		}
	}

	private function addSummaryTemplateToWikiTextIfNeeded(string $page_title): bool {
		$result = false;

		$title = Title::newFromText($page_title);
		if (!$title || !$title->exists()) return false;

		$rev = Revision::newFromTitle($title);
		if (!$rev) return false;

		$wikitext = ContentHandler::getContentText( $rev->getContent() );

		$namespace = MWNamespace::getCanonicalName(NS_SUMMARY);
		$title_regex = '('.preg_quote($title->getText()).'|'.preg_quote($title->getDBKey()).')';

		$summary_template_exists = preg_match('/{{'.$namespace.':'.$title_regex.'}}/i', $wikitext);
		if ($summary_template_exists) {
			$page = WikiPage::factory($title);
			$this->purgeIt($page, $title);
			return true;
		}

		$template = '{{'.$namespace.':'.$title->getDBKey().'}}';
		$new_summary_section = $this->prepareNewSummarySection($template);

		$inline_comment = wfMessage('summary_section_notice')->text();

		$wikitext .= 	"\n\n".
									$inline_comment."\n".
									$new_summary_section;

		$content = ContentHandler::makeContent($wikitext, $title);
		$comment = wfMessage('summary_add_log')->text();
		$edit_flags = EDIT_UPDATE | EDIT_MINOR;

		$page = WikiPage::factory($title);
		$status = $page->doEditContent($content, $comment, $edit_flags);

		if ($status->isOK()) {
			$this->purgeIt($page, $title);
			$result = true;
		}

		return $result;
	}

	private function prepareNewSummarySection($template) {
		$default_header = '== '.wfMessage('summary_section_default_header')->text().' ==';
		return $default_header."\n".$template;
	}

	private function successResult(string $language_code, string $page_title_intl): string {
		$intl_domain = wfCanonicalDomain($language_code);
		$intl_summary_url = 'https://'.$intl_domain.'/Summary:'.$page_title_intl;

		$vars = [
			'result' => wfMessage('translate_summaries_tool_success')->inLanguage('en')->parse(),
			'description' => wfMessage('translate_summaries_tool_success_description')->inLanguage('en')->text(),
			'summary_url' => $intl_summary_url,
			'continue' => wfMessage('continue')->inLanguage('en')->text()
		];

		return $this->renderTemplate('translate_summaries_tool_result', $vars);
	}

	private function eoq(): array {
		$vars = [
			'result' => wfMessage('translate_summaries_tool_eoq')->inLanguage('en')->parse(),
			'description' => wfMessage('translate_summaries_tool_eoq_description')->inLanguage('en')->text()
		];

		return [
			'html' => $this->renderTemplate('translate_summaries_tool_eoq', $vars),
			'page_id_intl' => ''
		];
	}

	private function skipSummary(): array {
		$aid = $this->getRequest()->getInt('page_id_intl');
		if (empty($aid)) return [];

		$this->skipTool->skipItem($aid);

		return ['result' => TranslateSummaries::removeHoldOnSummary($aid)];
	}

	private function purgeIt($page, $title) {
		//purge the page to immediately show the new summary
		if (!empty($page)) $page->doPurge();

		//purge the title to force the api to grab the new summary (lag of 2-3m)
		if (!empty($title)) $title->purgeSquid();
	}
}
