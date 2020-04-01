<?php

require_once __DIR__ . '/../../Maintenance.php';

class changeSourcesSectionToReferences extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "change sources section to references";
		$this->addOption( 'limit', 'number of items to process', false, true, 'l' );
		$this->addOption( 'verbose', 'print verbose info', false, false, 'v' );
    }

	private function changeSourcesToReference( $pageId ) {
		$this->addOption( 'verbose', 'print verbose info', false, false, 'v' );

		$verbose = $this->getOption( "verbose" );

		//get latest revision
		$title = Title::newFromId( $pageId );
		if ( !$title ) {
			return 0;
		}

		if ( $verbose ) {
			decho("processing $title", $pageId);
		}

		$text = $this->getLatestGoodRevisionText( $title );

		// get this for comparison later
		$originalText = $text;

		$text = str_ireplace( "== sources and citations ==", "== References ==", $text );
		$text = str_ireplace( "==sources and citations ==", "== References ==", $text );
		$text = str_ireplace( "== sources and citations==", "== References ==", $text );
		$text = str_ireplace( "==sources and citations==", "== References ==", $text );
		$text = str_ireplace( "==sources & citations==", "== References ==", $text );
		$text = str_ireplace( "== sources & citations ==", "== References ==", $text );
		$text = str_ireplace( "== sources ==", "== References ==", $text );
		$text = str_ireplace( "==sources==", "== References ==", $text );
		$text = str_ireplace( "==citations==", "== References ==", $text );
		$text = str_ireplace( "== citations ==", "== References ==", $text );

		if ( $text != $originalText ) {
			if ( $verbose ) {
				decho( "will edit content on $title" );
			}
			$this->editContent( $text, $title );
			return 1;
		} else {
			if ( $verbose ) {
				decho( "no edit for $title" );
			}
		}
		return 0;
	}

	public static function getScriptUser() {
		$user = User::newFromName( "MiscBot" );
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
	}

	// text - the final text to save on the title
	// title - the title on which we are doing the edit
	// returns result of the doEditContent call
	private function editContent( $text, $title ) {
		$content = ContentHandler::makeContent( $text, $title );

		// we do not use the EDIT_SUPPRESS_RC flag because that prevents the edit from
		// being auto patrolled
		$editFlags = EDIT_UPDATE | EDIT_MINOR | EDIT_FORCE_BOT;

		$editSummary = 'Updating References section name to match new sitewide conventions';

		$scriptUser = $this->getScriptUser();

		$page = WikiPage::factory( $title );
		$result = $page->doEditContent( $content, $editSummary, $editFlags, false, $scriptUser);
		return $result;
	}

	private function getLatestGoodRevisionText( $title ) {
		$gr = GoodRevision::newFromTitle( $title );
		if ( !$gr ) {
			return "";
		}

		$latestGood = $gr->latestGood();
		if ( !$latestGood ) {
			return "";
		}
		$r = Revision::newFromId( $latestGood );
		if ( !$r ) {
			return "";
		}
		return ContentHandler::getContentText( $r->getContent() );
	}


	private function processItems() {
		$pageIds = $this->getPageIds();
		$itemCount = count( $pageIds );
		if ( $itemCount == 0 ) {
			decho("there are no pages to process");
			exit();
		}
		$edits = 0;
		foreach ( $pageIds as $pageId ) {
			$result = $this->changeSourcesToReference( $pageId );
			if ( $result > 0 ) {
				$edits++;
			}
		}
		decho( "procesed $itemCount pages. edits", $edits );
	}

	private function getPageIds() {
		$verbose = $this->getOption( "verbose" );
		$limit = 1;
		if ( $this->getOption( 'limit' ) ) {
			$limit = $this->getOption( 'limit');
		}

		$pages = array();
		$dbr = wfGetDb( DB_REPLICA );
		$table = 'page';
		$var = "page_id";
		$cond = "page_namespace = 0";
		$options = ['LIMIT' => $limit];
		$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );
		if ( $verbose ) {
			decho( "get page ids query", $dbr->lastQuery() );
		}
		foreach ( $res as $row ) {
			$pages[] = $row->page_id;
		}

		return $pages;
	}

	public function execute() {
		global $wgLanguageCode;
		$this->processItems();
	}
}


$maintClass = "changeSourcesSectionToReferences";
require_once RUN_MAINTENANCE_IF_MAIN;

