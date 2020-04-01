<?php

require_once __DIR__ . '/../commandLine.inc';

/* schema:
CREATE TABLE qc_archive LIKE qc;
CREATE TABLE qc_vote_archive LIKE qc_vote;
 */

$dbw = wfGetDB(DB_MASTER);

// rcpatrol QC items enter into the QC queue faster than they can be QC patrolled.  Automatically qc patrol qc rcpatrol items older than 2
// weeks so they can be archived below
$timestamp = wfTimestamp(TS_MW, time() - 60 * 60 * 24 * 14 );

echo "==============================\n";
echo "Mark patrolled qc and qc_vote items that are older than $timestamp \n";
// Clean up stale/old qc items. This happens if the community can't keep up with the qc queue. We clean these out to make sure
// QC queries perform well
$dbw->update('qc', array('qc_patrolled' => 1), array("qc_timestamp < '$timestamp'", "qc_patrolled" => 0));

echo "Archive qc and qc_vote items that are marked patrolled\n";
// Grab all the qc items and associate qc votes, put them in the respective archive tables and then delete items
// Do it in batches of 1000 to not overload the database
$count = 0;
do {
	$res = $dbw->select('qc', array('*'), array("qc_patrolled" => 1), __FILE__, array("LIMIT" => 1000));
	$qcRows = array();
	$qcVoteRows = array();
	$qcIds = array();
	$moreRows = false;
	while($row = $dbw->fetchObject($res)) {
		$moreRows = true;
		$qcRows[] = get_object_vars($row);
		$qcIds[] = $row->qc_id;
		$count++;
	}

	if (sizeof($qcIds)) {
		$res1 = $dbw->select('qc_vote', array('*'), array(joinClause("qcv_qcid", $qcIds)), __FILE__);
		foreach ($res1 as $row) {
			$qcVoteRows[] = get_object_vars($row);
		}
	}
	archiveQC($dbw, $qcRows);
	archiveQCVote($dbw, $qcVoteRows);
	removeQCItems($dbw, $qcIds);
} while ($moreRows);


echo "FINISHED archive: " . $count . " qc items archived\n";
function archiveQC(&$dbw, &$qcRows) {
	if (is_array($qcRows) && sizeof($qcRows)) {
		$dbw->insert('qc_archive', $qcRows, 'archiveQC.php::archiveQC');
	}
}

function archiveQCVote(&$dbw, &$qcVoteRows) {
	if (is_array($qcVoteRows) && sizeof($qcVoteRows)) {
		$dbw->insert('qc_vote_archive', $qcVoteRows, 'archiveQC.php::archiveQCVote');
	}
}

function removeQCItems(&$dbw, &$qcIds) {
	if (is_array($qcIds) && sizeof($qcIds)) {
		$dbw->delete('qc', array(joinClause("qc_id", $qcIds)), "archiveQC.php::removeQCItems");
		$dbw->delete('qc_vote', array(joinClause("qcv_qcid", $qcIds)), "archiveQC.php::removeQCItems");
	}
}

function joinClause($field, &$qcIds) {
	return "$field IN (" . join(",", $qcIds) . ") ";
}
