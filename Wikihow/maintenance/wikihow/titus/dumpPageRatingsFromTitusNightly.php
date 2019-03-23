<?php
//
// dumps the latest page rating data from titus
//

require_once(__DIR__ . '/../../Maintenance.php');

/**
 * Dumps page rating data from titus
 */
class DumpPageRatingsFromWikiLogNightlyMaintenance extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Dump page helpfulness ratings from helpfulnes table to page tables";
		$this->addOption( 'outfile', 'The filename for the flat page view output', true, true, 'o' );
    }

	/**
	 * Run the maintenance task to do the data dumping
	 */
	public function execute() {
		global $wgLanguageCode, $IP;

		require_once("$IP/extensions/wikihow/titus/Titus.class.php");

		$titus = new TitusDB();
		$dbr = $titus->getTitusDB();

		// Select all page view data from the wiki_log database on titus server
		$res = $dbr->select('titus_intl',
				array('ti_page_id as pageid', 'ti_helpful_percentage as rating', 'ti_helpful_total as count'),
				array('ti_language_code' => $wgLanguageCode),
			__METHOD__);

		// Read all the pageid and page view count data from table
		$outFilename = $this->getOption('outfile');
		$fp = fopen($outFilename, 'wb');
		if ($fp == false) {
			decho("could not open file for writing. aborting",false, false);
			exit();
		}

		$dbr = wfGetDB(DB_REPLICA);

		foreach ($res as $row) {
			$allTimeVotes = $dbr->selectField('rating', 'count(*)', array('rat_page' => $row->pageid));
			if ( $row->pageid && $row->rating != null ) {
				$output = implode( " ", array( $row->rating?:0, $row->count?:0, $allTimeVotes?:0, $row->pageid ) );
				fwrite($fp, "$output\n");
			}
		}
		fclose($fp);
	}
}

$maintClass = "DumpPageRatingsFromWikiLogNightlyMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;

