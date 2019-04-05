<?php

if (!defined('MEDIAWIKI')) die();

/*
 *db schema:
CREATE TABLE config_storage_history (
	csh_id int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	csh_modified varbinary(14) NOT NULL,
	csh_user_id int unsigned NOT NULL,
	csh_username varbinary(255) NOT NULL,
	csh_key varbinary(64) NOT NULL,
	csh_log_short varbinary(255) NOT NULL,
	csh_log_full longblob
);
 */

class ConfigStorageHistory {

	/**
	 * List all recent history items
	 */
	public static function dbListHistory(int $items = 20) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( 'config_storage_history',
			['csh_id', 'csh_modified', 'csh_username', 'csh_key', 'csh_log_short' ],
			'',
			__METHOD__,
			['LIMIT' => $items, 'ORDER BY' => 'csh_modified DESC'] );
		$rows = [];
		foreach ($res as $row) {
			$rows[] = (array)$row;
		}
		return $rows;
	}

	/**
	 * Get details of a history entry
	 */
	public static function dbGetDetails(int $csh_id) {
		$dbr = wfGetDB(DB_REPLICA);
		$row = (array)$dbr->selectRow( 'config_storage_history',
			['csh_key', 'csh_log_short', 'csh_log_full'],
			['csh_id' => (int)$csh_id],
			__METHOD__ );
		return $row;
	}

	/**
	 * Add new history entry based on item edit
	 */
	public static function dbChangeConfigStorage($key, $oldConfig, $config) {
		$orig = explode("\n", $oldConfig);
		$new = explode("\n", $config);
		$diff = new Diff($orig, $new);
		$formatter = new TableDiffFormatter();
		$formatted = $formatter->format( $diff );

		$summary = "{$userName} changed tag '{$key}': " . self::diffSummary($diff);
		$full = $formatted;

		self::dbAddRow($key, $summary, $full);
	}

	private static function diffSummary($diff) {
		if (!$diff->edits) {
			return "no changes";
		}
		$add = 0;
		$delete = 0;
		$change = 0;
		foreach ($diff->edits as $diffop) {
			$type = $diffop->type;
			if ($type == 'add') {
				$add += count( $diffop->closing );
			} elseif ($type == 'delete') {
				$delete += count( $diffop->orig );
			} elseif ($type == 'change') {
				$change += count( $diffop->orig );
			}

			// we ignore DiffOpCopy types since they represent no change
		}
		if ($add == 0 && $delete == 0 && $change == 0) {
			return "no changes";
		}
		$summary = "$add line(s) added; $change line(s) changed; $delete line(s) deleted";
		return $summary;
	}

	public static function dbDeleteConfigStorage($key, $oldConfig) {
		$summary = "{$userName} deleted tag '{$key}'";
		$full = $summary . ". The contents before being deleted were:\n" . $oldConfig;

		self::dbAddRow($key, $summary, $full);
	}

	private static function dbAddRow($key, $summary, $full) {
		$dbw = wfGetDB(DB_MASTER);

		$user = RequestContext::getMain()->getUser();
		$userID = $user->getID();
		$userName = $user->getName();

		$dbw->insert( 'config_storage_history',
			[ 'csh_modified' => wfTimestampNow(),
			  'csh_user_id' => $userID,
			  'csh_username' => $userName,
			  'csh_key' => $key,
			  'csh_log_short' => $summary,
			  'csh_log_full' => $full,
			],
			__METHOD__ );
	}
}
