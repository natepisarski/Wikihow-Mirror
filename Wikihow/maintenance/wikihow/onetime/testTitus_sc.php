<?php

require_once __DIR__ . '/../../commandLine.inc';

$dbr = wfGetDB(DB_REPLICA);

$statsToCalc = TitusConfig::getOtherStats();
$titus = new TitusDB(true);
$titus->calcStatsForAllPagesWithOutput($statsToCalc);
