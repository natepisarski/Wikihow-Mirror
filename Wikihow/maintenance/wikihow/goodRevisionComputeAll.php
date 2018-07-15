<?php
//
// Call into GoodRevision class to compute the latest good (patrolled) revision
// for each article on the site.
//

require_once __DIR__ . '/../commandLine.inc';

class GoodRevisionRecompute {

	/**
	 * Compute the latest good revisions table for all articles.
	 */
	public static function computeLatestAll() {
		$dbw = wfGetDB(DB_MASTER);
		$one_week_ago = wfTimestamp(TS_MW, time() - 7 * 24 * 60 * 60 );
		$corrected = array();
		
		$updateRevFunc = function ($page_id, $page_title, $rev_id) {
			$title = Title::newFromDBkey($page_title);
			$goodRev = GoodRevision::newFromTitle($title, $page_id);
			if ($goodRev) {
				return $goodRev->updateRev($rev_id, true); 
			} else {
				return false;
			}
		};  
		
		// Clear from good_revision table all the deleted articles, 
		// articles moved to other namespaces and articles 
		// turned into redirects.
		$sql = 'DELETE good_revision FROM good_revision
				LEFT JOIN page ON gr_page = page_id
				WHERE page_is_redirect <> 0 OR
					page_namespace <> 0 OR
					page_title IS NULL';
		$dbw->query($sql, __METHOD__);

		$count = $dbw->affectedRows();
		print __METHOD__ . ": removed " . $dbw->affectedRows() . " non-article rows from good_revision table\n";

		// List all articles patrolled over the last week and 
		// compute good_rev on them
		$sql = 'SELECT page_title, page_id, MAX(rc_id) AS rc_id
				FROM page, recentchanges 
				WHERE page_id = rc_cur_id AND
					page_namespace = 0 AND
					page_is_redirect = 0 AND
					rc_patrolled = 1 AND
					page_touched >= ' . $dbw->addQuotes($one_week_ago) . '
				GROUP BY rc_cur_id';
		$patrolled = array();
		$res = $dbw->query($sql, __METHOD__);
		foreach ($res as $obj) { 
			$patrolled[ $obj->page_id ] = (array)$obj;
		}

		// Store recently patrolled articles with their patrolled revision
		foreach ($patrolled as $row) {
			$rev_id = GoodRevision::getRevFromRC($row['page_id'], $row['rc_id']);
			$updated = $updateRevFunc($row['page_id'], $row['page_title'], $rev_id);
			if ($updated) {
				$corrected[] = $row['page_id'];
			}
		}

		$count = count($corrected);
		print __METHOD__ . ": updated $count recently patrolled articles in good_revision table\n";
		
		// List all articles that haven't been touched in the last week
		// and correct their good revision if need
		$sql = 'SELECT page_title, page_id, page_latest
				FROM page
				WHERE page_namespace = 0 AND
					page_is_redirect = 0 AND
					page_touched < ' . $dbw->addQuotes($one_week_ago);
		$rows = array();
		$res = $dbw->query($sql, __METHOD__);
		foreach ($res as $obj) {
			$rows[] = (array)$obj;
		}

		// Store latest revision of all articles not edited in the last week
		foreach ($rows as $row) {
			if (!isset( $patrolled[ $row['page_id'] ] )) {
				$updated = $updateRevFunc($row['page_id'], $row['page_title'], $row['page_latest']);
				if ($updated) {
					$corrected[] = $row['page_id'];
				}
			}
		}

		print __METHOD__ . ": updated " . (count($corrected) - $count) . " older articles in good_revision table\n";

		// Call out to DailyEdits to let Titus and others know what should be recomputed
		foreach ($corrected as $aid) {
			DailyEdits::onGoodRevisionFixed($aid);
		}

	}

}

GoodRevisionRecompute::computeLatestAll();

