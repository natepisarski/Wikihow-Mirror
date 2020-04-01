<?php

require_once __DIR__ . '/../../Maintenance.php'; 	

class UpdateLowercaseUsernames extends Maintenance {
	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$dbw = wfGetDB(DB_MASTER);
		$rows = [];
		$res = $dbw->query("select user_id, user_name, user_editcount from wiki_shared.user where user_name regexp '^[a-z]'", __FILE__);
		foreach ($res as $row) {
			$rows[] = $row;
		}
		print "Fixing " . count($rows) . " rows from user table\n";
		foreach ($rows as $row) {
			$username = $row->user_name;
			$ucname = ucfirst($username);
			$userid = $row->user_id;
			$ec1 = $row->user_editcount;
			$newRow = $dbw->selectRow('user', ['user_id', 'user_editcount'], ['user_name' => $ucname], __FILE__);
			if (!$newRow) {
				$dbw->update('user', ['user_name' => $ucname], ['user_id' => $userid], __FILE__);
			} else {
				$ec2 = $newRow->user_editcount;
				print "DUPLICATE user found; tried to change $username (editcount: $ec1) to $ucname (editcount: $ec2), but $ucname already exists\n";
			}
		}
	}
}

$maintClass = 'UpdateLowercaseUsernames';
require_once RUN_MAINTENANCE_IF_MAIN;
