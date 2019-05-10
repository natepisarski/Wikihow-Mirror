<?php

namespace EditMapper;

use RequestContext;
use TranslateEditor;
use TranslationLink;
use User;
use WikiPage;

require_once __DIR__ . '/../TranslationLink.php';

/**
 * Map article edits made by translators to the "WikiHow Traduce" account
 */
class TranslatorEditMapper extends EditMapper {

	private $isLogged = false;

	/**
	 * True for new articles if the user in in the "translator" user group
	 */
	public function shouldMapEdit($title, $user, bool $isNew, string $comment): bool {
		$main = RequestContext::getMain();
		$langCode = $main->getLanguage()->getCode();
		$requestTitle = $main->getTitle();
		return \CreateEmptyIntlArticle::isEligibleToTranslate($title, $langCode, $user) && TranslateEditor::isTranslatorUser()&& $requestTitle->inNamespace(NS_MAIN);
	}

	public function getDestUser($title, bool $isNew) {
		return User::newFromName( wfMessage("translator_account")->text() );
	}

	/**
	 * Log the mapping in the DB
	 * Moved and adapted from TranslateEditor.body.php::onSaveComplete()
	 */
	protected function afterUnmapping(WikiPage $page, User $user) {
		if ($this->isLogged) {
			return;
		}
		$this->isLogged = true;

		$langCode = RequestContext::getMain()->getLanguage()->getCode();
		$toTitle = $page->getTitle();

		if (!$toTitle || !$toTitle->getArticleID()) {
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			'pre_translation_link',
			[ 'ptl_english_aid', 'ptl_translator', 'ptl_to_title' ],
			[ 'ptl_translator' => $user->getId(), 'ptl_to_title' => $toTitle->getText() ]
		);
		if ($row = $dbr->fetchObject($res)) {
			$tl = new TranslationLink();
			$tl->fromAID = $row->ptl_english_aid;
			$tl->fromLang = 'en';
			$tl->toLang = $langCode;
			$tl->toAID = $toTitle->getArticleId();
			$tl->isTranslated = true;
			$tl->insert();

			TranslationLink::writeLog(TranslationLink::ACTION_SAVE, 'en', NULL, $tl->fromAID,
				NULL, $langCode, $toTitle->getText(), $toTitle->getArticleId());

			//also need to unprotect the article now
			$page = new WikiPage($toTitle);
			$cascade = false;
			$protectResult = $page->doUpdateRestrictions([], [], $cascade, "Doing translation", $user)->isOK();
		}
	}

}
