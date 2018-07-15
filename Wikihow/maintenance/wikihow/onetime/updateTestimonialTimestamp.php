<?php

require_once __DIR__ . '/../../commandLine.inc';

$res = DatabaseHelper::batchSelect([UserReview::TABLE_CURATED, UserReview::TABLE_SUBMITTED], ['us_id', 'uc_submitted_id', 'us_submitted_timestamp'], ['uc_submitted_id = us_id'], __FILE__);

$dbw = wfGetDB(DB_MASTER);
$i = 0;
foreach($res as $row) {
	$dbw->update(UserReview::TABLE_CURATED, ['uc_timestamp' => $row->us_submitted_timestamp], ['uc_submitted_id' => $row->uc_submitted_id], __FILE__);
	$i++;
	if($i%500 == 0) {
		usleep(500);
	}
}