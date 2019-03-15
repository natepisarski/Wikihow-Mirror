<?php

/**
 * @group SubmittedUserReview
 * @group wikihow
 * @group UserReview
 */
class SubmittedUserReviewTest extends MediaWikiTestCase {



	var $submittedUserReviewRow = [
			'us_id' => 1,
			'us_user_id' => 2788911,
			'us_visitor_id' => null,
			'us_article_id' => 503318,
			'us_email' => 'kamsie2014@hotmail.com',
			'us_review' => 'This may seem a bit difficult but if you take it step by step carefully, it\'s easy and has a great outcome!',
			'us_first_name' => 'Kenzie',
			'us_last_name' => 'Hood',
			'us_submitted_timestamp' => '20160308214437',
			'us_curated_timestamp' => null,
			'us_status' => 0,
			'us_positive' => 1,
			'us_curated_user' => 0,
			'us_eligible' => 0,
			'us_checkout' => null
	];

	var $userReviewFormData = [
		'articleId' => 503318,
		'firstName' => 'Wilson',
		'lastName'=> 'Restrepo',
		'review' => 'Fantastic article. Would recommend to a friend.',
		'email' => 'wilson@wikihow.com',
		'userId' => 123,
		'visitorId' => 13
	];

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testIsQualified() {
		$sur = SubmittedUserReview::newFromFormData($this->userReviewFormData);

		$tooShort = "hi";
		$sur->review = $tooShort;
		$this->assertFalse($sur->isQualified());

		$tooLong = str_repeat("testing ", 100);
		$sur->review = $tooLong;
		$this->assertFalse($sur->isQualified());

		$tooFewWords = "too few ";
		$sur->review = $tooFewWords;
		$this->assertFalse($sur->isQualified());

		$longWords =  "these words are long: antidisesteblishmentarianism supercalifragilisticexpialidocious";
		$sur->review = $longWords;
		$this->assertFalse($sur->isQualified());

		$hasBadWords = "This is a perfectly valid review but it has the word fuck in it. Fuck.";
		$sur->review = $hasBadWords;
		$this->assertFalse($sur->isQualified());
	}

	public function testCorrectFields() {
		$sur = SubmittedUserReview::newFromFormData($this->userReviewFormData);

		$wikiHowCapReview = "wikiHow can be spelled wikihow, Wikihow, WikiHow, wiki how, Wiki How, Wiki how";
	        $sur->review = $wikiHowCapReview;
		$wikiHowCapReviewFix ="wikiHow can be spelled wikiHow, wikiHow, wikiHow, wikiHow, wikiHow, wikiHow";
		$sur->correctFields();
		$this->assertEquals($sur->review, $wikiHowCapReviewFix);

		$utf8Review = "This character \xee\x90\x95 is unicode";
		$sur->review = $utf8Review;
		$utf8ReviewFix = "This character  is unicode";
		$sur->correctFields();
		$this->assertEquals($sur->review, $utf8ReviewFix);

		$oneLetterName = "r";
		$sur->lastName = $oneLetterName;
		$oneLetterNameFix = "R.";
		$sur->correctFields();
		$this->assertEquals($sur->lastName, $oneLetterNameFix);


		$uncappedName = "wilson";
		$sur->firstName = $uncappedName;
		$uncappedNameFix = "Wilson";
		$sur->correctFields();
		$this->assertEquals($sur->firstName, $uncappedNameFix);
	}

	public function testSave() {
		$dbw = wfGetDB(DB_MASTER);

		$sur = SubmittedUserReview::newFromFormData($this->userReviewFormData);
		$result = $sur->save();
		$surId = $result['id'];
		$insertedReview = $dbw->selectField(URDB::TABLE_SUBMITTED, 'us_review', ['us_id' => $surId] );

		$this->assertEquals($sur->review, $insertedReview);
	}


	public function testCreateNewObject() {
		$sur = SubmittedUserReview::newFromFormData($this->userReviewFormData);
		$this->assertEquals($sur->articleId, $this->userReviewFormData['articleId']);
		$this->assertEquals($sur->firstName, $this->userReviewFormData['firstName']);
		$this->assertEquals($sur->lastName, $this->userReviewFormData['lastName']);
		$this->assertEquals($sur->review, $this->userReviewFormData['review']);
		$this->assertEquals($sur->email, $this->userReviewFormData['email']);
		$this->assertEquals($sur->visitorId, $this->userReviewFormData['visitorId']);
		$this->assertEquals($sur->status, 0);
		$this->assertEquals($sur->positive, 0);
		$this->assertEquals($sur->eligible,UserReview::isArticleEligibleForReviews($sur->articleId));

	}

}
