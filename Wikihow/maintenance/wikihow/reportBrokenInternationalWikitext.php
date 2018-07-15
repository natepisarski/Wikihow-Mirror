<?php

require_once __DIR__ . '/../Maintenance.php';

class ReportBrokenInternationalWikitext extends Maintenance {
	const LL_RE = '/\[\[[a-zA-Z][a-zA-Z]:[^\]]+\]\]/';
	const REF_OPEN_RE = '/<ref>/';
	const REF_CLOSE_RE = '/<\/ref>/';
	const REFLIST_RE = '/{{reflist}}/';
	const STEPS_NEWLINES_RE = '/#[^=]+[\p{Zl}\n][^=#]*\n#([^\p{Zl}\n]+)/';
	const STEPS_SPACES_RE = '/[\p{Zl}\n^] +#([^\p{Zl}\n]+)/';

	private $lang_sources;
	private $lang_steps;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generate a report of international articles with broken Wikitext';
	}

	public function execute() {
		$tsv = $this->generateReport();
		print $tsv;
	}

	public function generateLangPatterns() {
		global $wgLanguageCode;
		$lang = $wgLanguageCode;
		$this->lang_sources = wfMessage('sources')->inLanguage($lang)->plain();
		$this->lang_steps = wfMessage('steps')->inLanguage($lang)->plain();
	}

	public function generateReport() {
		$this->generateLangPatterns();

		$errors = $this->getWikitextErrors();

		$tsv = $this->getTSV($errors);

		return $tsv;
	}

	protected function getTSV(&$errors) {
		global $wgLanguageCode;

		$whUrl = Misc::getLangBaseURL($wgLanguageCode);

		$tsv = array();
		$tsv[] = "url\terrors";

		foreach ($errors as $page_title=>$page_errors) {
			$url = $whUrl . str_replace(' ', '-', $page_title);
			$tsv[] = $url . "\t" . implode("\t", $page_errors);
		}

		return implode("\n", $tsv);
	}

	public function getWikitextErrors() {
		global $wgLanguageCode;
		$lang = $wgLanguageCode;

		$dbr = wfGetDB(DB_SLAVE);

		$lang_errors = array();

		$page_titles = array();
		$res = $dbr->select(
			array($dbr->addIdentifierQuotes(Misc::getLangDB($lang)) . '.page'),
			array('page_title'),
			array(
				'page_namespace' => NS_MAIN,
				'page_is_redirect' => 0
			),
			__METHOD__
		);

		if ($res === false) {
			$lang_errors['success'] = false;
			$lang_errors['error'] = 'A database error has occurred';
			return $lang_errors;
		}

		foreach ($res as $row) {
			$page_titles[] = $row->page_title;
		}

		foreach ($page_titles as $i => $page_title) {
			$t = Title::newFromDBKey($page_title);
			$wikitext = Wikitext::getWikitext($dbr, $t);

			$errors = $this->checkWikitext($wikitext);

			if ($errors) {
				$lang_errors[$page_title] = call_user_func_array(
					'array_merge',
					$errors
				);
			}
		}

		return $lang_errors;
	}

	public function checkWikitext(&$wikitext) {
		return array_map(
			function ($result) {
				return $result['errors'];
			},
			array_filter(
				array(
					// $this->checkForLL($wikitext), // Disabled for now
					$this->checkForCite($wikitext),
					$this->checkForStepSpaces($wikitext),
					$this->checkForSteps($wikitext)
				),
				function ($result) {
					return !$result['success'];
				}
			)
		);
	}

	public function checkForLL(&$wikitext) {
		$result = array();
		$result['success'] = true;

		if (preg_match(self::LL_RE, $wikitext)) {
			$result['success'] = false;
			$result['errors']['interwiki'] = 'Interwiki links are no longer allowed.';
		} 

		return $result;
	}

	public function checkForCite(&$wikitext) {
		$result = array();
		$result['success'] = true;

		if (preg_match(self::REF_OPEN_RE, $wikitext)) {
			if (!preg_match(self::REFLIST_RE, $wikitext)
				|| !preg_match(
					'/== *' . $this->lang_sources . ' *==/',
					$wikitext
				)
			) {
				$result['success'] = false;
				$result['errors']['sources'] =
					"Article contains '<ref>', so there must be a '"
					. $this->lang_sources
					. "' section with template {{reflist}}.";
			}

			if (!preg_match(self::REF_CLOSE_RE, $wikitext)) {
				$result['success'] = false;
				$result['errors']['refclose'] =
					"Article must contain closing '</ref>' for ref tag.";
			}
		}

		return $result;
	}

	public function checkForStepSpaces(&$wikitext) {
		$result = array();
		$result['success'] = true;
		
		if (preg_match(self::STEPS_NEWLINES_RE, $wikitext, $m)) {
			$step = $m[1];
			if (mb_strlen($step) > 48) {
				$step = mb_substr($step, 0, 48) . '...';
			}
			$result['success'] = false;
			$result['errors']['steps_newlines'] =
				"Extra newline before the following step: $step";
		}

		if (preg_match(self::STEPS_SPACES_RE, $wikitext, $m)) {
			$step = $m[1];
			if (mb_strlen($step) > 48) {
				$step = mb_substr($step, 0, 48) . '...';
			}
			$result['success'] = false;
			$result['errors']['steps_spaces'] =
				"Extra space before the following step: $step";
		}

		return $result;
	}

	public function checkForSteps(&$wikitext) {
		$result = array();
		$result['success'] = true;

		if (!preg_match(
			'/== *' . $this->lang_steps . ' *==[^=]/',
			$wikitext
		)) {
			$result['success'] = false;
			$result['errors']['steps'] =
				"There must be a '"
				. $this->lang_steps
				. "' section.";
		}

		return $result;
	}
}

$maintClass = 'ReportBrokenInternationalWikitext';
require_once RUN_MAINTENANCE_IF_MAIN;

