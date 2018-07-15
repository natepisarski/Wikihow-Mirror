<?php

//TODO: 
// 1. move functions from here in to LDao.php.
// 2. Accept __METHOD__ as a parameter

class DbUtils {

	const GROUP_CONCAT_MAX_LEN = 10000;
	
// 	private static function exDb($query, $DB) {
// 		$db = wfGetDB($DB);
// 		return $db->query($query, __METHOD__);
// 	}
	
	public static function exDbR($query) {
		$dbr = self::getDbr();
		$dbr->query("SET group_concat_max_len=". self::GROUP_CONCAT_MAX_LEN, __METHOD__);
		return $dbr->query($query, __METHOD__);
	}

	public static function exDbW($query) {
		$dbw = self::getDbw();
		return $dbw->query($query, __METHOD__);
	}
	
	public static function getDbr() {
		return wfGetDB(DB_SLAVE);
	}
	
	public static function getDbw() {
		return wfGetDB(DB_MASTER);
	}
}