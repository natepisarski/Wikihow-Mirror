<?php
require_once __DIR__ . '/../Maintenance.php';


class ReverificationMaintenanceUpdateSpreadsheet extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Update the verifications spreadsheet with latest reverifications.";
	}

	public function execute() {

		try {
			$updater = new ReverificationSpreadsheetUpdater();
			$updater->update();
		} catch (Exception $e) {
			echo "Ooops. Got an exception:\n";
			echo $e->getMessage() . "\n";
			echo $e->getTraceAsString() . "\n";
			echo "Restarting the updater:\n";
			// retry the updater one more time
			$updater = new ReverificationSpreadsheetUpdater();
			$updater->update();
		}

	}
}

$maintClass = 'ReverificationMaintenanceUpdateSpreadsheet';
require_once RUN_MAINTENANCE_IF_MAIN;


