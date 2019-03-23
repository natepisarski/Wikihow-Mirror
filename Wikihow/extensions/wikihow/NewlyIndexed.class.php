<?php

/****************
 *
 * This class controls the articles that will
 * be put into the good_new_pages table.
 * This table will then be used to create a
 * secondary sitemap which will include articles
 * that are post-nab and have been indexed recetly.
 *
 ***************/

$wgHooks['NABMarkPatrolled'][] = 'NewlyIndexed::onMarkPatrolled';
$wgHooks['TitusRobotPolicy'][] = 'NewlyIndexed::onMarkIndexed';

class NewlyIndexed {

	const TABLE_NAME	= "good_new_pages";
	const NAB_FIELD		= "gnp_nab";
	const INDEX_FIELD	= "gnp_index";
	const PAGE_FIELD	= "gnp_page";

	public static function onMarkIndexed($title, $status) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		if (!$title) {
			return true;
		}

		$date = wfTimestamp(TS_MW);

		$indexed = $dbr->selectField(NewlyIndexed::TABLE_NAME, NewlyIndexed::INDEX_FIELD, array(NewlyIndexed::PAGE_FIELD => $title->getArticleID()), __FUNCTION__);

		if ($indexed === false) {
			//row doesn't exist yet
			if ($status == RobotPolicy::POLICY_INDEX_FOLLOW_STR) {
				//has now been indexed, so add the new row
				$dbw->insert(NewlyIndexed::TABLE_NAME, array(NewlyIndexed::PAGE_FIELD => $title->getArticleID(), NewlyIndexed::INDEX_FIELD => $date, NewlyIndexed::NAB_FIELD => 0), __METHOD__);
			}
		} else {
			if ($status != RobotPolicy::POLICY_INDEX_FOLLOW_STR) {
				$date = 0;
			}
			//either its already index and needs to be be-indexed OR it hasn't been indexed and now it is
			if ($indexed > 0 && $date == 0 || $indexed == 0 && $date > 0 )
				$dbw->update(NewlyIndexed::TABLE_NAME, array(NewlyIndexed::INDEX_FIELD => $date), array(NewlyIndexed::PAGE_FIELD => $title->getArticleID()), __METHOD__);
		}

		return true;
	}

	public static function onMarkPatrolled($articleId) {
		if ($articleId <= 0) {
			return true;
		}

		$articleId = (int)$articleId;
		$dbw = wfGetDB(DB_MASTER);

		$sql = "INSERT INTO " . NewlyIndexed::TABLE_NAME;
		$sql .= " (" . NewlyIndexed::PAGE_FIELD . ", " . NewlyIndexed::NAB_FIELD . ", " . NewlyIndexed::INDEX_FIELD . ")";
		$sql .= " values ({$articleId}, 1, '0') ON DUPLICATE KEY UPDATE " . NewlyIndexed::NAB_FIELD . " = 1";
		$dbw->query($sql, __METHOD__);

		return true;
	}
}

/******
 CREATE TABLE IF NOT EXISTS `good_new_pages` (
  `gnp_page` int(10) unsigned NOT NULL,
  `gnp_nab` tinyint(3) unsigned NOT NULL,
  `gnp_index` varchar(14) collate utf8_unicode_ci NOT NULL,
  UNIQUE KEY `gnp_page` (`gnp_page`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
********/
