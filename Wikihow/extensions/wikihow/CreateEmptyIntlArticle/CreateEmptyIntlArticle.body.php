<?php

class CreateEmptyIntlArticle extends UnlistedSpecialPage {
	var $rowPos = array('lang' => 0, 'en_id' => 1, 'translated_title' => 2);
	var $translationUser;

	public function __construct() {
		parent::__construct( 'CreateEmptyIntlArticle');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();
		$request = $this->getRequest();
		$upload = $request->getVal('upload','');

		$out->setRobotPolicy('noindex, nofollow');

		if (!self::authorizedUser($user)) {
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if($upload == 1) {
			//user has uploaded a csv file. Process and output a csv file of results
			$out->setArticleBodyOnly(true);
			$filename = $request->getFileTempName('csvFile');
			$results = $this->processCSVFile($filename);
			$this->exportResults($results);
		} else {
			$loader = new Mustache_Loader_CascadingLoader([
				new Mustache_Loader_FilesystemLoader(__DIR__)
			]);
			$options = array('loader' => $loader);
			$m = new Mustache_Engine($options);

			$html = $m->render('/templates/page');
			$out->addHtml($html);
			$out->addModules("ext.wikihow.createemptyintlarticle");
			$out->setHTMLTitle("Create Empty International Articles");
		}

	}

	public static function authorizedUser(User $user): bool {
		return !$user->isAnon() && Misc::isUserInGroups($user, self::authorizedGroups());
	}

	public static function authorizedGroups(): array {
		return [
			'staff',
			'staff_widget',
		];
	}

	private function processCSVFile($filename) {
		ini_set('auto_detect_line_endings',TRUE);
		$handle = fopen($filename, 'r');

		fgetcsv($handle, 0, ",");
		$numRows = 1;

		//change the user so the first edit is the translation account for this language
		$this->translationUser = $this->getTranslationUser();
		$results = [];
		while (($datum = fgetcsv($handle, 0, ",")) !== FALSE ) {
			if (!$this->validDataRow($datum)) {
				$invalidUrls[$datum[$this->rowPos['url']]] = "missing row data (url, id, score or rank)";
				continue;
			} else {
				$data[] = $datum;
			}

			$results[] = $this->processRow($datum);
			$numRows++;
		}

		return $results;
	}

	private function processRow($data) {
		$context = $this->getContext();
		$languageCode = $context->getLanguage()->getCode();
		$result = [
			'lang' => $data[$this->rowPos['lang']],
			'en_id' => $data[$this->rowPos['en_id']],
			'en_url' => '',
			'intl_id' => 0,
			'intl_url' => '',
			'result' => ''
		];

		//check to make sure we're on the right domain
		if($languageCode != $data[$this->rowPos['lang']]) {
			$result['result'] = "wrong domain (" . $data[$this->rowPos['lang']] . ")";
			return $result;
		}

		//check to make sure that the english article id is good
		$json = file_get_contents("https://www.wikihow.com/api.php?action=articletext&format=json&aid=" . $data[$this->rowPos['en_id']]);
		if (empty($json)) {
			$result['result'] = "English id not good (" . $data[$this->rowPos['en_id']] . ")";
			return $result;
		}
		$decodeJson = json_decode($json);
		if($decodeJson->error) {
			$result['result'] = "English id not good (" . $data[$this->rowPos['en_id']] . ")";
			return $result;
		}

		$result['en_url'] = $decodeJson->data->articleUrl;

		//check to make sure there isn't a link in the translation link table yet.
		$where = [
			'tl_from_aid = ' . $data[$this->rowPos['en_id']]
		];
		$links = TranslationLink::getLinks("en", $data[$this->rowPos['lang']], $where);
		if(count($links) > 0) {
			$result['result'] = "translation link already exists (en id: " . $data[$this->rowPos['en_id']] . ")";
			return $result;
		}

		//check to make sure the intl article doesn't already exist
		$toTitle = Title::newFromText($data[$this->rowPos['translated_title']]);
		if($toTitle && $toTitle->exists()) {
			$result['intl_url'] = $toTitle->getFullURL();
			$result['intl_id'] = $toTitle->getArticleID();
			$result['result'] = "Intl title already exists (" . $data[$this->rowPos['translated_title']] . ")";
			return $result;
		}

		if(!$toTitle) {
			$result['result'] = "Problem with intl title (" . $data[$this->rowPos['translated_title']] . ")";
			return $result;
		}

		//create the page on the intl site
		$page = new WikiPage($toTitle);
		$content = ContentHandler::makeContent($this->getArticleText(), $toTitle);
		$page->doEditContent($content, $this->getEditSummary(), 0, false, $this->translationUser);
		$limit = ['edit' => "translation", 'move' => 'translation'];
		$cascade = false;
		//protect the new article so only a translator can edit it
		$protectResult = $page->doUpdateRestrictions($limit, [], $cascade, "Waiting for translation", $this->translationUser)->isOK();

		$result['intl_url'] = $toTitle->getFullURL();
		$result['intl_id'] = $toTitle->getArticleID();
		$result['result'] = "success";

		//add the translation link
		$tl = new TranslationLink();
		$tl->fromAID = $data[$this->rowPos['en_id']];
		$tl->fromLang = 'en';
		$tl->toLang = $languageCode;
		$tl->toAID = $toTitle->getArticleID();
		$tl->isTranslated = TranslationLink::TL_STUBBED;
		$tl->insert();

		TranslationLink::writeLog(TranslationLink::ACTION_SAVE, 'en', NULL, $tl->fromAID,
			NULL, $languageCode, $toTitle->getText(), $toTitle->getArticleId());

		return $result;
	}

	private function getArticleText() {
		return wfMessage("ftt_articletext", wfMessage("Steps")->text(), wfMessage("ftt_step_placeholder")->text())->inLanguage("en")->plain();
	}

	private function getEditSummary() {
		return wfMessage("ftt_editsummary")->text();
	}

	private function getTranslationUser() {
		$userName = wfMessage("Translator_account")->text();
		$user = User::newFromName($userName);
		return $user;
	}

	private function validDataRow(&$datum) {
		$rowPos = $this->rowPos;
		$aid = $datum[$rowPos['en_id']];
		$url = $datum[$rowPos['translated_title']];
		$lang = $datum[$rowPos['lang']];
		$valid = true;
		if (!is_numeric($aid) || empty($url) || empty($lang)) {
			$valid = false;
		}
		return $valid;
	}

	private function exportResults($results) {
		$date = date('Y-m-d');
		$this->setCSVHeaders("emptyintlarticles_{$date}.xls");
		$connector = "\t";
		echo "Language code{$connector}EN id{$connector}EN url{$connector}intl id{$connector}intl url{$connector}result\n";
		foreach($results as $result) {
			echo $result['lang'] . $connector . $result['en_id'] . $connector . $result['en_url'] . $connector . $result['intl_id'] . $connector . $result['intl_url'] . $connector . $result['result'] . "\n";
		}
	}

	private function setCSVHeaders(string $fname) {
		// NOTE: setArticleBodyOnly(true) doesn't work here because
		// we need to change Content-Type response header.
		$this->getOutput()->disable();
		header("Content-type: application/force-download");
		header("Content-disposition: attachment; filename=$fname");
	}

	//Check to see whether the intl article
	//can be translated
	public static function isEligibleToTranslate($title, $languageCode, $user) {
		if($languageCode == "en") return false;

		if(!Misc::isUserInGroups($user, ['translator', 'staff'])) return false;

		//does it exist in the translation table yet?
		$links = TranslationLink::getLinks("en", $languageCode, ['tl_to_aid = ' . $title->getArticleID()]);
		foreach($links as $link) {
			if($link->isTranslated == TranslationLink::TL_TRANSLATED) return false;
			if($link->isTranslated == TranslationLink::TL_STUBBED) return true;
		}

		//it doesn't exist in the translation table, so does it exist at all?
		if($title->getArticleID() > 0) return false;

		return true;
	}
}
