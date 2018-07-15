<?php

/**
 * This class is designed to take a large select statement and perform
 * in batches to decrease the load on the db. 
 */

class DatabaseHelper {
	
	const DEFAULT_BATCH_SIZE = 2000;		//number of records to pull at once
	const SLEEPTIME = 500000;	//measured in microseconds = .5 seconds
	
	/**
	 *
	 * Essentially a wrapper for the database select function. Accepts the same parameters
	 * as Database::select, with an additional optional parameters for the batch size and dbr.
	 * Function selects rows in batches of DEFAULT_BATCH_SIZE and sleeps for SLEEPTIME 
	 * microseconds between each batch. All rows are returned in an array of row objects.
	 * 
	 */
	public static function batchSelect($table, $fields, $conditions = '', $fname = __METHOD__, $options = array(), $batchSize = self::DEFAULT_BATCH_SIZE, $dbr = null) {
		if (is_null($dbr)) {
			$dbr = wfGetDB(DB_SLAVE);
		}
		
		if( !is_array( $options ) ) {
			$options = array( $options );
		}

		// don't over-write input $options['LIMIT'] (it's nice to use in testing)
		$customLimit = isset($options['LIMIT']) ? $options['LIMIT'] : 0;
		if (!$customLimit || $customLimit > $batchSize) {
			$options['LIMIT'] = $batchSize;
		}
		
		$rows = array();
		$batchNum = 0;

		while (true) {
			$options['OFFSET'] = $batchNum*$batchSize;
			$res = $dbr->select($table, $fields, $conditions, $fname, $options);

			if (!$res || $res->numRows() == 0) break;

			foreach ($res as $row) {
				$rows[] = $row;
			}

			if ($customLimit && count($rows) > $customLimit) break;

			$res->free();

			usleep(self::SLEEPTIME);
			$batchNum++;
		}
		
		return $rows;
	}

}

