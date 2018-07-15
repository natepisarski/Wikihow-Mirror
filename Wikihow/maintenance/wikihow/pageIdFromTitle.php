<?php

require_once __DIR__ . '/../Maintenance.php';

// print out id for a given title
// useful for streaming..for example if you want to transform a list of titles in a txt file (one per line) into a comma separated list (one line) for use
// in the testTitusStat script, do:
// cat ~/titles  | php pageIdFromTitle.php | paste -s -d','
class PageIdFromTitle extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "get page id for title";
    }

	public function execute() {
		while (false !== ($line = fgets(STDIN))) {
			$title = Misc::getTitleFromText( trim( $line ) );
			if (!$title) {
				echo("title does not exist: ". $line);
				continue;
			}
			$id = $title->getArticleID();
			echo $id;
			echo "\n";
		}
	}
}


$maintClass = "PageIdFromTitle";
require_once RUN_MAINTENANCE_IF_MAIN;

