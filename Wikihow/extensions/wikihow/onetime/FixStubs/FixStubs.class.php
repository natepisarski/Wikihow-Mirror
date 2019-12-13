<?php

/**
 * LH 3084: The AdminMassEdit tool crashed on FR/ID/RU when attempting to stub
 * lots of articles. That left the DB in an inconsitent state: the "templatelinks"
 * and "index_info" tables didn't get updated to reflect the newly added {{stub}} tags.
 *
 * This tool fixes articles that contain a {{stub}} tag but are still indexed.
 */
class FixStubsSpecialPage extends UnlistedSpecialPage
{
	public function __construct() {
		parent::__construct('FixStubs');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		// AUTH

		$allowed = Misc::isIntl() && $user->getName() == 'Albur';
		if ( !$allowed ) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$out->setPageTitle('FixStubs');
		$out->addModules('ext.wikihow.FixStubs');

		// GET

		if ( !$req->wasPosted() ) {
			$must = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)]);
			$html = $must->render('FixStubs.mustache', [ 'token' => $user->getEditToken() ]);
		}

		// POST - invalid token

		elseif ( !$user->matchEditToken( $req->getText('token') ) ) {
			$html = '<b>Error:</b> Invalid token.';
		}

		// POST - correct token

		else {
			DeferredUpdates::addUpdate( new Helper() );
			$html = "<b>Success!</b> A DeferrableUpdate has been scheduled";
		}

		$out->addHTML( $html );
	}

}

class Helper implements DeferrableUpdate {

	private $botUser;

	public function doUpdate() {
		$this->botUser = AdminMassEdit::getBotUser();

		wfDebugLog( 'alber', "Starting." );
		$count = $this->fixStubs();
		wfDebugLog( 'alber', "Complete. Fixed $count articles." );
	}

	private function fixStubs(): int
	{
		ini_set('memory_limit', '1024M');
		set_time_limit(0);
		ignore_user_abort(true);

		// Get all indexable articles

		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['page', 'index_info'];
		$fields = ['page_id', 'ii_policy'];
		$where = [
			'page_namespace' => NS_MAIN,
			'page_is_redirect' => 0,
			'ii_page = page_id',
			'ii_policy' => [1, 4]
		];
		$opts = [];
		$join = [ 'index_info' => [ 'LEFT JOIN', [ 'page_id = ii_page' ] ] ];
		$rows = $dbr->select($tables, $fields, $where, __METHOD__, $opts, $join);

		$count = 0;
		foreach ($rows as $row)
		{
			// Does the article exist?

			$aid = (int) $row->page_id;
			$page = WikiPage::newFromId($aid);
			if ( !$page || !$page->exists() ) {
				continue;
			}

			// Does the article contain '{{stub...}}' ?

			$wikiText = ContentHandler::getContentText( $page->getContent() );
			$hasStubTag = preg_match('/{{stub[^\}]*}}/i', $wikiText, $matches) === 1;
			if ( !$hasStubTag ) {
				continue;
			}

			// "Touch" the article so `templatelinks` and `index_info` get updated
			$this->editArticle($page, $wikiText);

			if ( ++$count % 100 == 0) {
				wfDebugLog( 'alber', "Processed $count articles" );
			}
		}

		return $count;
	}

	/**
	 * Call WikiPage->doEditContent() using the existing wikiText, so there are no changes
	 * and no new revision. This is enough to trigger the relevant hooks.
	 */
	private function editArticle(WikiPage $page, string $wkTxt): string {
		$title = $page->getTitle();

		if ( $title->isRedirect() ) {
			return "skip_redirect";
		}

		$revision = Revision::newFromTitle($title);
		if ( !$revision || $revision->getId() <= 0 ) {
			return "skip_no_prev_rev";
		}

		$summary = "LH#3084";
		$content = ContentHandler::makeContent($wkTxt, $title);
		$status = $page->doEditContent( $content, $summary, 0, false, $this->botUser);

		if ( $status->isOK() ) {
			return 'success';
		} else {
			return 'error';
		}
	}

}
