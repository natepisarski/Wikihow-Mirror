<?php

/**
 * @group ArticleQuestion
 * @group wikihow
 * @group QA
 */
class ArticleQuestionTest extends MediaWikiTestCase {

	var $articleQuestionRow = [
			'qa_id' => 1,
			'qa_article_id' => 2,
			'qa_question_id' => 3,
			'qa_inactive' => 1,
			'qa_votes_up' => 12,
			'qa_votes_down' => 15,
			'qq_id' => 4,
			'qq_submitted_id' => 5,
			'qq_question' => 'question text',
			'qn_id' => '6',
			'qn_question_id' => 3,
			'qn_answer' => 'answer text',
			'qn_updated_timestamp' => '20150101000000',
			'qq_updated_timestamp' => '20150101000000',
			'qa_updated_timestamp' => '20150101000000',
			'vi_id' => 7,
			'vi_info' => '{"date":null,"name":"Neal Gorenflo","blurb":"Founder of Shareable","hoverBlurb":"Neal Gorenflo is an expert on sharing. He has 10 years of experience as an entrepreneur and thought leader.","nameLink":"http:\/\/www.shareable.net\/users\/neal-gorenflo","category":"Miscellaneous","image":"http:\/\/www.wikihow.com\/Image:Neal-Gorenflo.jpg","initials":"NG","revisionId":null,"worksheetName":null}',
	];
	var $formData = [
		'aid' => 1,
		'aqid' => 0,
		'sqid'=> 123,
		'cqid' => 1234,
		'caid' => 12345,
		'vid' => 123456,
		'question' => 'question text',
		'answer' => 'answer text',
		'inactive' => 1,
		'votes_up' => 13,
		'votes_down' => 14,
	];

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}


	public function testNewFromDBRow() {
		$row = $this->articleQuestionRow;
		$aq = ArticleQuestion::newFromDBRow($row);
		$this->assertEquals($aq->getId(), $row['qa_id']);
		$this->assertEquals($aq->getArticleId(), $row['qa_article_id']);
		$this->assertEquals($aq->getInactive(), $row['qa_inactive']);
		$this->assertEquals($aq->getCuratedQuestionId(), $row['qa_question_id']);
		$this->assertEquals($aq->getVotesUp(), $row['qa_votes_up']);
		$this->assertEquals($aq->getVotesDown(), $row['qa_votes_down']);
		$this->assertEquals($aq->getVerifierId(), $row['vi_id']);
		$this->assertEquals($aq->getCuratedQuestion(), CuratedQuestion::newFromDBRow($row));
	}

	public function testNewFromWeb() {
		$data = $this->formData;
		$aq = ArticleQuestion::newFromWeb($data);
		$this->assertEmpty($aq->getId());
		$this->assertEquals($aq->getArticleId(), $data['aid']);
		$this->assertEquals($aq->getInactive(), $data['inactive']);
		$this->assertEquals($aq->getVotesUp(), $data['votes_up']);
		$this->assertEquals($aq->getVotesDown(), $data['votes_down']);
		$this->assertEquals($aq->getVerifierId(), $data['vid']);
		$this->assertEmpty($aq->getCuratedQuestionId());
		$this->assertEquals($aq->getCuratedQuestion(), CuratedQuestion::newFromWeb($data));
	}
}
