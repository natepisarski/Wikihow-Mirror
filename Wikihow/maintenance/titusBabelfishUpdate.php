<?php
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");
$titus = new TitusDB(true);
$allStats['PageId'] = 1;
$allStats['Title'] = 1;
$allStats['Timestamp'] = 1;
$allStats['LanguageCode'] = 1;
$allStats['BabelfishData'] = 1;
$titus->calcStatsForAllPages($allStats);
