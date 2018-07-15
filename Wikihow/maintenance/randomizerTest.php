<?php
// Test the getRandomTitle function
global $IP;
require_once 'commandLine.inc';
require_once "$IP/extensions/wikihow/Randomizer.php";

$numUrls = 100;
for ($i = 0; $i < $numUrls; $i++) {
	$t = Randomizer::getRandomTitle();
	echo $t->getFullURL() . "\n";
}
