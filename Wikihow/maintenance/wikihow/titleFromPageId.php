<?php

require_once __DIR__ . '/../Maintenance.php';

// print out title for a given page
// cat ~/pageids  | php titleFromPageId.php | paste -s -d','
class TitleFromPageId extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "get title for page id";
    }

	public function execute() {
		while (false !== ($line = fgets(STDIN))) {
			$pageId = trim( $line );
			$title = Title::newFromID( $pageId );
			if (!$title) {
				echo("title does not exist: ". $line);
				continue;
			}
			$linksTo = $title->getLinksTo();
			foreach( $linksTo as $link ) {
				decho("linked from", $link, false );
			}
			echo "\n";
		}
	}
}


$maintClass = "TitleFromPageId";
require_once RUN_MAINTENANCE_IF_MAIN;

