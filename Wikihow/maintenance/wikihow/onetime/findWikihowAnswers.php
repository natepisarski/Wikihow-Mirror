<?php

require_once __DIR__ . '/../../commandLine.inc';

if(!isset($argv[0])) {
	echo "You must provide a file name with article id's\n";
	return;
}

$fin = fopen($argv[0], "r");
while($id = fgets($fin)) {
	$idArray[] = trim($id);
}
$questionsArray = [];
$stuffToCheck = [
	"wikihow",
	"wiki how",
	"http",
	"www",
	"\[\[",
	"\]\]",
	"<",
	">"
];
$regex = "/(" . implode("|", $stuffToCheck) . ")/";

$dbr = wfGetDB(DB_REPLICA);
foreach($idArray as $id) {
	$res = $dbr->select(
		[QADB::TABLE_ARTICLES_QUESTIONS, QADB::TABLE_CURATED_ANSWERS],
		'*',
		['qa_article_id' => $id, 'qa_inactive' => 0, 'qa_question_id = qn_question_id'],
		__FILE__,
		['ORDER BY' => 'qa_score DESC']
	);

	//select * from qa_articles_questions where qa_article_id = ____ and qa_inactive = 0 and qa_verifier_id = 0 and qa_submitter_user_id = 0 order by qa_score desc
	if($dbr->numRows($res) >= 20) {
		$questions = [];

		foreach ($res as $row) {
			$questions[] = $row;//$row->qa_id;
		}

		if ( count($questions) > 20  ) {
			for($i = 20; $i < count($questions); $i++) {
				$question = $questions[$i];

				$match = preg_match($regex, $question->qn_answer);

				if($question->qa_verifier_id == 0 && $question->qa_submitter_user_id == 0 && $match == 0) {
					$questionsArray[] = $question->qa_id;
				}
			}
		} 
	}
}

foreach($questionsArray as $questionId) {
	echo "$questionId\n";
}
