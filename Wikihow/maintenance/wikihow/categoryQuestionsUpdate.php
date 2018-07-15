<?php

require_once __DIR__ . '/../commandLine.inc';

$categoryQuestions = new CategoryQuestions();
$dbr = wfGetDB(DB_SLAVE);

$maxTime = 600; //10 minutes

$conds = [];
$conds['GROUP BY'] = 'aqq_category';
$conds['ORDER BY'] = 'aqq_queue_timestamp ASC';
if(!$argv || $argv[0] != "all") {
	$conds['LIMIT'] = 1;
}
$res = $dbr->select(AnswerQuestions::TABLE_QUEUE, ['aqq_category'], [], __FILE__, $conds);
$startTime = microtime(true);
$oldCategories = [];

echo "Found questions for ";
foreach($res as $row) {
	$success = $categoryQuestions->getQuestionsByCategory($row->aqq_category);
	if(!$success) {
		$oldCategories[] = $row->aqq_category;
	} else {
		echo "$row->aqq_category, ";
	}
	if((microtime(true)-$startTime) > $maxTime) {
		echo "\n Exceeded max time. Will finish the rest tomorrow.\n";
		break;
	}
}
echo "\n";
if(count($oldCategories) > 0) {
	echo "The following categories no longer exist and have been removed: " . implode(",", $oldCategories) . ".\n";
}
echo "Finished in " . (microtime(true)-$startTime) . " seconds\n";