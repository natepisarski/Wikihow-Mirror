<?php

require_once __DIR__ . '/../../TranslationLink.php';

/**
 * Modifies the edit page to ask for a URL to translate, fetches content to translate and tracks
 * translations between languages in teh database.

CREATE TABLE `pre_translation_link` (
  `ptl_translator` int(11) NOT NULL,
  `ptl_to_title` varchar(255) NOT NULL,
  `ptl_english_aid` int(11) NOT NULL,
  `ptl_timestamp` varchar(14) NOT NULL,
  PRIMARY KEY  (`ptl_translator`,`ptl_to_title`)
)
 */

class TranslateEditor extends UnlistedSpecialPage {

	const TOOL_NAME = "TRANSLATE_EDITOR";

	// These templates will be removed when we translate
	function __construct() {
		parent::__construct('TranslateEditor');
	}

	public function execute ($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;
		$user = $this->getUser();
		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}
		$userGroups = $user->getGroups();

		if (!self::isTranslatorUser()) {
			$wgOut->setRobotPolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$action = $wgRequest->getVal('action', null);
		$target = $wgRequest->getVal('target', null);
		$toTarget = $wgRequest->getVal('toTarget', null);

		if ($action == "getarticle") {
			$this->startTranslation($target, $toTarget);
		}
	}

