<?php
require_once(__DIR__ . '/../../commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

$statsToCalc = TitusConfig::getDailyEditStats();
$titus = new TitusDB(true);
/*
$titus->calcStatsForAllPages($statsToCalc);*/
$ids = array(2053);
$titus->calcStatsForPageIds($statsToCalc, $ids);

/*$dailyEditStats = TitusConfig::getDailyEditStats();
$titus->calcLatestEdits($dailyEditStats);*/
