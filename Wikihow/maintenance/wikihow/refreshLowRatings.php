<?php

require_once __DIR__ . '/../commandLine.inc';

require_once "$IP/extensions/wikihow/Rating/RatingsTool.php";
require_once "$IP/extensions/wikihow/Rating/RatingSample.php";
require_once "$IP/extensions/wikihow/Rating/RatingArticle.php";

$insertMax = 150;

$dbw = wfGetDB( DB_MASTER );
# we need to set timeouts to be longer just in case we're connected to master.
# Slave has default timeouts of 4 hours; master has default timeouts of 5 minutes
$dbw->query('SET SESSION wait_timeout=3600', __FILE__);
$dbw->query('SET SESSION net_read_timeout=3600', __FILE__);
$dbw->query('SET SESSION net_write_timeout=3600', __FILE__);
$dbw->query('SET SESSION interactive_timeout=3600', __FILE__);

$dbr = wfGetDB( DB_REPLICA );
$avg = wfMessage('list_bottom_rated_pages_avg');
$minvotes = wfMessage('list_bottom_rated_pages_min_votes');
$cleardays = wfMessage('list_bottom_rated_pages_clear_limit_days');

$ratingTool = new RatingArticle();

$dateDiff = strtotime('now - 1 month');
$res = $dbr->select(
	array($ratingTool->getTableName()),
	array("distinct {$ratingTool->getTablePrefix()}page"),
	array("{$ratingTool->getTablePrefix()}deleted_when > FROM_UNIXTIME(".$dbr->addQuotes($dateDiff).")"),
	__FILE__ . ':newlyDeleted');

$newlyDeletedPages = array();
foreach ($res as $row) {
	$newlyDeletedPages[] = $row->{$ratingTool->getTablePrefix().'page'};
}

$notin = join(',', $newlyDeletedPages);

$conditions = array("{$ratingTool->getTablePrefix()}page = page_id", "{$ratingTool->getTablePrefix()}isdeleted" => 0, 'page_is_redirect' => 0);
if (!empty($notin)) {
	$notin = "{$ratingTool->getTablePrefix()}page NOT IN (".$notin.")";
	$conditions[] = $notin;
}

$dbw->query('DELETE FROM rating_low', __FILE__ . ':delete');

$res = $dbr->select(
	array($ratingTool->getTableName(), 'page'),
	array("{$ratingTool->getTablePrefix()}page",
		"AVG({$ratingTool->getTablePrefix()}rating) as R",
		'count(*) as C'),
	$conditions,
	__FILE__,
	array('GROUP BY' => "{$ratingTool->getTablePrefix()}page"));

$sqlStart = "INSERT INTO {$ratingTool->getLowTableName()} ({$ratingTool->getLowTablePrefix()}page, {$ratingTool->getLowTablePrefix()}avg, {$ratingTool->getLowTablePrefix()}count) VALUES ";
$sql = $sqlStart;
$count = 0;
foreach ($res as $row) {
	if ($row->C >= $minvotes && $row->R <= $avg) {
		if ($count % $insertMax != 0) {
			$sql .= ", ";
		}

		$sql .= "('".$row->{$ratingTool->getTablePrefix().'page'}."', '$row->R', '$row->C')";
		$count++;

		if ($count % $insertMax == 0) {
			$dbw->query($sql, __FILE__);
			$sql = $sqlStart;
		}
	}
}

if ($sql != $sqlStart) {
	$dbw->query($sql, __FILE__);
}

// now refresh the SAMPLE ratings
$ratingTool = new RatingSample();
$res = DatabaseHelper::batchSelect($ratingTool->getTableName(), array('*'), array('rats_isdeleted' => 0), __FILE__);
$newStats = array();
$deletedPages = array();
foreach ($res as $row) {
	if ($row->rats_isdeleted == 0) {
		if (@$newStats[$row->rats_page] == null) {
			$newStats[$row->rats_page] = array("count" => 0, "ratingCount" => 0);
		}
		$newStats[$row->rats_page]["count"]++;
		$newStats[$row->rats_page]["ratingCount"] += $row->rats_rating;
	}
}

// dump the table
$dbw->query("delete from {$ratingTool->getLowTableName()}", __FILE__);

$sqlStart = "INSERT into {$ratingTool->getLowTableName()} ({$ratingTool->getLowTablePrefix()}page, {$ratingTool->getLowTablePrefix()}avg, {$ratingTool->getLowTablePrefix()}count) VALUES ";
$sql = $sqlStart;
$count = 0;
foreach ($newStats as $id => $stats) {
	$avg = $stats['ratingCount'] / $stats['count'];
	$total = intval($stats['count']);
	if ($count % $insertMax != 0) {
		$sql .= ", ";
	}
	$sql .= "(" . $dbw->addQuotes($id) . ", '$avg', '$total')";
	$count++;
	if ($count % $insertMax == 0) {
		$dbw->query($sql, __FILE__);
		$sql = $sqlStart;
	}
}

if ($sql != $sqlStart) {
	$dbw->query($sql, __FILE__);
}

