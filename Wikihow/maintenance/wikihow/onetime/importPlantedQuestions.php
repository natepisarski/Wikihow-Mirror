<?php

require_once( "../../commandLine.inc" );

if( sizeof($argv) < 2 ) {
	echo "You need to supply 2 parameters (Tool name AND file name).\n";
	return;
}

$className == "";
switch ($argv[0]) {
	case "Category":
		$className = "CategoryPlants";
		break;
	case "Tip":
		$className = "TipPlants";
		break;
	case "Spell":
		$className = "SpellingPlants";
		break;
	case "UCI":
		$className = "UCIPlants";
		break;
}

$count = 0;

if ($className != "") {
	$dbw = wfGetDB(DB_MASTER);

	$plant = new $className;
	$fields = $plant->getQuestionDbFields();

	$handle = fopen($argv[1], 'r');
	while ($line = fgets($handle)) {
		$parts = explode("\t", $line);
		foreach ($parts as $index => $part) {
			$insertValues[$fields[$index]] = trim($part);
		}
		$dbw->insert($plant->questionTable, $insertValues);
		$count++;
	}
	echo "Inserted {$count} rows into the {$plant->questionTable} table.\n";
} else {
	echo "Unrecognized class name entered.\n";
}

