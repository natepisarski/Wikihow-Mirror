<?php

require_once __DIR__ . '/../../Maintenance.php';

/**
 * This script finds articles with an incorrect ti_bytes value, and edits them
 * (without content changes) in order to create a new revision, which fixes the stat.
 *
 * Background:
 *
 * https://wikihow.lighthouseapp.com/projects/97771/tickets/3261
 *
 * - There are some rows in the revision table that differ in db1 vs db8.
 * - This is due to replication errors that took place during the last week
 *   of March 2020, when we transitioned from Rackspace to AWS.
 * - The ti_bytes field is calculated based on the latest article revision, so
 *   a few articles that were last edited that week have an incorrect ti_bytes value.
 */
class editArticlesWithBrokenTitusBytes extends Maintenance {

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		global $wgLanguageCode, $wgIsDevServer;

		$baseUrl = !$wgIsDevServer ? Misc::getLangBaseURL() : "https://{$wgLanguageCode}-i1.wikidogs.com";
		$user = $this->getBotUser();
		foreach ($this->getTitles() as $aid => $title) {
			$url = $baseUrl . $title->getLocalUrl();
			$status = $this->editTitle($user, $title);
			echo "{$wgLanguageCode}\t{$aid}\t{$url}\t{$status}\n";
		}
	}

	private function getBotUser(): User {
		$user = User::newFromName( 'MiscBot' );
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
	}

	private function getTitles(): Generator {
		global $wgLanguageCode;

		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['wikidb_112.titus_copy'];
		$fields = ['ti_page_id'];
		$where = [
			'ti_language_code' => $wgLanguageCode,
			'ti_bytes < 50',
			'ti_deleted_date IS NULL',
			'ti_timestamp IS NOT NULL',
		];
		$res = $dbr->select($tables, $fields, $where, __METHOD__);
		foreach ($res as $row) {
			$title = Title::newFromId($row->ti_page_id);
			if ( $title && $title->exists() && !$title->isMainPage() && !$title->isRedirect() ) {
				yield (int)$row->ti_page_id => $title;
			}
		}
	}

	private function editTitle( User $user, Title $title ): ?Status {
		$text = $this->getLatestGoodRevisionText( $title );
		$summary = 'Bot edit to create a new revision - LH 3261';
		$flags = EDIT_UPDATE | EDIT_MINOR | EDIT_FORCE_BOT;
		$page = WikiPage::factory( $title );

		$content = ContentHandler::makeContent( "$text\nbot edit", $title );
		$page->doEditContent( $content, "$summary - part 1", $flags, false, $user);

		$content = ContentHandler::makeContent( $text, $title );
		return $page->doEditContent( $content, "$summary - part 2", $flags, false, $user);
	}

	private function getLatestGoodRevisionText(Title $title): ?string {
		$goodRev = GoodRevision::newFromTitle( $title );
		if ( !$goodRev ) {
			return null;
		}

		$lastRevId = $goodRev->latestGood();
		if ( !$lastRevId ) {
			return null;
		}

		$newRev = Revision::newFromId( $lastRevId );
		if ( !$newRev ) {
			return null;
		}

		return ContentHandler::getContentText( $newRev->getContent() );
	}

}


$maintClass = 'editArticlesWithBrokenTitusBytes';
require_once RUN_MAINTENANCE_IF_MAIN;

