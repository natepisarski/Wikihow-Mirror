<?php

require_once __DIR__ . '/../../commandLine.inc';
require_once "$IP/extensions/wikihow/titus/Titus.class.php";

$titus = new TitusDB(true);

$stats = TitusConfig::getDailyEditStats(); 
$stats['RobotPolicy'] = 0;
$stats['Social'] = 0;
$dbr = DatabaseBase::factory('mysql');
$dbr->open(TitusDB::getDBHost(), WH_DATABASE_MAINTENANCE_USER, WH_DATABASE_MAINTENANCE_PASSWORD, TitusDB::TITUS_DB_NAME);

$sql = "select ti_page_id from titus_intl where ti_language_code='$wgLanguageCode' AND ti_num_steps=0";
$res = $dbr->query($sql);

$ids = array();
foreach($res as $row) {
	$ids[] = $row->ti_page_id;	
}
$batch = array();
foreach($ids as $id) {
	$batch[] = $id;
	if(sizeof($batch) == 999) {
		$titus->calcStatsForPageIds($stats,$batch);
		$batch = array();
	}
}
$titus->calcStatsForPageIds($stats,$batch);

