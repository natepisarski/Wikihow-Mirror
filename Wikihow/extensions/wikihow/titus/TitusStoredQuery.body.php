<?php

/**
 * Allows fetching Titus queries stored in the database from permanent URLs.
 */
 /*
 CREATE TABLE `stored_query` (
  `sq_uuid` binary(36) NOT NULL,
  `sq_query` varbinary(255) NOT NULL,
  PRIMARY KEY  (`sq_uuid`)
 )
 */

global $IP;
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

class TitusStoredQuery extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('TitusStoredQuery');
	}

	/**
	 * Get the the restricted database for titus stored queries
	 */
	private function getDB() {
		$db = DatabaseBase::factory('mysql');
		$db->open(TitusDB::getDBHost(), WH_DATABASE_USER, WH_DATABASE_PASSWORD, TitusDB::getDBName());
		return $db;
	}


	public function execute($par) {
		global $wgRequest, $wgOut, $wgIsTitusServer, $wgIsDevServer;

		if ( !($wgIsTitusServer || $wgIsDevServer) ) {
			$wgOut->setRobotPolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$uuid = $wgRequest->getVal('queryUUID');
		if ( $uuid ) {
			set_time_limit(600);

			// Fetch query to run
			$db = $this->getDB();
			$res = $db->select('stored_query', array('sq_query'), array('sq_uuid' => $uuid));
			$row = $db->fetchObject($res);
			$query = $row->sq_query;

			// If we have a stored query to run, run it
			if ( $query) {
				$res = $db->query($query, __METHOD__);

				header("Content-Type: text/tsv");
				header('Content-Disposition: attachment; filename="out.xls"');

				$first = true;
				$keys = array();
				foreach ( $res as $row ) {
					if ( $first ) {
						$keys = array_keys(get_object_vars($row));
						print implode($keys, "\t") . "\n";
						$first = false;
					}
					$firstField = true;
					foreach ( $keys as $key ) {
						if ( !$firstField ) {
							print "\t";
						}
						else {
							$firstField = false;
						}
						print $row->$key;
					}
					print "\n";
				}
				exit;
			} else {
				$wgOut->setRobotPolicy('noindex,nofollow');
				$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				return;
			}
		}
	}
}
