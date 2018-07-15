<?php

require_once __DIR__ . '/../../commandLine.inc';

global $IP;
require_once("$IP/extensions/wikihow/dedup/SuccessfulEdit.class.php");

if (sizeof($argv) != 1) {
	die( basename(__FILE__) . " [articleIdFile]\n" );
}

$f = fopen($argv[0],"r");
if (!$f) {
	die("Unable to open file: {$argv[0]}\n");
}

print "Article Id\tUsername\tBytes Added\tRevision\tGood Revision\n";
while (!feof($f)) {
	$l = fgets($f);
	$l = rtrim($l);
	$articleId = intval($l);
	if ($articleId != 0) {
		$se = SuccessfulEdit::getEdits($articleId);

		foreach ($se as $e) {
			print $articleId . "\t" . $e['username'] . "\t" . $e['added'] . "\t" . $e['rev'] . "\t" . $e["gr"] . "\n";
		}
	}
}

fclose($f);
