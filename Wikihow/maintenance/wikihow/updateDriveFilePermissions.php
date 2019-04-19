<?php
/**
 * move a google drive file to another folder
 *
 */

require_once __DIR__ . '/../Maintenance.php';

/**
 * Maintenance script that moves google drive files to a new folder
 *
 */
class UpdateDriveFilePermissions extends Maintenance {
	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		global $IP;
		require_once("$IP/extensions/wikihow/socialproof/CoauthorSheets/CoauthorSheetTools.php");
		$this->updatePermissions();
	}

	private function updatePermissions() {
		$tools = new CoauthorSheetTools();
		$done = false;
		$oneTime = true;
		while ( $done == false ) {
			$count = $tools->fixPermissions(500);
			decho( "fixed file count", $count, false );
			if ( $oneTime || $count < 1 ) {
				$done = true;
			}
			usleep(30000);
		}
	}
}

$maintClass = "UpdateDriveFilePermissions";
require_once RUN_MAINTENANCE_IF_MAIN;

