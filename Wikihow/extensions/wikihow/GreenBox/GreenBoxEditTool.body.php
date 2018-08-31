<?php

class GreenBoxEditTool extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'GreenBoxEditTool');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();
		$request = $this->getRequest();
		$action = $request->getVal('action','');

		$out->setRobotpolicy('noindex, nofollow');

		if (!self::authorizedUser($user) || empty($action)) {
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		global $wgMimeType;
		$wgMimeType = 'application/json';
		$out->setArticleBodyOnly(true);

		if ($action == 'get_edit_box') {
			$green_box_edit_tool_html = $this->greenBoxEditUI();
			print json_encode(['html' => $green_box_edit_tool_html]);
		}
		elseif ($action == 'get_green_box_content') {
			$green_box_content = $this->greenBoxContent($request);
			print json_encode(['green_box_content' => $green_box_content]);
		}
		elseif ($action == 'save') {
			$result = $this->greenBoxSave($request);
			print json_encode($result);
		}
	}

	public static function authorizedUser(User $user): bool {
		return !$user->isAnon() && Misc::isUserInGroups($user, self::authorizedGroups());
	}

	public static function authorizedGroups(): array {
		return [
			'green_box_editors',
			'staff',
			'staff_widget',
			'editor_team'
		];
	}

	private function greenBoxEditUI(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'header' => wfMessage('green_box_edit_header')->text(),
			'delete_button' => wfMessage('delete')->text(),
			'cancel_button' => wfMessage('cancel')->text(),
			'save_button' => wfMessage('save')->text()
		];

		$html = $m->render('green_box_edit', $vars);
		return $html;
	}

	private function greenBoxContent(WebRequest $request) {
		$page_id = $request->getInt('page_id', 0);
		$step_info = $request->getText('step_info', '');

		$title = Title::newFromId($page_id);
		if (empty($title)) return 'bad title';

		$revision = Revision::newFromTitle($title);
		if (empty($revision)) return 'bad revision';

		$wikitext = ContentHandler::getContentText($revision->getContent());
		list($steps_section, $sectionID) = Wikitext::getStepsSection($wikitext, true);
		if (empty($steps_section)) return 'could not find steps';

		$current_step = $this->getStep($steps_section, $step_info);
		preg_match('/{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX.'(.*)}}/is', $current_step, $m);
		$green_box_content = !empty($m[1]) ? $m[1] : '';

		return $green_box_content;
	}

	private function greenBoxSave(WebRequest $request): array {
		$page_id = $request->getInt('page_id', 0);
		$step_info = $request->getText('step_info', '');
		$box_content = $this->formatBoxContent($request->getText('content', ''));
		$success = false;

		$bad_stuff = $this->findInvalidContent($box_content);
		if (!empty($bad_stuff)) return ['error' => 'Invalid content found: '.$bad_stuff];

		$title = Title::newFromId($page_id);
		if (empty($title)) return ['error' => 'bad title'];

		$revision = Revision::newFromTitle($title);
		if (empty($revision)) return ['error' => 'bad revision'];

		$wikitext = ContentHandler::getContentText($revision->getContent());
		list($steps_section, $sectionID) = Wikitext::getStepsSection($wikitext, true);
		if (empty($steps_section)) return ['error' => 'could not find steps'];

		$original_step = $this->getStep($steps_section, $step_info);
		if (empty($original_step)) return ['error' => 'could not find step'];

		$updated_step = $this->insertGreenBoxIntoStep($original_step, $box_content);
		$green_box_changed = strcmp($original_step, $updated_step) !== 0;

		if ($green_box_changed) {
			$updated_wikitext = str_replace($original_step, $updated_step, $wikitext);
			$success = $this->updatePage($title, $updated_wikitext);
		}
		else {
			$success = true; //nothing updated, but didn't fail so...yay?
		}

		$html = $this->newGreenBoxHtml($box_content);

		return [
			'success' => $success,
			'html' => $html
		];
	}

	private function getStep(string $steps_section, string $step_info): string {
		$step = '';

		list($method_num, $step_num) = $this->parseStepInfo($step_info);
		if (empty($method_num) || empty($step_num)) return '';

		if (Wikitext::countAltMethods($steps_section) > 0) {
			$altMethods = $this->splitAltMethods($steps_section);
			$stepsText = $altMethods[$method_num - 1];
		}
		else {
			$stepsText = $steps_section;
		}

		$includeSubsteps = false;
		$steps = Wikitext::splitSteps($stepsText, $includeSubsteps);

		if (!empty($steps) && !empty($steps[$step_num])) {
			$step = $steps[$step_num];
		}

		return $step;
	}

	private function splitAltMethods(string $steps_section): array {
		$altMethods = preg_split('/^=/m', $steps_section, $limit = -1, PREG_SPLIT_NO_EMPTY);

		//header-only sections are not sections; remove 'em
		foreach ($altMethods as $key => $value) {
			if (empty(preg_replace('/^=+\s*.*?\s*=+\s*[\n|$]/', '', $altMethods[$key]))) {
				array_splice($altMethods, $key, 1);
			}
		}

		return $altMethods;
	}

	private function parseStepInfo(string $step_info): array {
		preg_match('/step_(\d+)_(\d+)/', $step_info, $matches);

		$method_num = !empty($matches[1]) ? $matches[1] : 0;
		$step_num = !empty($matches[2]) ? $matches[2] : 0;

		return [ $method_num, $step_num ];
	}

	private function insertGreenBoxIntoStep(string $step, string $green_box_content): string {
		$step = trim($step);

		//remove any existing green boxes
		$step = preg_replace('/{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX.'.*}}/is', '', $step);

		//add our updated one (if there is one)
		if (!empty($green_box_content)) {
			$step .= '{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX.$green_box_content.'}}';
		}

		return $step."\n";
	}

	private function formatBoxContent(string $box_content): string {
		//no actual line breaks
		$box_content = preg_replace('/\n/m', '<br>', $box_content);

		return $box_content;
	}

	private function findInvalidContent(string $box_content): string {
		$bad_stuff = '';

		if ($this->hasSpamBlacklistLinks($box_content)) {
			$bad_stuff = wfMessage('green_box_error_spam')->text();
		}

		return $bad_stuff;
	}

	private function hasSpamBlacklistLinks(string $box_content): bool {
		preg_match_all('/(https?:\/\/.+)(?:\s|\]|\n|$)/iU', $box_content, $m);
		$links = !empty($m[1]) ? $m[1] : [];

		$spamObj = BaseBlacklist::getInstance( 'spam' );
		$spamMatches = $spamObj->filter($links);
		return !empty($spamMatches);
	}

	private function newGreenBoxHtml(string $box_content): string {
		if (empty($box_content)) return '';

		$box_content .= $this->contentNotices($box_content);

		$html = wfMessage('green_box_container', $box_content)->parse();

		//suppress any parsing errors that arise
		$html = preg_replace('/<p><br\s?\/?><strong class="error">.*<\/p>/is', '', $html);

		return $html;
	}

	private function contentNotices(string $html): string {
		$notices = [];

		if (preg_match('/<ref>/', $html)) {
			$notices[] = wfMessage('green_box_notice_ref')->text();
		}
		if (preg_match('/<math>/', $html)) {
			$notices[] = wfMessage('green_box_notice_math')->text();
		}

		if (!empty($notices))
			$notices_html = Html::rawElement('div', ['id' => 'green_box_notice_box'], implode("<br>", $notices));
		else
			$notices_html = '';

		return $notices_html;
	}

	private function updatePage(Title $title, string $wikitext): bool {
		$content = ContentHandler::makeContent($wikitext, $title);
		$comment = wfMessage('green_box_log_message')->text();
		$edit_flags = EDIT_UPDATE | EDIT_MINOR;

		$page = WikiPage::factory($title);
		$status = $page->doEditContent($content, $comment, $edit_flags);

		return $status->isOK();
	}
}