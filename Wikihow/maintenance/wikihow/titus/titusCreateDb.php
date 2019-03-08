<?php

require_once(__DIR__ . '/../../commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

$titus = new TitusDB(true);

// To recalculate just the SuperTitus fields after gdocs connection failure:
$allStats = TitusConfig::getBasicStats();
$allStats['Top10k'] = 1;
$allStats['Ratings'] = 1;
$allStats['LastFellowEdit'] = 1;

// To recalculate all fields, except robots which is done independently
// with a different, continuous script
//$allStats = TitusConfig::getAllStats();

$titus->calcStatsForAllPages($allStats);
