<?php
/**
 * Job Queue class for the maintenance needed for the Reverification tool.
 * This is triggered by a verification spreadsheet import.
 *
 * @file
 * @ingroup JobQueue
 */
class ReverificationMaintenanceJob extends Job {
	public function __construct(Title $targetArticle, $params = false) {
		parent::__construct('ReverificationMaintenanceJob', $targetArticle, $params);
	}

	public function run() {
		$maintenance = new ReverificationMaintenance();
		$maintenance->updateQueue();
	}
}