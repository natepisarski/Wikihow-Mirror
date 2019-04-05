<?php

/**
 * Created by PhpStorm.
 * User: jordan
 * Date: 3/10/17
 * Time: 3:51 PM
 */
class ReverificationDB {
	const TABLE_REVERIFICATIONS = 'reverifications';
	static $db = null;

	private function __construct() {}

	/**
	 * @return ReverificationDB
	 */
	public static function getInstance() {
		$db = null;
		if (is_null(self::$db)) {
			self::$db = new ReverificationDB();
		}

		return self::$db;
	}

	/**
	 * @param ReverificationData[] $reverifications
	 */
	public function insert($reverifications) {
		$dbw = wfGetDB(DB_MASTER);

		$rows = [];
		foreach ($reverifications as $rv) {
			$rows [] = $rv->getDBRow();
		}

		$dbw->insert(
			self::TABLE_REVERIFICATIONS,
			$rows,
			__METHOD__,
			['IGNORE']
		);
	}

	/**
	 * Update an existing reverification row in the database matching ReverificationData::id.
	 *
	 * @param ReverificationData $reverification
	 */
	public function update($reverification) {
		if (empty($reverification) || empty($reverification->getId())) {
			throw new Exception('ReverificationData::id cannot be empty');
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			self::TABLE_REVERIFICATIONS,
			$reverification->getDBRow(),
			['rv_id' => $reverification->getId()],
			__METHOD__
		);
	}

	/**
	 * Get the oldest ReverificationData by username, or null if none found.
	 *
	 * @param string $userName
	 *
	 * @return null|ReverificationData
	 */
	public function getOldestReverification($userName, $olderThan) {
		$dbw = wfGetDB(DB_MASTER);
		$row = $dbw->selectRow(
			[self::TABLE_REVERIFICATIONS, VerifyData::VERIFIER_TABLE],
			'*',
			[
				'vi_user_name' => $userName,
				'rv_new_date = ""',
				"rv_old_date < $olderThan",
				"rv_skip_ts < '" . wfTimestamp(TS_MW,time() - 60 * 60 * 24 * 14) . "'", // not skipped within the past 14 days
			],
			__METHOD__,
			[
				'ORDER BY' => 'rv_old_date',
				'LIMIT' => 1
			],
			[self::TABLE_REVERIFICATIONS => ['LEFT JOIN', 'rv_verifier_id = vi_id']]
		);

		$verifification = null;
		if ($row) {
			$verifification = ReverificationData::newFromDBRow($row);
		}

		return $verifification;
	}

	/**
	 * Get the oldest ReverificationData by username, or null if none found.
	 *
	 * @param string $userName
	 *
	 * @return null|ReverificationData
	 */
	public function getOldestQuickFeedback() {
		$dbw = wfGetDB(DB_MASTER);
		$row = $dbw->selectRow(
			[self::TABLE_REVERIFICATIONS],
			'*',
			[
				'rv_new_date != ""',
				"rv_reverified" => 0,
				"rv_feedback != ''",
				"rv_flag" => 0,
				"rv_skip_ts < '" . wfTimestamp(TS_MW,time() - 60 * 60 * 24) . "'", // not skipped within the past 24 hours
			],
			__METHOD__,
			[
				'ORDER BY' => 'rv_old_date',
				'LIMIT' => 1,
			]
		);

		$verifification = null;
		if ($row) {
			$verifification = ReverificationData::newFromDBRow($row);
		}

		return $verifification;
	}


	public function getById($reverificationId) {
		$dbw = wfGetDB(DB_MASTER);
		$row = $dbw->selectRow(
			self::TABLE_REVERIFICATIONS,
			'*',
			['rv_id' => $reverificationId],
			_METHOD__
		);

		$reverification = null;
		if ($row) {
			$reverification = ReverificationData::newFromDBRow($row);
		}

		return $reverification;
	}


	/**
	 * @param int $reverIds
	 * @param string $ts
	 * @throws Exception
	 */
	public function updateExportTimestamp($reverIds, $ts = null) {
		if (empty($reverIds) || count($reverIds) < 1) {
			return;
		}

		if (is_null($ts)) {
			$ts = wfTimestampNow();
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			self::TABLE_REVERIFICATIONS,
			['rv_export_ts' => $ts],
			['rv_id' => $reverIds],
			__METHOD__
		);
	}

	/**
	 * @return ReverificationData[]
	 */
	public function getExported($from = null, $to = null) {
		$dbw = wfGetDB(DB_MASTER);

		$where = ["rv_new_date != ''"];
		if (!empty($from) && !empty($to)) {
			$where []= "rv_new_date between $from AND $to";
		}

		$rows = $dbw->select(
			self::TABLE_REVERIFICATIONS,
			'*',
			$where,
			_METHOD__
		);

		$reverifications = [];
		foreach ($rows as $row) {
			$reverifications []= ReverificationData::newFromDBRow($row);
		}

		return $reverifications;
	}

	/**
	 * Returns reverification rows that have yet to be updated in the master expert verified
	 * spreadsheet
	 * @return ReverificationData[]
	 */
	public function getScriptExport() {
		$dbw = wfGetDB(DB_MASTER);

		$where = [
			"rv_new_date != ''",
			"rv_script_export_ts = ''",
			"rv_reverified" => 1
		];

		$rows = $dbw->select(
			self::TABLE_REVERIFICATIONS,
			'*',
			$where,
			__METHOD__
		);

		$reverifications = [];
		foreach ($rows as $row) {
			$reverifications []= ReverificationData::newFromDBRow($row);
		}

		return $reverifications;
	}

	/**
	 * Used primarily by RevericationMaintenance.  Remove rows
	 * that users haven't reverified. This should be called before
	 * the queue is updated to prevent duplicate article rows that can occur
	 * because reverifications can happen outside of this tool (in the content portal).
	 */
	public function purgeStaleQueueItems() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(
			self::TABLE_REVERIFICATIONS,
			["rv_new_date = ''"],
			__METHOD__
		);
	}

	/**
	 * Get the verifier name for a given username
	 *
	 * @param $username
	 * @return bool|mixed
	 */
	public function getVerifierName($username) {
		if (empty($username)) {
			return false;
		}

		$dbr = wfGetDB(DB_REPLICA);
		return $dbr->selectField(
			VerifyData::VERIFIER_TABLE,
			'vi_name',
			['vi_user_name' => $username],
			__METHOD__
		);
	}

	/**
	 * Returns the wh username given a verifier name
	 * @param $verifierName
	 * @return bool|string
	 */
	public function getVerifierUsername($verifierName) {
		$u = null;
		if (empty($verifierName)) {
			return false;
		}

		$dbr = wfGetDB(DB_REPLICA);
		return $dbr->selectField(
			VerifyData::VERIFIER_TABLE,
			'vi_user_name',
			['vi_name' => $verifierName],
			__METHOD__
		);
	}
}
