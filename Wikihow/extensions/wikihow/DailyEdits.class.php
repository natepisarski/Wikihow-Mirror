<?php
/*
 * Contains a static method to store pages that have been edited or deleted for a given day.
 * See maintenance/trimDailyEditsTable.php for maintenance of the table
 *
 * IMPORTANT:  This class should be included after GoodRevision.class.php to ensure a last good
 * revision id is present when consumers of this table attempt to use it
 */

/** db schema:
daily_edits | CREATE TABLE `daily_edits` (
  `de_page_id` int(8) unsigned NOT NULL,
  `de_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
  `de_edit_type` int(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`de_page_id`,`de_edit_type`),
  KEY `de_timestamp` (`de_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
*/

class DailyEdits {
	const EDIT_TYPE = 1;
	const DELETE_TYPE = 2;
	const MOVE_TYPE = 3;
	const FIX_TYPE = 4;

	/**
	 * We add a column to daily_edits when the good_revision table is corrected
	 */
	public static function onGoodRevisionFixed($aid) {
		try {
			$aid = (int)$aid;
			$ts = wfTimestampNow();
			$type = DailyEdits::FIX_TYPE;
			$sql = "INSERT IGNORE INTO daily_edits (de_page_id, de_timestamp, de_edit_type) VALUES ($aid, '$ts', $type)
					ON DUPLICATE KEY UPDATE de_timestamp = '$ts', de_edit_type = $type";
			$dbw = wfGetDB(DB_MASTER);
			$dbw->query($sql, __METHOD__);
		} catch(Exception $e) {
			return false;
		}

		return true;
	}

	public static function onTitleMoveComplete(&$ot, &$nt, &$user, $oldid, $newid) {
		// $nt represents the original article, aid with a new title. See hook documentation for more info
		if ($nt && $nt->exists() && $nt->inNamespace(NS_MAIN)) {
			try {
				$aid = $nt->getArticleId();
				$ts = wfTimestampNow();
				$type = DailyEdits::MOVE_TYPE;
				$sql = "INSERT IGNORE INTO daily_edits (de_page_id, de_timestamp, de_edit_type) VALUES ($aid, '$ts', $type)
					ON DUPLICATE KEY UPDATE de_timestamp = '$ts', de_edit_type = $type";
				$dbw = wfGetDB(DB_MASTER);
				$dbw->query($sql, __METHOD__);
			} catch(Exception $e) {}
		}
		return true;
	}

	public static function onMarkPatrolledDB(&$rcid, &$article) {
		if ($article) {
			$t = $article->getTitle();
			if ($t && $t->exists() && $t->inNamespace(NS_MAIN)) {
				try {
					$aid = $t->getArticleId();
					$ts = wfTimestampNow();
					$type = DailyEdits::EDIT_TYPE;
					$sql = "INSERT IGNORE INTO daily_edits (de_page_id, de_timestamp, de_edit_type) VALUES ($aid, '$ts', $type)
						ON DUPLICATE KEY UPDATE de_timestamp = '$ts', de_edit_type = $type";
					$dbw = wfGetDB(DB_MASTER);
					$dbw->query($sql, __METHOD__);
				} catch(Exception $e) {}
			}
		}
		return true;
	}

	public static function onArticleDeleteComplete($wikiPage, $user, $reason, $aid) {
		try {
			$dbw = wfGetDB(DB_MASTER);
			$ts = wfTimestampNow();

			// Then add a delete entry
			$type = DailyEdits::DELETE_TYPE;
			$sql = "INSERT IGNORE INTO daily_edits (de_page_id, de_timestamp, de_edit_type) VALUES ($aid, '$ts', $type)
				ON DUPLICATE KEY UPDATE de_timestamp = '$ts', de_edit_type = $type";
			$dbw->query($sql, __METHOD__);
		} catch(Exception $e) {}
		return true;
	}

	public static function onArticleUndelete($t, $create) {
		if ($t && $t->getArticleId() > 0  && $t->inNamespace(NS_MAIN)) {
			try {
				$dbw = wfGetDB(DB_MASTER);
				$aid = $t->getArticleId();
				$ts = wfTimestampNow();

				// then add an edit entry
				$type = DailyEdits::EDIT_TYPE;
				$sql = "INSERT IGNORE INTO daily_edits (de_page_id, de_timestamp, de_edit_type) VALUES ($aid, '$ts', $type)
					ON DUPLICATE KEY UPDATE de_timestamp = '$ts', de_edit_type = $type";
				$dbw->query($sql, __METHOD__);
			} catch(Exception $e) {}
		}
		return true;
	}

	public static function onQuickSummaryEditComplete($summary_page, $main_title) {
		if ($main_title && $main_title->exists() && $main_title->inNamespace(NS_MAIN)) {
			try {
				$aid = $main_title->getArticleId();
				$ts = wfTimestampNow();
				$type = DailyEdits::EDIT_TYPE;
				$sql = "INSERT IGNORE INTO daily_edits (de_page_id, de_timestamp, de_edit_type) VALUES ($aid, '$ts', $type)
					ON DUPLICATE KEY UPDATE de_timestamp = '$ts', de_edit_type = $type";
				$dbw = wfGetDB(DB_MASTER);
				$dbw->query($sql, __METHOD__);
			} catch(DBError $e) {
				//whoops! Oh, well. Don't sweat the little stuff. #YOLO
			}
		}
		return true;
	}
}
