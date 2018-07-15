<?php
//
// Generate tabular data dump for the changes to our meta descriptions
//

require_once __DIR__ . '/../../Maintenance.php';

class GenerateNewMetaDescriptions extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->mDescription = 'Onetime script to generate new meta descriptions along side old ones';
    }

    public function execute() {
		$fp = fopen('pageids.txt', 'r');
		if (!$fp) {
			print "error: could not open pageids.txt in " . getcwd() . "\n";
			exit;
		}

		while (true) {
			$line = fgets($fp);
			if ($line === false) break;
			$pageid = (int)trim($line);
			if (!$pageid) {
				print "error: could not read valid page id from line: $line\n";
				continue;
			}
			$title = Title::newFromID($pageid);
			if (!$title || !$title->exists() || !$title->inNamespace(NS_MAIN)) {
				print "error: page title for main-namespace page not found, looked up by page id $pageid\n";
				continue;
			}

			// meta description
			$ami = new ArticleMetaInfo($title);
			if ($ami) {
				$current = $ami->getDescription();
				$new = $ami->genNewLongerDescription();
			} else {
				print "error: unable to load ArticleMetaInfo object for page id $pageid\n";
				continue;
			}

			print "$pageid\t$current\t$new\n";
		}
    }
}

$maintClass = 'GenerateNewMetaDescriptions';
require_once RUN_MAINTENANCE_IF_MAIN;
