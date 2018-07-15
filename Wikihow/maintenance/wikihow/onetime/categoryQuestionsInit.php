<?php

require_once __DIR__ . '/../../commandLine.inc';


$categoryQuestions = new CategoryQuestions();
$dbr = wfGetDB(DB_SLAVE);

$conds = [];
$conds['GROUP BY'] = 'aqq_category';
$conds['ORDER BY'] = 'aqq_queue_timestamp ASC';
if(!$argv || $argv[0] != "all") {
	$conds['LIMIT'] = 10;
}
$res = $dbr->select(AnswerQuestions::TABLE_QUEUE, ['aqq_category'], [], __FILE__, $conds);
//var_dump($dbr->lastQuery());
echo "Found questions for ";
foreach($res as $row) {
	$categoryQuestions->getQuestionsByCategory($row->aqq_category);
	echo "$row->aqq_category, ";
}
echo "\n";