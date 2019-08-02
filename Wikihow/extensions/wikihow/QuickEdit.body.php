<?php

class QuickEdit extends UnlistedSpecialPage {
	function __construct() {
		global $wgHooks;
		parent::__construct( 'QuickEdit' );
		$wgHooks['EditFilterMergedContentError'][] = array('QuickEdit::onEditFilterMergedContentError');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$type = $req->getVal('type', null);
		$target = $req->getVal('target', null);
		if ($type == 'editform') {
			$out->setArticleBodyOnly(true);
			$title = Title::newFromURL($target);
			if (!$title) {
				$out->addHTML('error: bad target');
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
		$ctx = RequestContext::getMain();
		$wikiPage = WikiPage::factory( $title );
		$ctx->setWikiPage($wikiPage);

		$req = $ctx->getRequest();
		$out = $ctx->getOutput();

		$article = new Article($title);
		$editor = new EditPage($article);
		$editor->edit();

		if ($editor->isConflict && $req->wasPosted()) {
			$out->clearHTML();
			$out->setStatusCode(409);
			$out->addWikiText( 'Your edit could not be saved due to an edit conflict. Try closing and re-opening the Quick Edit window' );
			return;
		}

		if ($out->mRedirect && $req->wasPosted()) {
			$out->redirect('');
			$rev = Revision::newFromTitle($title);
			$out->addHTML( $out->parse( ContentHandler::getContentText( $rev->getContent() ) ) );
		}
	}

	public static function onEditFilterMergedContentError($context, $content, $status) {
		$out = $context->getOutput();
		header('HTTP/1.0 409 Conflict');
		print $status->getHTML();
		// Never let this function end, otherwise the output is overwritten
		exit;
	}

	public static function getQuickEditUrl($t) {
		return Title::makeTitle(NS_SPECIAL, "QuickEdit")->getFullURL()
		. '?type=editform&target=' . urlencode($t->getFullText());
	}

}
