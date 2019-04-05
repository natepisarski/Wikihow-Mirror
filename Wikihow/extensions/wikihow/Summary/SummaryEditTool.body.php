<?php

class SummaryEditTool extends UnlistedSpecialPage {

	var $page_title = '';
	static $default_summary_data = [
		'content' => '',
		'last_sentence' => '',
		'at_top' => false
	];

	public function __construct() {
		parent::__construct( 'SummaryEditTool');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();
		$action = $this->getRequest()->getVal('action','');

		$out->setRobotPolicy('noindex, nofollow');

		if (!self::authorizedUser($user) || empty($action)) {
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		global $wgMimeType;
		$wgMimeType = 'application/json';
		$out->setArticleBodyOnly(true);

		$this->page_title = $this->getRequest()->getVal('page_title','');
		if (empty($this->page_title)) return json_encode([]);

		if ($action == 'get_edit_box') {
			$summary_edit_tool_html = $this->summaryEditUI();
			print json_encode(['html' => $summary_edit_tool_html]);
		}
		elseif ($action == 'submit') {
			$result = $this->summarySubmit();
			print json_encode($result);
		}
	}

	public static function authorizedUser(User $user): bool {
		return !$user->isAnon() && Misc::isUserInGroups($user, self::authorizedGroups());
	}

	public static function authorizedGroups(): array {
		return [
			'sysop',
			'newarticlepatrol',
			'staff',
			'staff_widget',
			'editor_team'
		];
	}

	public static function editCTAforArticlePage() {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'text' => wfMessage('summary_edit_text')->text()
		];

		$html = $m->render('summary_edit_sidebox', $vars);
		return $html;
	}

	private function summaryEditUI() {
		$summary_data = $this->summaryDataFromTemplateOrWikitext();

		$vars = [
			'header' => wfMessage('set_header')->text(),
			'section_content_header' => wfMessage('set_content_header')->text(),
			'section_content' => $summary_data['content'],
			'last_sentence_header' => wfMessage('set_last_sentence_header')->text(),
			'last_sentence' => $summary_data['last_sentence'],
			// 'checked' => $summary_data['at_top'],
			// 'checkbox_label' => wfMessage('set_checkbox_label')->text(),
			'cancel_button' => wfMessage('cancel')->text(),
			'save_button' => wfMessage('save')->text(),
			'close_button' => wfMessage('set_close_button')->text(),
			'default_summary_header' => wfMessage('summary_section_default_header')->text(),
			'edit' => wfMessage('edit')->text()
		];

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$html = $m->render('summary_edit_tool', $vars);
		return $html;
	}

	private function summaryDataFromTemplateOrWikitext(): array {
		$summary_data = SummarySection::summaryData($this->page_title);

		if (empty($summary_data) || empty($summary_data['content'])) {
			$title = Title::newFromText($this->page_title);
			$rev = Revision::newFromTitle($title);
			if (!$rev) return [];
			$summary_data = self::oldSummaryData(ContentHandler::getContentText( $rev->getContent() ));
		}

		return $summary_data;
	}

	public static function oldSummaryData($wikitext): array {
		$old_section = Wikitext::getSummarizedSection($wikitext);
		if (empty($old_section)) return self::$default_summary_data;

		//ignore video for now
		$video_regex = '{{whvid.*?}}\n?';
		$old_summary = preg_replace('/'.$video_regex.'/s', '', $old_section);

		//remove [[Category:*]] lines from old section so we don't replace them
		//and make sure they're on their own line for parsing needs
		$category_regex = '\[\[Category:.*?\]\]';
		$old_section = preg_replace('/'.$category_regex.'/m', '', $old_section);
		$old_summary = preg_replace('/('.$category_regex.')/m', "\n$1", $old_summary);

		$summary_lines = [];
		$category_lines = [];
		$lines = explode( PHP_EOL, $old_summary );

		foreach ( $lines as $lineNum => $line ) {
			$header = $lineNum == 0;
			$category_line = strstr($line, '[[Category');
			$other_header = strstr($line, '==');

			if ($header)
				$summary_heading = trim(str_replace('==','',$line));
			elseif ($other_header)
				break;
			elseif ($category_line)
				$category_lines[] = $line;
			else
				$summary_lines[] = $line;
		}

		$at_top = self::oldSummaryAtTop($wikitext, $old_section);

		return [
			'header' => $summary_heading,
			'content' => implode( "\n", $summary_lines ),
			'last_sentence' => '',
			'at_top' => $at_top,
			'category_lines' => implode( "\n", $category_lines),
			'old_section' => $old_section
		];
	}

