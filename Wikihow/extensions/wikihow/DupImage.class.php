<?php

/*
CREATE TABLE `images_sha1` (
  `is_sha1` varchar(255) NOT NULL,
  `is_page_id` int(10) unsigned NOT NULL,
  `is_page_title` varchar(255) NOT NULL,
  `is_updated` varchar(14) NOT NULL,
  PRIMARY KEY (`is_sha1`),
  KEY `is_page_id` (`is_page_id`)
);
 */

class DupImage {

	/**
	 * Get database handle for reading or writing
	 */
	private static function getDB($type) {
		static $dbw = null, $dbr = null;
		if ('read' == $type) {
			if (!$dbr) $dbr = wfGetDB(DB_REPLICA);
			return $dbr;
		} elseif ('write' == $type) {
			if (!$dbw) $dbw = wfGetDB(DB_MASTER);
			return $dbw;
		} else {
			throw new Exception('bad db type');
		}
	}

	/**
	 * Add a dup image sha1 hash to images_sha1 table.
	 */
	public static function addDupImage($filename, $mwName) {
		$dbw = self::getDB('write');
		$title = Title::newFromText($mwName, NS_IMAGE);
		if (!$title) return false;

		$contents = @file_get_contents($filename);
		if (!$contents) return false;

		$sha1 = sha1($contents);
		$page_title = $title->getDBkey();
		$page_id = $title->getArticleID();
		$now = wfTimestampNow();
		$row = array(
			'is_sha1' => $sha1,
			'is_page_id' => $page_id,
			'is_page_title' => $page_title,
			'is_updated' => $now);
		$dbw->replace('images_sha1', 'is_sha1', $row, __METHOD__);

		return true;
	}

	/**
	 * Check to see if an image was already uploaded for wikiphoto
	 */
	public static function checkDupImage($filename) {
		$dbr = self::getDB('read');
		$contents = @file_get_contents($filename);
		if ($contents) {
			$sha1 = sha1($contents);
			$db_title = $dbr->selectField('images_sha1',
				'is_page_title',
				array('is_sha1' => $sha1),
				__METHOD__);
			if ($db_title) {
				$title = Title::newFromDBkey('Image:' . $db_title);
				if ($title && $title->exists()) {
					$file = wfFindFile($title);
					if ($file) {
						$path = $file->getPath();
						if ($path && @file_exists($path)) {
							$contents = @file_get_contents($path);
							if ($contents) {
								$sha1_orig = sha1($contents);
								if ($sha1_orig == $sha1) {
									return $title->getText();
								}
							}
						}
					}
				}
			}
		}
		return '';
	}
}
