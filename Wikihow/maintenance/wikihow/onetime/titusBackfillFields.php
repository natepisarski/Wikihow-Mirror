<?php
require_once __DIR__ . '/../../commandLine.inc';
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

if (count($argv) < 1) {
	echo "You must provide at least one field to backfill\n";
	exit;
}

$titus = new TitusDB(true);

$allStats = TitusConfig::getAllStats();
$desiredStats = TitusConfig::getBasicStats();

foreach ($argv as $field) {
	if (array_key_exists($field, $allStats)) {
		$desiredStats[$field] = 1;
	} else {
		echo "$field is an unknown titus field\n";
		exit;
	}
}

$titus->calcStatsForAllPages($desiredStats);
