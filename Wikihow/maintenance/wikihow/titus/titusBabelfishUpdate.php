<?php

require_once(__DIR__ . '/../../commandLine.inc');

$titus = new TitusDB(true);
$allStats['PageId'] = 1;
$allStats['Title'] = 1;
$allStats['Timestamp'] = 1;
$allStats['LanguageCode'] = 1;
$allStats['BabelfishData'] = 1;
$titus->calcStatsForAllPages($allStats);
