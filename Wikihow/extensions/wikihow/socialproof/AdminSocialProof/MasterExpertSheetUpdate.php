<?php
/**
 * Update for the master expert verified sheet
 *
 */

/*
CREATE TABLE `master_expert_sheet_update` (
	`mesu_running` int(10) unsigned NOT NULL,
	`mesu_stats` blob NOT NULL,
	`mesu_start_time` datetime NOT NULL,
	`mesu_finish_time` datetime NOT NULL
);
 */
class MasterExpertSheetUpdate implements DeferrableUpdate {

	public function __construct() {}

	public static function getCurrentStateFromDB(): array {
		$dbw = wfGetDB( DB_MASTER );
		$running = $dbw->selectRow( 'master_expert_sheet_update', '*', [], __METHOD__ );
		return (array)$running;
	}

	public static function prepareUpdate() {
		$dbw = wfGetDB( DB_MASTER );
		$updateData = [
			'mesu_running' => 1,
			'mesu_stats' => '',
			'mesu_start_time' => gmdate( 'Y-m-d H:i:s' ),
			'mesu_finish_time' => ''
		];
		$dbw->update( 'master_expert_sheet_update', $updateData, [], __METHOD__ );
	}

	public function doUpdate() {
		ini_set('memory_limit', '1024M');
		set_time_limit(300);
		$old_user_abort = ignore_user_abort( true );

		$dbw = wfGetDB( DB_MASTER );

		$coauthorSheet = new CoauthorSheetMaster();
		try {
			$result = $coauthorSheet->doImport();
		} catch (Exception $e) {
			$msg = (string) $e;
			$result = [
				'errors' => [ "<b>CoauthorSheetMaster threw an exception</b>:<br><pre>$msg</pre>" ],
				'warnings' => [],
				'imported' => []
			];
		}

		$errors = count($result['errors']);
		$warnings = count($result['warnings']);
		$lines = count($result['imported']);
		unset($result['imported']);
		$success = "<span class='spa_okay'>Success ($lines lines imported)</span>";

		if ($errors) {
			$result['last_run_result'] = "<span class='spa_error'>Cancelled ($errors errors)</span>";
		} elseif ($warnings) {
			$result['last_run_result'] = "$success <span class='spa_warn'>($warnings warnings)</span>";
		} else {
			$result['last_run_result'] = "$success";
		}

		$result['stats'] = self::getVerifierStats();

		$updateData = array(
			'mesu_running' => 0,
			'mesu_stats' => json_encode( $result ),
			'mesu_finish_time' => gmdate( 'Y-m-d H:i:s' ),
		);

		$dbw->update( 'master_expert_sheet_update', $updateData, array(), __METHOD__ );
		ignore_user_abort( $old_user_abort );
	}

	private static function getVerifierStats() {
		// get the verify data for all pages that have it
		$pages = VerifyData::getAllArticlesFromDB();

		// get the total count
		$total = count( $pages );

		// set up result array
		$counts = array_flip( CoauthorSheetMaster::getWorksheetIds() );
		$counts = array_map( function() { return 0; }, $counts );
		$counts['total'] = $total;

		foreach ( $pages as $verifyData ) {
			$counts[$verifyData->worksheetName]++;
		}
		$text = "";
		foreach ( $counts as $name => $count ) {
            $nameText = wfMessage( 'asp_' . $name )->text();
			$text .= "<b>$count</b> $nameText<br>";
		}

		$elem = Html::rawElement( 'p', array( 'class'=>'sp_stat' ), $text );

		return $elem;
	}
}
