<?php
global $IP;
require_once __DIR__ . '/../../commandLine.inc';
require_once ("$IP/extensions/wikihow/titus/Titus.class.php");

$dbr = wfGetDB(DB_SLAVE);

$statsToCalc = TitusConfig::getOtherStats();
$titus = new TitusDB(true);
$titus->calcStatsForAllPagesWithOutput($statsToCalc);

