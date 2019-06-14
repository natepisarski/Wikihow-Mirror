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

		$out->setRobotPolicy('noindex, nofollow');

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
			$green_box_data = $this->greenBoxContent($request);
			print json_encode($green_box_data);
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
			'editor_team',
			'translator'
		];
	}

	private function greenBoxEditUI(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'default_type_class' => GreenBox::$green_box_types[0].'_edit_type',
			'header' => wfMessage('green_box_edit_header')->text(),
			'green_box_types' => $this->greenBoxTypes(),
			'question_label' => wfMessage('green_box_question_label')->text(),
			'answer_label' => wfMessage('green_box_answer_label')->text(),
			'delete_button' => wfMessage('delete')->text(),
			'cancel_button' => wfMessage('cancel')->text(),
			'save_button' => wfMessage('save')->text(),
			'expert_label' => wfMessage('green_box_edit_expert_label')->text(),
			'experts' => $this->expertList()
		];

		$html = $m->render('green_box_edit', $vars);
		return $html;
	}

	private function greenBoxTypes(): array {
		$green_box_types = [];

		foreach (GreenBox::$green_box_types as $type) {
			$green_box_types[] = [
				'type' => $type,
				'name' => wfMessage($type.'_type')->text()
			];
		}

		return $green_box_types;
	}

	private function expertList(): array {
		$experts = [
			['id' => 0, 'name' => wfMessage('green_box_expert_default')->text()],
			['id' => GreenBox::GREENBOX_EXPERT_STAFF, 'name' => '-- '.wfMessage('sp_staff_reviewed')->text().' --']
		];

		$verifierData = VerifyData::getAllVerifierInfo();
		foreach ($verifierData as $datum) {
			$experts[]= ['id' => $datum->verifierId, 'name' => $datum->name];
		}

		$buildSorter = function($key) {
			return function ($a, $b) use ($key) {
				return strnatcmp($a[$key], $b[$key]);
			};
		};
		usort($experts, $buildSorter('name'));

		return $experts;
	}

	private function greenBoxContent(WebRequest $request): array {
		$expert_id = ''; $content = ''; $content_2 = '';

		$page_id = $request->getInt('page_id', 0);
		$step_info = $request->getText('step_info', '');

		$title = Title::newFromId($page_id);
		if (empty($title)) return ['error' => 'bad title'];

		$revision = Revision::newFromTitle($title);
		if (empty($revision)) return ['error' => 'bad revision'];

		$wikitext = ContentHandler::getContentText($revision->getContent());
		list($steps_section, $sectionID) = Wikitext::getStepsSection($wikitext, true);
		if (empty($steps_section)) return ['error' => 'could not find steps'];

		$current_step = $this->getStep($steps_section, $step_info);

		if (strpos($current_step, '{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX)) {
			//classic greenbox
			preg_match('/{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX.'(.*?)}}/is', $current_step, $m);
			$content = !empty($m[1]) ? $m[1] : '';
		}
		elseif (strpos($current_step, '{{'.GreenBox::GREENBOX_EXPERT_TEMPLATE_PREFIX)) {
			//expert greenbox (quote or Q&A)
			preg_match('/{{'.GreenBox::GREENBOX_EXPERT_TEMPLATE_PREFIX.'(.*?)\|(.*?)(?:\|(.*?)|)}}/is', $current_step, $m);
			$expert_id = !empty($m[1]) ? $m[1] : '';
			$content = !empty($m[2]) ? $m[2] : '';
			$content_2 = !empty($m[3]) ? $m[3] : '';
		}

		return [
			'green_box_expert' => $expert_id,
			'green_box_content' => $content,
			'green_box_content_2' => $content_2
		];
	}

	private function greenBoxSave(WebRequest $request): array {
		$page_id = $request->getInt('page_id', 0);
		$step_info = $request->getText('step_info', '');
		$box_content = $this->formatBoxContent($request->getText('content', ''));
		$box_content_2 = $this->formatBoxContent($request->getText('content_2', ''));
		$expert_id = $request->getText('expert', '');
		$success = false;

		//expert id can only be an int or "staff"
		if ($expert_id != GreenBox::GREENBOX_EXPERT_STAFF) $expert_id = intval($expert_id);

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

		$updated_step = $this->insertGreenBoxIntoStep($original_step, $box_content, $box_content_2, $expert_id);
		$green_box_changed = strcmp($original_step, $updated_step) !== 0;

		if ($green_box_changed) {
			$updated_wikitext = str_replace($original_step, $updated_step, $wikitext);
			$success = $this->updatePage($title, $updated_wikitext);
		}
		else {
			$success = true; //nothing updated, but didn't fail so...yay?
		}

		$html = $this->newGreenBoxHtml($box_content, $box_content_2, $expert_id);

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
		$sample_prefix = MWNamespace::getCanonicalName(NS_DOCUMENT);

		$altMethods = preg_split('/^=/m', $steps_section, $limit = -1, PREG_SPLIT_NO_EMPTY);

		//Ignore sections if they're:
		//1) header-only sections
		//2) Samples sections
		foreach ($altMethods as $key => $value) {
			if (
				empty(preg_replace('/^=+\s*.*?\s*=+\s*[\n|$]/', '', $value)) ||
				preg_match('/\[\['.$sample_prefix.':/', $value)
			) {
				unset($altMethods[$key]);
			}
		}

		return array_values($altMethods);
	}

	private function parseStepInfo(string $step_info): array {
		preg_match('/step_(\d+)_(\d+)/', $step_info, $matches);

		$method_num = !empty($matches[1]) ? $matches[1] : 0;
		$step_num = !empty($matches[2]) ? $matches[2] : 0;

		return [ $method_num, $step_num ];
	}

	private function insertGreenBoxIntoStep(string $step, string $box_content, string $box_content_2 = '',
		$expert_id = 0): string
	{
		$step = trim($step);

		//remove any existing green boxes
		$template_regex = '('.GreenBox::GREENBOX_TEMPLATE_PREFIX.'|'.GreenBox::GREENBOX_EXPERT_TEMPLATE_PREFIX.')';
		$step = preg_replace('/{{'.$template_regex.'.*?}}/is', '', $step);

		//add our updated one (if there is one)
		if (!empty($box_content)) {
			if (empty($expert_id))
				$step .= '{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX.$box_content.'}}';
			else
				$step .= '{{'.GreenBox::GREENBOX_EXPERT_TEMPLATE_PREFIX.$expert_id.'|'.$box_content.'|'.$box_content_2.'}}';
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

	private function newGreenBoxHtml(string $box_content, string $box_content_2 = '', $expert_id = 0): string {
		if (empty($box_content)) return '';

		$box_content .= $this->contentNotices($box_content.$box_content_2);

		if (empty($expert_id))
			$html = wfMessage('green_box_container', $box_content)->parse();
		else
			$html = wfMessage('green_box_expert_container', $expert_id, $box_content, $box_content_2)->parse();

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

		$result = $status->isOK();
		if ($result) $page->doPurge();

		return $result;
	}
}
