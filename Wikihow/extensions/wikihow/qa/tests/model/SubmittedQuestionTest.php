<?php

/**
 * @group SubmittedQuestion
 * @group wikihow
 * @group Q&A
 */
class SubmittedQuestionTest extends MediaWikiTestCase {

	var $submittedQuestionRow = [
			'qs_id' => 1,
			'qs_article_id' => 2,
			'qs_question' => 'question text',
			'qs_ignore' => 1,
			'qs_curated' => '1',
	];

	public function testNewFromDBRow() {
		$row = $this->submittedQuestionRow;
		$sq = SubmittedQuestion::newFromDBRow($row);
		$this->assertEquals($sq->getId(), $row['qs_id']);
		$this->assertEquals($sq->getArticleId(), $row['qs_article_id']);
		$this->assertEquals($sq->getText(), $row['qs_question']);
		$this->assertEquals($sq->getIgnore(), $row['qs_ignore']);
		$this->assertEquals($sq->getCurated(), $row['qs_curated']);
	}
}
