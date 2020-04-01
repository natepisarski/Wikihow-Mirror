<?php

require_once __DIR__ . '/../../commandLine.inc';


$categoryQuestions = new CategoryQuestions();
echo "Started at " . microtime(true) . "\n";
$csv = $categoryQuestions->getCategoryData();
$csvFile = fopen("$IP/maintenance/wikihow/onetime/categoryinfo.csv", 'w');
fwrite($csvFile, $csv);
echo "finished at " . microtime(true) . "\n";
