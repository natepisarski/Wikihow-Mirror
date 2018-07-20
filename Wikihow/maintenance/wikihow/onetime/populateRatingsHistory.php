<?php
/*
 * Populate the table rating_history for the first time, from data in the
 * rating table.
 */

require_once __DIR__ . '/../../Maintenance.php';

class PopulateRatingHistory extends Maintenance {

    public function execute() {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select('rating',
			['rat_page', 'UNIX_TIMESTAMP(MAX(rat_deleted_when)) AS last_reset'],
			['rat_isdeleted' => 1],
			__METHOD__,
			['GROUP BY' => 'rat_page']);
		$count = 0;
		foreach ($res as $row) {
			$pageid = $row->rat_page;
			$lastReset = $row->last_reset;
			$mwLastReset = wfTimestamp(TS_MW, $lastReset);

			$dbw->insert('rating_history',
				['rh_pageid' => $pageid,
				 'rh_timestamp' => $mwLastReset,
				 'rh_source' => 'rating'],
				__METHOD__);
			
			if (++$count % 1000 == 0) {
				$this->output("#");
			}
		}
		$this->output("\n");
	}

}

$maintClass = 'PopulateRatingHistory';
require_once RUN_MAINTENANCE_IF_MAIN;
