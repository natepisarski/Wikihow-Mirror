<?php
/**
 create table user_contribution_snapshot (
	ucs_user int,
	ucs_day varchar(8),
	ucs_count int,
	primary key(ucs_user,ucs_day) 
);
 */
require_once __DIR__ . '/../commandLine.inc';
class ContributionSnapshot {

	public static function snapshot() {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "insert ignore into flavius.user_contribution_snapshot(ucs_user,ucs_count,ucs_day) select user_id as ucs_user, user_editcount as ucs_count,replace(substring(now(),1,11),'-','') as ucs_day from wiki_shared.user";
		$dbw->query($sql, __METHOD__);
	}

	public static function cleanSnapshots() {
		$dbw = wfGetDB(DB_MASTER);
		$sql = "delete from flavius.user_contribution_snapshot where ucs_day < date_sub(now(), interval 90 day)";	
		$dbw->query($sql, __METHOD__);
	}

	public static function run() {
		print wfTimestampNow() . " --- taking snapshot of user_contributions on " . "\n";
		self::snapshot();	
		print wfTimestampNow() . " --- preparing to clean old snapshots\n";
		self::cleanSnapshots();
		print wfTimestampNow() . " --- removed old snapshots\n";
	}

}

ContributionSnapshot::run();
