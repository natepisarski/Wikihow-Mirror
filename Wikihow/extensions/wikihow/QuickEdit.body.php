<?php

class QuickEdit extends UnlistedSpecialPage {
	function __construct() {
		global $wgHooks;
		parent::__construct( 'QuickEdit' );
		$wgHooks['EditFilterMergedContentError'][] = array('QuickEdit::onEditFilterMergedContentError');
	}

	public function execute($par) {
		global $wgUser, $wgRequest, $wgOut;

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		$type = $wgRequest->getVal('type', null);
		$target = $wgRequest->getVal('target', null);
		if ($type == 'editform') {
			$wgOut->setArticleBodyOnly(true);
			$title = Title::newFromURL($target);
			if (!$title) {
				$wgOut->addHTML('error: bad target');
			} else {
				self::showEditForm($title);
			}
		}
	}

	/**
	 * Display the Edit page for an article for an AJAX request.  Outputs
	 * HTML.
	 *
	 * @param Title $title title object describing which article to edit
	 */
	public static function showEditForm($title) {
		global $wgRequest, $wgTitle, $wgOut;

		$wgTitle = $title;
		$article = new Article($title);
		$editor = new EditPage($article);
		$editor->edit();

		if ($editor->isConflict && $wgRequest->wasPosted()) {
			$wgOut->clearHTML();
			$wgOut->setStatusCode(409);
			$wgOut->addWikiText( 'Your edit could not be saved due to an edit conflict. Try closing and re-opening the Quick Edit window' );
			return;
		}

		if ($wgOut->mRedirect && $wgRequest->wasPosted()) {
			$wgOut->redirect('');
			$rev = Revision::newFromTitle($title);
			$wgOut->addHTML( $wgOut->parse( $rev->getText() ) );
		}
	}

	public static function onEditFilterMergedContentError($context, $content, $status) {
		$out = $context->getOutput();
		header('HTTP/1.0 409 Conflict');
		print $status->getHTML();
		// Never let this function end, otherwise the output is overwritten
		exit;
		return true;
	}

	public static function getQuickEditUrl($t) {
		return Title::makeTitle(NS_SPECIAL, "QuickEdit")->getFullURL()
		. '?type=editform&target=' . urlencode($t->getFullText());
	}

}
