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
class UpdateTechFeedbackSheet extends Maintenance {
	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		global $IP;
		require_once("$IP/extensions/wikihow/socialproof/CoauthorSheets/CoauthorSheetMaster.php");
		$this->updateSheet();
	}

	private function updateSheet() {
        //get the data
        //$sql = 'select stfi_page_id, p.page_title, rr.ratr_text, stfi_rating_reason_id, stfi_timestamp from special_tech_feedback_item, rating_reason as rr, page as p where stfi_feedback_status < 1 and p.page_id = stfi_page_id and rr.ratr_id = stfi_rating_reason_id and stfi_user_id <> "" group by stfi_rating_reason_id having sum(stfi_vote) > 1 limit 1;';
        $dbr = wfGetDB( DB_REPLICA );
        $table = 'special_tech_feedback_item, rating_reason as rr';
        $vars = array( 'stfi_page_id', 'stfi_rating_reason_id', 'stfi_timestamp', 'rr.ratr_text as comment' );
        $conds = array( 'stfi_rating_reason_id = rr.ratr_id', 'stfi_feedback_status < 1', 'stfi_user_id <> ""' );
        $options = array(
            'GROUP BY' => 'stfi_rating_reason_id',
            'HAVING' => array( 'sum(stfi_vote) > 1' ),
            //'LIMIT' => 1
        );
        $res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );

        $updates = array();
        foreach ( $res as $row ) {
            $pageId = $row->stfi_page_id;
            $date = strtotime( $row->stfi_timestamp );
            $comment = $row->comment;
            $ratingReasonId = $row->stfi_rating_reason_id;
            SpecialTechFeedback::sendToSpreadsheet( $pageId, $date, $comment );

            $updates[] = array(
                'stfi_page_id' => $pageId,
                'stfi_rating_reason_id' => $ratingReasonId
            );
        }

        $dbw = wfGetDB( DB_MASTER );
        $table = 'special_tech_feedback_item';
        $values = array( 'stfi_feedback_status' => 1 );
        foreach ( $updates as $conds ) {
            $dbw->update( $table, $values, $conds, __METHOD__ );
        }
        decho("imported items", count( $updates ), false );
	}

}

$maintClass = "UpdateTechFeedbackSheet";
require_once RUN_MAINTENANCE_IF_MAIN;