	/**
	 * Check if the user is a translater, and return true if a translator and false otherwise
	 */
	public static function isTranslatorUser() {
		global $wgUser, $wgLanguageCode;

		$userGroups = $wgUser->getGroups();

		//if ($wgUser->getID() == 0 || (!(in_array('translator', $userGroups) ) )) {
		$translatorAcct = wfMessage('translator_account')->text();
		if ($wgUser->getID() == 0
			|| ( !in_array('translator', $userGroups)
				&& strcasecmp($wgUser->getName(), $translatorAcct) != 0 )
			|| $wgLanguageCode == "en"
		) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Called when the user goes to an edit page
	 * Override the functionality of the edit to require a URL to translate
	 */
	public static function onCustomEdit() {
		global $wgRequest, $wgOut, $wgLanguageCode, $wgUser;

		$target = $wgRequest->getVal('title', null);
		$title = Title::newFromURL($target);

		$isCustomEdit = $title && $title->inNamespace(NS_MAIN) && self::isTranslatorUser();
		if ( !$isCustomEdit ) {
			return;
		}

		$draft = $wgRequest->getVal('draft', null);
		$action = $wgRequest->getVal('action', null);
		$section = $wgRequest->getVal('section',$wgRequest->getVal('wpSection',null));
		$save = $wgRequest->getVal('wpSave',null);

		$wgOut->addModules('ext.wikihow.translateeditor');

		Mustache_Autoloader::register();
		$options =  ['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)];
		$m = new Mustache_Engine($options);
		$vars = [
			'remove_templates' => json_encode( array_map('preg_quote', EditorUtil::getMsgArray('remove_templates')) ),
			'remove_sections'  => json_encode( array_map('preg_quote', EditorUtil::getMsgArray('remove_sections')) ),
			'sources_name' => json_encode(wfMessage('Sources')->text()),
			'steps_name' => json_encode(wfMessage('Steps')->text()),
			'references_name' => json_encode(wfMessage('references')->text())
		];

		// We have the dialog to enter the URL when we are adding a new article, and have no existing draft.

		if ($draft == null
			&& (!$title->exists() || CreateEmptyIntlArticle::isEligibleToTranslate($title, $wgLanguageCode, $wgUser))
			&& $action=='edit'
		) {
			// Templates to remove from translation
			// Words or things to automatically translate
			$translations = EditorUtil::getSectionTranslations();
			$translations[] = [ 'from' => '\[\[Category:[^\]]+\]\]', 'to' => '' ];

			$vars = array_merge( $vars, [
				'title' => $target,
				'checkForLL' => true,
				'translateURL' => true,
				'translations' => json_encode($translations),
			]);
			if ($title->exists()) {
				$wikiText = Wikitext::getWikitext($title);
				$summary = Wikitext::getSummarizedSection($wikiText);
				if($summary != "") {
					$vars['translationExists'] = true;
					$vars['translatedSummary'] = wfMessage("summary_section_notice")->text() . "\n" . $summary;
				}
			}

			$html = $m->render('TranslateEditor.mustache', $vars);
			$wgOut->addHTML($html);
			QuickEdit::showEditForm($title);
			return false;
		}
		elseif ($section == null && $save == null) {
			$vars = array_merge( $vars, ['title'=>$target, 'checkForLL'=>true, 'translateURL'=>false] );
			$html = $m->render('TranslateEditor.mustache', $vars);
			$wgOut->addHTML($html);
			QuickEdit::showEditForm($title);
			return false;
		}
		return true;
	}

	/**
	 * Start a translation by fetching the article to be translated,
	 * and logging it.
	 */
	private function startTranslation($fromTarget, $toTarget) {
		global $wgOut, $wgRequest, $wgLanguageCode, $wgUser;
		$target = urldecode($fromTarget);
		$text = EditorUtil::getArticleInfoByName($target);
		$output = array();
		$wgOut->setArticleBodyOnly(true);
		$json = json_decode($text, true);
		$ak = array_keys($json['query']['pages']);
		$fromAID = intVal($ak[0]);
		$translationExists = false;
		$stubExists = false;
		//first check to see if there's a translation started, and if so, is it the same article
		$toTitle = Title::newFromText(urldecode($toTarget));
		if($toTitle && $toTitle->getArticleID() > 0) {
			$links = TranslationLink::getLinks("en", $wgLanguageCode, ['tl_to_aid = ' . $toTitle->getArticleID()]);
			foreach($links as $link) {
				if($link->fromAID != $fromAID) {
					$translationExists = true;
				} else {
					if($link->isTranslated == TranslationLink::TL_TRANSLATED) {
						$translationExists = true;
					}
				}
			}
		}

		//The article we are translating exists
		if ($fromAID > 0 ) {
			$links = TranslationLink::getLinksTo("en", $fromAID, true);
			foreach ($links as $link) {
				if($link->toLang == $wgLanguageCode) {
					if ($link->isTranslated == TranslationLink::TL_STUBBED && $toTitle && $link->toAID != $toTitle->getArticleID()) {
						$stubExists = true;
						$stubTitle = $link->toURL;
						$translationExists = true;
					} elseif ($link->isTranslated == TranslationLink::TL_STUBBED && !$toTitle) {
						$stubExists = true;
						$stubTitle = $link->toURL;
						$translationExists = true;
					} elseif ($link->isTranslated == TranslationLink::TL_TRANSLATED) {
						$translationExists = true;
					}
				}
			}
			if (!$translationExists) {
				$fromRevisionId = $json['query']['pages'][$fromAID]['revisions'][0]['revid'];
				$txt = $json['query']['pages'][$fromAID]['revisions'][0]['*'];
				if (preg_match("/#REDIRECT/",$txt)) {
					$output['error'] = "It seems the article you are attempting to translate is a redirect. Please contact your project manager.";
					$output['success'] = false;
				}
				else {
					$txt = EditorUtil::replaceInternalLinks($txt);

					$dbr = wfGetDB(DB_REPLICA);
					$enDbKey = $dbr->selectField( 'wikidb_112.page', 'page_title', ['page_id'=>$fromAID] );
					$txt = EditorUtil::removeSummary($txt, $enDbKey);

					$output['success'] = true;
					$output['aid'] = $fromAID;
					$output['text'] = $txt;

					$dbw = wfGetDB(DB_MASTER);
					$sql = 'INSERT INTO pre_translation_link (ptl_translator, ptl_english_aid, ptl_to_title, ptl_timestamp) VALUES (' . $dbw->addQuotes($wgUser->getId()) . ',' . $dbw->addQuotes($fromAID) . ',' . $dbw->addQuotes($toTarget) . ',' . $dbw->addQuotes(wfTimestampNow()) .  ') on duplicate key update ptl_english_aid=' . $dbw->addQuotes($fromAID) . ', ptl_timestamp=' . $dbw->addQuotes(wfTimestampNow());
					$dbw->query($sql, __METHOD__);
					TranslationLink::writeLog(TranslationLink::ACTION_NAME, 'en', $fromRevisionId, $fromAID,$target,$wgLanguageCode,$toTarget);
				}
			} else {
				if($stubExists) {
					$output['warning'] = wfMessage('translation_stub_warning', $stubTitle)->parse();
				} else {
					$output['error'] = "It seems the article was already translated. Please contact your project manager.";
				}
				$output['success'] = false;
			}
		}
		else {
			$output['success'] = false;
			$output['error'] = "No article at given URL. Please contact your project manager.";
		}
		$wgOut->addHTML(json_encode($output));
	}

}
