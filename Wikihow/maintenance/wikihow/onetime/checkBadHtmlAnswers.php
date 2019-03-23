<?php

require_once __DIR__ . '/../../commandLine.inc';

// TODO: upgrade this method to not rely on calling MWTidy::checkErrors().
// Not doing this now since it's a "onetime" script.
//
// There is a MW 1.31 release note that states:
//   * As part of work to modernise user-generated content clean-up, a config option
//    and some methods related to HTML validity were removed without deprecation.
//    The public methods MWTidy::checkErrors() and the path through which it was
//    called, TidyDriverBase::validate(), are removed, as are the testing methods
//    MediaWikiTestCase::assertValidHtmlSnippet() and ::assertValidHtmlDocument().
//    The $wgValidateAllHtml configuration option is removed and will be ignored.
die("Needs upgrade to newer Mediawiki");

$db = wfGetDB(DB_REPLICA);

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