	public static function oldSummaryAtTop($wikitext, $old_summary): bool {
		$steps = Wikitext::getStepsSection($wikitext);
		$steps_position = !empty($steps) && !empty($steps[0]) ? strpos($wikitext, $steps[0]) : false;
		$summary_position = strpos($wikitext, $old_summary);

		if ($summary_position === false || $steps_position === false)
			$at_top = false; //default value
		else
			$at_top = $summary_position < $steps_position;

		return $at_top;
	}

	private function summarySubmit(): array {
		$success = false;
		$result_text = '';

		$request = $this->getRequest();

		$summary_content = trim(strip_tags($request->getVal('content'),'<br>'));
		$last_sentence = trim(strip_tags($request->getVal('last_sentence'),'<br>'));
		// $summary_position = $request->getInt('show_at_top') ? 'top' : 'bottom';
		$summary_position = 'bottom'; //always at the bottom (even though it's just TOC now)

		$summary = Title::newFromText($this->page_title, NS_SUMMARY);
		if ($summary) {
			$quicksummary_template = 	'{{'.SummarySection::QUICKSUMMARY_TEMPLATE_PREFIX.
																$summary_position.'|'.
																$last_sentence.'|'.
																$summary_content.'}}';

			$content = ContentHandler::makeContent($quicksummary_template, $summary);
			$comment = wfMessage('summary_edit_log')->text();

			$page = WikiPage::factory($summary);
			$status = $page->doEditContent($content, $comment);

			if ($status->isOK()) {
				$success = $this->addSummaryTemplateToWikiTextIfNeeded();

				$main_title = Title::newFromText($this->page_title, NS_MAIN);
				Hooks::run('QuickSummaryEditComplete', [ $summary, $main_title ] );

				if (empty( $status->value['revision'] ))
					$result_text = wfMessage('summary_edit_nochange')->text();
				else
					$result_text = wfMessage('summary_edit_success')->text();
			}
		}

		if (empty($result_text)) {
			$result_text = wfMessage('summary_edit_fail')->text();
		}

		return [
			'success' => $success,
			'text' => $result_text
		];
	}

	private function addSummaryTemplateToWikiTextIfNeeded(): bool {
		$result = false;

		$title = Title::newFromText($this->page_title);
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

		$old_summary_data = self::oldSummaryData($wikitext);
		$old_summary_exists = !empty($old_summary_data) && !empty($old_summary_data['old_section']);

		if ($old_summary_exists) {
			$new_summary_section = $this->prepareReplacementSummarySection($old_summary_data, $template, $wikitext);
		}
		else {
			$new_summary_section = $this->prepareNewSummarySection($template);
		}

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

	private function purgeIt($page, $title) {
		//purge the page to immediately show the new summary
		if (!empty($page)) $page->doPurge();

		//purge the title to force the api to grab the new summary (lag of 2-3m)
		if (!empty($title)) $title->purgeSquid();
	}

	private function prepareReplacementSummarySection($old_summary_data, $template, &$wikitext) {
		global $wgParser;
		$section = Wikitext::getSection($wikitext, $old_summary_data['header'], true);
		$wikitext = $wgParser->replaceSection($wikitext, $section[1], '');

		//did we remove [[Category:*]] lines from the intro?
		if (!empty($old_summary_data['category_lines']) && !strpos('[[Category:', $wikitext)) {
			//well, put them back!
			$intro = Wikitext::getIntro($wikitext);
			$intro .= "\n".$old_summary_data['category_lines'];
			$wikitext = Wikitext::replaceIntro($wikitext, $intro);
		}

		if ($old_summary_data['content'] == '') {
			$summary_section = $old_summary_data['old_section']."\n".$template;
		}
		else {
			$summary_section = str_replace($old_summary_data['content'], $template, $old_summary_data['old_section']);
		}
		return $summary_section;
	}

	private function prepareNewSummarySection($template) {
		$default_header = '== '.wfMessage('summary_section_default_header')->text().' ==';
		$summary_section = 	$default_header."\n".
												$template;

		return $summary_section;
	}
}
