<?php
//
// this script will take data from an input file which corresponds to
// article helpfulness rating and number of votes (dumped from titus nightly) and
// imports it into the local db in the page_rating table


require_once __DIR__ . '/../Maintenance.php';

class UpdatePageRatingsNightlyMaintenance extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Update the page_rating columns with new page ratings from previous day";
		$this->addOption( 'updatefile', 'data to be updated', false, true, 'u' );
		$this->addOption( 'insertfile', 'data to be inserted', false, true, 'i' );
    }

	/* sql for this table
	 CREATE TABLE `page_rating` (
	   `pr_page_id` int(10) unsigned NOT NULL,
	   `pr_count` int unsigned NOT NULL DEFAULT '0',
	   `pr_all_time_count` int unsigned NOT NULL DEFAULT '0',
	   `pr_rating` tinyint(3) unsigned NOT NULL DEFAULT '0',
	   PRIMARY KEY (`pr_page_id`)
	 );
	 */

	public function execute() {
		if ( $this->getOption('updatefile') ) {
			$this->updateExistingRows();
		}
		if ( $this->getOption('insertfile') ) {
			$this->insertNewRows();
		}
	}

	function updateExistingRows() {
		$updateFile = $this->getOption('updatefile');
		$fp = fopen($updateFile, 'r');

		$lines = 0;
		$rowCount = 0;
		$insertCount = 0;

		$fields = array ( 'pr_rating', 'pr_count', 'pr_all_time_count', 'pr_page_id' );

		$dbw = wfGetDB(DB_MASTER);
		while (($line = fgets($fp)) !== false) {
			$lines++;

			$row = array_combine( $fields, array_map('intval', explode(' ', trim( $line ) ) ) );

			$pageId = $row['pr_page_id'];
			$result = $dbw->update('page_rating',
				$row,
				array('pr_page_id' => $pageId),
				__FILE__);

			$affectedRows = $dbw->affectedRows();
			if ($affectedRows == 0) {
				// this is not normal but if the data is missing then try inserting it
				$exists = $dbw->selectField('page_rating', 'count(*)', array('pr_page_id' => $pageId), __FILE__);
				if ($exists < 1) {
					$insertCount += $this->doInsert($row);
					if ($insertCount > 0 && $insertCount % 1000 == 0) {
						sleep(5);
					}
				}
			}
			$rowCount += $affectedRows;
			if ($rowCount > 0 && $rowCount % 1000 == 0) {
				sleep(5);
			}
		}

		fclose($fp);

		print "Read $lines lines from '$updateFile', updated $rowCount page rating rows.\n";
		if ($insertCount > 0) {
			print "(Update function inserted $insertCount page rating rows.)\n";
		}
	}

	function insertNewRows() {
		$insertFile = $this->getOption('insertfile');
		$fp = fopen($insertFile, 'r');
		$lines = 0;
		$rowCount = 0;

		$fields = array ( 'pr_rating', 'pr_count', 'pr_all_time_count', 'pr_page_id' );

		while (($line = fgets($fp)) !== false) {
			$lines++;

			$rows[] = array_combine( $fields, array_map('intval', explode(' ', trim( $line ) ) ) );

			if (sizeof($rows) >= 2000) {
				$rowCount += $this->doInsert($rows);
				$rows = array();
				sleep(3);
			}
		}

		$rowCount += $this->doInsert($rows);

		fclose($fp);

		print "Read $lines lines from '$insertFile', inserted $rowCount new page rating rows.\n";
	}

	function doInsert($rows) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('page_rating',
			$rows,
			__FILE__,
			array( 'IGNORE' ) );
		return $dbw->affectedRows();
	}
}

$maintClass = "UpdatePageRatingsNightlyMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;

