<?php
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

if ($wgLanguageCode == 'en') {
	print "Retranslatefish updates not supported for English. Exiting.\n";
	die();
}

$titus = new TitusDB(true);
$statsToCalc['PageId'] = 1;
$statsToCalc['LanguageCode'] = 1;
$statsToCalc['RetranslationComplete'] = 1;
$statGroups = $titus->getPagesToCalcByStat($statsToCalc, wfTimestampNow());

try {
	$titus->calcStatsForPageIds($statsToCalc, $statGroups['custom_id_stats']['RetranslationComplete']);
	print 'Updated ' . count($statGroups['custom_id_stats']['RetranslationComplete']) . " pages\n";
} catch (Exception $e) {
	print 'Caught exception: "' . $e->getMessage() . "\"\n";
}

