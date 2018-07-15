<?php

require_once(__DIR__ . '/../commandLine.inc');
require_once("$IP/extensions/wikihow/thumbratings/ThumbRatingsMaintenance.class.php");

$wgUser = User::newFromName('Votebot');
$calculationDuration = 30;
$numToCalc = 1000;

$cmd = isset($argv[0]) ? $argv[0] : '';

if ($cmd == "iterations") {
	numIterations();
} elseif ($cmd == "rank") {
	rank();
}

function rank() {
 	global $numToCalc, $calculationDuration;
	$trMaint = new ThumbRatingsMaintenance();
	// Set num to the number of articles to rank.  Ranks articles never ranked or ranked > 2 weeks ago
	$lowDate =  wfTimestamp(TS_MW, strtotime("-$calculationDuration day", strtotime(date('Ymd', time()))));
	$trMaint->rankArticles($numToCalc, $lowDate);
}

// How many articles should we rank tonight assuming we process all the articles over a two week period?
// Figure out the number of iterations to call rankArticles assuming 1k articles are ranked per iteration
function numIterations() {
	global $calculationDuration, $numToCalc;
	$totalRatedArticles = ThumbRatingsMaintenance::getRatedArticlesCount();
	echo ceil($totalRatedArticles / $calculationDuration / $numToCalc);
}
