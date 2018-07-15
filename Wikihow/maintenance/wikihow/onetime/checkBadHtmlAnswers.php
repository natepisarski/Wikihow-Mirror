<?php

require_once __DIR__ . '/../../commandLine.inc';

$db = wfGetDB(DB_SLAVE);

$res = $db->select(
	[
		QADB::TABLE_CURATED_ANSWERS,
		QADB::TABLE_ARTICLES_QUESTIONS
	],
	[
		'qn_answer',
		'qa_article_id'
	],
	[
		'qn_question_id = qa_question_id'
	],
	__METHOD__
);

$wrapper = new MWTidyWrapper;
$count = 1;

foreach ($res as $row) {
	$err = '';
	$html = $wrapper->getWrapped( $row->qn_answer );
	$good = MWTidy::checkErrors($html, $err);

	if (!$good &&
		!strpos($err,'unescaped &') &&
		!strpos($err,'improperly escaped URI reference'))
	{
		print $count.') page id = '.$row->qa_article_id."\n".$row->qn_answer."\n\nERROR(S):\n".$err."\n\n";
		$count++;
	}
}

print "\nDone.\n";