<?php
class URDB {
	const TABLE_SUBMITTED = "userreview_submitted";

	public static function insertNewReview($submittedUserReview) {
		$dbw = wfGetDB(DB_MASTER);

		$insertValues = array(
			'us_user_id' => $submittedUserReview->userId,
			'us_visitor_id' => $submittedUserReview->visitorId,
			'us_article_id' => $submittedUserReview->articleId,
			'us_email' => $submittedUserReview->email,
			'us_review' => $submittedUserReview->review,
			'us_firstname' => $submittedUserReview->firstName,
			'us_lastname' => $submittedUserReview->lastName,
			'us_submitted_timestamp' => $submittedUserReview->submittedTimestamp,
			'us_status' => $submittedUserReview->status,
			'us_eligible' => $submittedUserReview->eligible,
			'us_positive' => $submittedUserReview->positive,
			'us_rating' => $submittedUserReview->rating,
			'us_image' => $submittedUserReview->image
		);

		$dbw->insert(self::TABLE_SUBMITTED, $insertValues, __METHOD__);
		return $dbw->insertId();
	}

}
