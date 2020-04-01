<?php

// Not used? (Alberto, 2019-02)

//define('WH_USE_BACKUP_DB', true);

require_once __DIR__ . '/../Maintenance.php';

class ReverificationMaintenanceScript extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Weekly job to update the Reverification tool queue.";
	}

	public function execute() {
		$maintenance = new ReverificationMaintenance();
		$maintenance->updateQueue();
	}
}

$maintClass = 'ReverificationMaintenanceScript';
require_once RUN_MAINTENANCE_IF_MAIN;


