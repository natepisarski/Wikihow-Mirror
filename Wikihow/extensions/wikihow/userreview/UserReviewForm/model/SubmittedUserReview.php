<?php

class SubmittedUserReview {
	const MAX_WORD_LENGTH = 25;
	const MIN_WORD_COUNT = 3;
	const MIN_CHAR_COUNT = 20;
	const MAX_CHAR_COUNT = 500;

	var $id, $userId, $visitorId, $articleId, $email, $review, $firstName, $lastName, 
		$submittedTimestamp, $curatedTimestamp, $status, $positive, $curatedUser, 
		$eligible, $checkout, $rating, $image;

	private function __construct() {
		$this->submittedTimestamp = wfTimestampNow();
	}

	public function save(){
		$insertId = URDB::insertNewReview($this);
		return array('success' => true, 'id' => $insertId);
	}

	public function isQualified(){
		if( strlen($this->review) < self::MIN_CHAR_COUNT || strlen($this->review) > self::MAX_CHAR_COUNT ) return false;

		$words = explode(" ", $this->review);

		$wordCount = count($words);
		if( $wordCount <= self::MIN_WORD_COUNT) return false;

		$maxWordLen = function($value) {
				if ( strlen($value) > self::MAX_WORD_LENGTH ) return false;
				return true;
			       };
		
		$okWordLength = array_map($maxWordLen, $words);
		$andReduce = function($v1, $v2) {
				return $v1 && $v2;
			    };
		$allOkWordLength = array_reduce($okWordLength, $andReduce, true);
		if(! $allOkWordLength) return false;
		
		if (BadWordFilter::hasBadWord($this->review)) return false;

		return true;
	}

	public function correctFields(){
		$this->review = preg_replace('/wiki\s*how/i', "wikiHow", $this->review);
		$this->review = preg_replace( '/[^[:print:]]/', '',$this->review);

		$this->firstName =  ucfirst($this->firstName);
		$this->lastName = ucfirst($this->lastName);
		
		$this->firstName = preg_replace( '/[^[:print:]]/', '',$this->firstName);
		$this->lastName = preg_replace( '/[^[:print:]]/', '',$this->lastName);
		//COPPA compliance for under 13 years
		if(preg_match("/(I am|I'm|Im) \b([1-9]|1[0-2])\b/i", $this->review) === 1) {
			//throw out the last name
			$this->lastName = "";
		}

		if (strlen($this->firstName) == 1) $this->firstName .= ".";
		if (strlen($this->lastName) == 1) $this->lastName .= ".";

	}

	public static function newFromFormData($formData) {
		$sur = new SubmittedUserReview();
		$sur->loadFromFormData($formData);
		$sur->populateFlags();
		return $sur;
	}

	public static function newFromFields($articleId, $firstName, $lastName, $review, $email, $userId, $visitorId, $rating = 0, $image=''){
		$sur = new SubmittedUserReview();
		$data = array('articleId' => $articleId, 
				'firstName' => $firstName,
				'lastName' => $lastName, 
				'review' => $review,
				'email' => $email,
				'userId' => $userId,
				'visitorId' => $visitorId,
				'rating' => $rating,
				'image' => $image);
		$sur->loadFromFormData($data);
		$sur->populateFlags();
		return $sur;
	}

	public function populateFlags() {
		if($this->rating == 0 || $this->rating > 3) {
			if($this->image != "") {
				$this->status = UserReviewTool::STATUS_UCI_WAITING;
			} else {
				$this->status = UserReviewTool::STATUS_AVAILABLE;
			}
		} else {
			$this->status = UserReviewTool::STATUS_DELETED;
		}
		$this->positive = 0;
		$this->eligible = UserReview::isArticleEligibleForReviews($this->articleId);
	}

	public function loadFromFormData($formData) {
		$this->articleId = $formData['articleId'];
		$this->firstName = $formData['firstName'];
		$this->lastName = $formData['lastName'];
		$this->review = $formData['review'];
		$this->email = $formData['email'];
		$this->userId = $formData['userId'];
		$this->visitorId = $formData['visitorId'];
		$this->rating = $formData['rating'];
		$this->image = $formData['image'];
	}

	public static function onUnitTestsList( &$files ) {
		global $IP;
		$files = array_merge( $files, glob( "$IP/extensions/wikihow/userreview/UserReviewForm/tests/model/*Test.php" ) );
		return true;
	}
}

