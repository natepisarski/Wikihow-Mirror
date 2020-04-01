<?php
/**
 * moves a title by page id to a new name..does very little error checking
 * other than the page id must exist and the new title must not be taken already
 * it does not create a redirect.
 * it was written to handle the case where the original title has how to at the beginning
 * of the actual title text and therefore cannot be loaded with Title::newFromText
 *
 * run it like this:
 * /opt/wikihow/scripts/whrun --user=apache -- MovePageById.php -p 20532 -d "Kiss Someone"
 *
 */

require_once __DIR__ . '/../Maintenance.php';

/**
 * Maintenance script that will look at the related wikihows of an article
 * and remove them if they are pointing to non indexed articles
 *
 */
class MovePageById extends Maintenance {
	public function __construct() {
		parent::__construct();
        $this->mDescription = "move a title with source page id and destination title text";
		$this->addOption( 'destination', 'name of resulting page', true, true, 'd' );
		$this->addOption( 'page', 'id if page to rename', true, true, 'p' );
	}

	private function movePage() {
		$pageId = $this->getOption( 'page' );
		$newText = $this->getOption( 'destination' );

		// make sure newText is not already a title
		$newTitle = Title::newFromText( $newText );
		if ( $newTitle->exists() ) {
			$this->output( "an article with this title arleady exists\n" );
			exit(1);
		}

		$oldTitle = Title::newFromID( $pageId );

		if ( !$oldTitle || !$oldTitle->exists() ) {
			$this->output( "cannot find a title with pageId $pageId\n" );
			exit(1);
		}

		$oldTitle->moveTo( $newTitle, false, 'force move by script', false );

	}

	public function execute() {
		$this->movePage();
		$this->output( "Done\n" );
	}
}


$maintClass = "MovePageById";
require_once RUN_MAINTENANCE_IF_MAIN;

