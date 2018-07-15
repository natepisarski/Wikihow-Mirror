<?php
/**
 * update related wikihows section to remove deindexed titles
 * run it like this:
 * /opt/wikihow/scripts/whrun --user=apache -- updateRelatedWikihows.php -d -l 8
 *
 */

require_once __DIR__ . '/../Maintenance.php';

/**
 * Maintenance script that will look at the related wikihows of an article
 * and remove them if they are pointing to non indexed articles
 *
 */
class UpdateRelatedWikihows extends Maintenance {
	public function __construct() {
		parent::__construct();
        $this->mDescription = "update related wikihows";
		$this->addOption( 'run', 'delete the bad referenced articles', false, false, 'r' );
		$this->addOption( 'debug', 'debug output', false, false, 'd' );
		$this->addOption( 'limit', 'limit', false, true, 'l' );
		$this->addOption( 'page', 'force run on one page', false, true, 'p' );
	}

	public function execute() {
		$run = $this->getOption( 'run' );
		$this->updateArticles( $run );
		$this->output( "Done\n" );
	}

	// gets the title which is inside the [[ ]] brackets in wikitext
	private function getTitleFromWikitext( $text ) {
		if ( preg_match('@\[\[(.*?)(\]\]|\|)@im', $text, $m) ) {
			$titleText = $m[1];
			$title = Title::newFromText( $titleText );
			return $title;
		}
		//decho( "no match for", $text, false );
	}

