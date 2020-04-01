<?php
/**
 * Imports scheduled Google Spreadsheets submitted from /Special:QAAdmin
 */

require_once __DIR__ . '/../Maintenance.php';

class QAMaintenanceJob extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Imports scheduled Google Spreadsheets submitted from /Special:QAAdmin';
	}

	public function execute() {
		$maintenance = new QAMaintenance();
		$maintenance->processScheduled();
	}
}

$maintClass = 'QAMaintenanceJob';
require_once RUN_MAINTENANCE_IF_MAIN;

