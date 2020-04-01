<?php
//
// With the move to Fastly we can no longer rely on the old varnish method of
// counting visits. So we're using the data that we're already getting
// (indirectly) from the fastly logs to increment the page.page_counter field.
// This will happen once a day and this script must be called AFTER the flat
// input file with the page views exists. This script reads this flat file
// and runs them as update statements against the database.
//
// Note: this should only update NS_MAIN and NS_USER pages, but can do
// anything based on the pageids in the flat input file.
//

require_once __DIR__ . '/../Maintenance.php';

class UpdatePageCountersNightlyMaintenance extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Update the page.page_counters columns with new page views from previous day";
		$this->addOption( 'infile', 'The filename for the flat page view input', true, true, 'i' );
    }

	public function execute() {
		$dbw = wfGetDB(DB_MASTER);

		$inFile = $this->getOption('infile') or die("ERROR: cannot read from file $inFile\n");
		$fp = fopen($inFile, 'r');

		$lines = 0;
		$articleCount = 0;

		while (($line = fgets($fp)) !== false) {
			$lines++;
			list($pageid, $pageCount) = explode(' ', trim($line));
			$dbw->update('page',
				array('page_counter = page_counter + ' . (int)$pageCount),
				array('page_id' => (int)$pageid),
				__FILE__);

			$articleCount += $dbw->affectedRows();
			if ($articleCount > 0 && $articleCount % 1000 == 0) {
				sleep(5);
			}
		}

		fclose($fp);

		print "Read $lines lines from '$inFile', made $articleCount page counter updates.\n";
	}
}

$maintClass = "UpdatePageCountersNightlyMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;