	private function isDeindexed( $title ) {
		if ( !$title ) {
			return true;
		}

		$pageId = $title->getArticleID();
		if ( !$pageId ) {
			return true;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$count = $dbr->selectField(
			'index_info', 
			'count(*)',
			array('ii_page' => $pageId, 'ii_policy in (2, 3)'),
			__METHOD__);

		return $count > 0;
	}

	public static function getScriptUser() {
		$user = User::newFromName( "RelatedWikihowsBot" );
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
	}

	// title - the title of the article to be edited that contains the related wikihows section
	// text - the full wikitext of this article
	// linesToRemove - aray of objects that contains the title and line to remove
	// sectionHeading - the first line or heading of the related wikihows section in case we need to remove it
	// relatedCount - the total count of related wikihows we counted to determine if the section will be empty
	// return - result of the edit or nothing if no edit was done
	protected function removeLinesAndSave( $title, $text, $linesToRemove, $sectionHeading, $relatedCount ) {
		if ( !$title ) {
			return;
		}
		if ( !$text ) {
			return;
		}
		$removeCount = count( $linesToRemove );
		if ( !$linesToRemove || $removeCount < 1 ) {
			return;
		}

		$finalText = $text;
		// for each line to remove, delete it from the wikitext
		foreach ( $linesToRemove as $line ) {
			$replaceCount = 0;
			$finalText = str_replace( $line.PHP_EOL, "", $finalText, $replaceCount );
			// if this is the last line, then remove but not the php_eol
			if ( $replaceCount == 0 ) {
				$finalText = str_replace( $line, "", $finalText, $replaceCount );
			}
			if ( $replaceCount > 1 ) {
				decho("$replaceCount references to $line removed", $text, false);
			}
		}

		// if we have removed all of the related wikihows from the article
		// then also remove the section heading
		if ( $removeCount == $relatedCount ) {
			$finalText = str_replace( $sectionHeading.PHP_EOL, "", $finalText );
		}

		$url = wfExpandUrl( Misc::getLangBaseURL( 'en' ) . $title->getLocalURL(), PROTO_CANONICAL );
		echo( $url ." ".$removeCount." ".$relatedCount."\n" );
		$this->editContent( $finalText, $title );
	}

	// does the actual save of new content with proper user and summary and flags
	// text - the final text to save on the title
	// title - the title on which we are doing the edit
	// returns result of the doEditContent call
	private function editContent( $text, $title ) {
		$content = ContentHandler::makeContent( $text, $title );

		// we do not use the EDIT_SUPPRESS_RC flag because that prevents the edit from
		// being auto patrolled
		$editFlags = EDIT_UPDATE | EDIT_MINOR | EDIT_FORCE_BOT;

		$editSummary = 'Automatic link maintenance: cleaning up links to pages that are not ready for readers (stubbed/de-indexed articles)';

		$scriptUser = $this->getScriptUser();

		$page = WikiPage::factory( $title );
		$result = $page->doEditContent( $content, $editSummary, $editFlags, false, $scriptUser);
		return $result;
	}

	// keep track of how many related wikihows total we have seen
	// it's possible that somehow there is a messed up reference to a non title
	// that will mess up our count but we can run the script in debug mode to find
	// how many if any of these instances exist and safely assume it won't happen
	// in normal circumstances
	// pageId the pageId of the article to act on
	private function removeDeindexedRelatedWikihows( $pageId, $pageLatest ) {
		$revisionId = $pageLatest;

		if ( !$revisionId ) {
			// if a page is a redirect then it will not have a good revision
			decho( "no revision for page", $pageId, false );
			return;
		}

		$revision = Revision::newFromId( $revisionId );
		if ( !$revision ) {
			decho( "no revision for page", $pageId, false );
			return;
		}

		// get content of revision
		$content = $revision->getContent( Revision::RAW );

		//get text from content
		$text = ContentHandler::getContentText( $content );

		// cut out the text after the related wikihows section heading
		$cutText = stristr( $text, "== related wikihows ==" );

		// remove the first line (which is the related wikihows header
		if ( !$cutText ) {
			//decho( "no related wikihows for title", $title->getText, false );
			return;
		}

		// split up the lines after this heading so we can get the titles in it
		$relatedLines = explode( PHP_EOL, $cutText );

		// keep track of which lines we want to remove (that point to deindexed titles)
		$linesToRemove = array();
		$titlesToRemove = array();

		// keep track of the first line which is the related wikihows section header in case we need to remove it
		$sectionHeading = null;

		// keep track of total number of related wikihows we find so we can delete the entire section if needed
		$relatedCount = 0;
		foreach ( $relatedLines as $line ) {
			if ( $sectionHeading == null ) {
				$sectionHeading = $line;
				continue;
			}
			// skip blank lines
			if ( !$line ) {
				continue;
			}
			if ( stripos( $line, "__METHODS__" ) !== false ) {
				continue;
			}
			if ( stripos( $line, "__PARTS__" ) !== false ) {
				continue;
			}
			// if we have reached another section heading..then we are done
			if ( strpos( $line, "==" ) !== false ) {
				break;
			}

			$removeTitle = $this->getTitleFromWikitext( $line );

			// it's possible for other data to appear if the related section is the last one
			// so skip any lines where we cannot determine a title
			if ( !$removeTitle ) {
				//decho( "no title in article: $pageId for line", $line, false );
				continue;
			}

			$relatedCount++;

			// the main part of this code..check if the title is deindexed
			$deindexed = $this->isDeindexed( $removeTitle );
			if ( $deindexed ) {
				// store the titles for logging later
				$titlesToRemove[] = $removeTitle;

				//store the lines to remove
				$linesToRemove[] = $line;
			}
		}

		$title = Title::newFromID( $pageId );
		if ( $this->getOption( 'debug' ) ) {
			$url = wfExpandUrl( Misc::getLangBaseURL( 'en' ) . $title->getLocalURL(), PROTO_CANONICAL );
			foreach( $titlesToRemove as $t ) {
				$turl = wfExpandUrl( Misc::getLangBaseURL( 'en' ) . $t->getLocalURL(), PROTO_CANONICAL );
				//echo( $url .": ".$turl."\n" );
				echo( $t->getArticleID()."\n" );
			}
		}

		$this->removeLinesAndSave( $title, $text, $linesToRemove, $sectionHeading, $relatedCount );
	}

	private function updateArticles() {
		$dbr = wfGetDB( DB_SLAVE );

		// select the articles from the index info table so we only choose indexed articles
		$conditions = array( 
			'ii_page = page_id', 
			'ii_page = gr_page',
			'gr_rev = page_latest',
			'ii_policy' => RobotPolicy::POLICY_DONT_CHANGE,
			'page_is_redirect' => 0,
		);

		$pageId = $this->getOption( 'page' );
		if ( $pageId ) {
			$conditions['ii_page'] = $pageId;
		}

		$limit = $this->getOption( 'limit', 5 );
		$options = array( 'LIMIT' => $limit );

		$rows = DatabaseHelper::batchSelect(
			array( 'index_info', 'page', 'good_revision' ),
			array( 'ii_page', 'page_latest'),
			$conditions,
			__METHOD__,
			$options,
			DatabaseHelper::DEFAULT_BATCH_SIZE,
			$dbr
		);

		foreach ( $rows as $row ) {
			$pageId = $row->ii_page;
			$pageLatest = $row->page_latest;
			if ( $pageId == 5 ) {
				continue;
			}
			$relateds = $this->removeDeindexedRelatedWikihows( $pageId, $pageLatest );
		}
	}

}


$maintClass = "UpdateRelatedWikihows";
require_once RUN_MAINTENANCE_IF_MAIN;

