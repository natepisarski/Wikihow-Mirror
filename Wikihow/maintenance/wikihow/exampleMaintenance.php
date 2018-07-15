<?php
//
// An example maintenance script. You can copy for new maintenance scripts.
//

require_once __DIR__ . '/../Maintenance.php';

/** 
 * This class does ...
 */
class ExampleMaintenance extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Example one line description of this script";
    }
	
	/** 
	 * 
	 */
	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
	}
}

$maintClass = "ExampleMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;

