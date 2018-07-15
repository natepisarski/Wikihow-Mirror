<?php
// this script will update the db with data from the expert verification spreadsheet
// which can also be run from the page /Special:AdminSocialProof

require_once __DIR__ . '/../Maintenance.php';

class UpdateExpertVerified extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Update the expert verified data from the google spreadsheet";
    }

	public function execute() {
		global $IP;
		require_once( "$IP/extensions/wikihow/socialproof/ExpertVerifyImporter.php" );
		$sp = new ExpertVerifyImporter();
		$result = $sp->getSpreadsheet();
		$rows = count( $result['imported'] );
		echo( "successfully imported $rows rows\n" );
		echo("Warnings: ".implode( "\n", $result['warnings'])."\n" );
		echo("Errors: ".implode( "\n", $result['errors'])."\n" );
	}
}

$maintClass = "UpdateExpertVerified";
require_once RUN_MAINTENANCE_IF_MAIN;

