<?php

require_once __DIR__ . '/../Maintenance.php';

class GetHelpfulnessData extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "get helpfulness data for page id";
    }

	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$table = "rating_reason";
		$vars = array('ratr_rating', 'ratr_text');

		"select ratr_page_id, ratr_rating, ratr_textwhere ratr_page_id  in";

		while (false !== ($line = fgets(STDIN))) {
			$id = trim($line);
			$name = Title::nameOf( $id );
			if (!$name) {
				decho("no title for input", $id, false);
				continue;
			}
			$cond = array('ratr_page_id' => $id);
			$res = $dbr->select( $table, $vars, $cond, __METHOD__);
			foreach ( $res as $row ) {
				echo $id;
				echo ", ";
				echo $name;
				echo ", ";
				echo $row->ratr_rating;
				echo ", ";
				echo trim( $row->ratr_text );
				echo "\n";
			}
		}
	}
}

$maintClass = "GetHelpfulnessData";
require_once RUN_MAINTENANCE_IF_MAIN;

