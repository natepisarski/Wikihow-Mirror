<?php

require_once __DIR__ . '/../Maintenance.php';

class StaffReviewedArticles extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Update our list of articles that we mark as 'Staff Reviewed'";
	}

	public function execute() {
		$article_ids = $this->staffReviewedArticlesFromTitus();
		StaffReviewed::updateArticlesAdminTag($article_ids);
	}

	private function staffReviewedArticlesFromTitus() {
		$article_ids = [];
		$two_years_ago = date('Ymd', strtotime('today - 2 years'));

		$staff = StaffReviewed::staffReviewers();
		if (empty($staff)) return $article_ids;

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select(
			'titus_copy',
			[
				'ti_page_id',
				'ti_helpful_total',
				'ti_helpful_percentage',
				'ti_helpful_total_including_deleted',
				'ti_helpful_percentage_including_deleted',
			],
			[
				'ti_language_code' => 'en',
				"ti_expert_verified_source IN ('','".StaffReviewed::STAFF_REVIEWED_SOURCE."')",
				"ti_last_fellow_edit_timestamp > $two_years_ago",
				"ti_last_fellow_edit IN ('".implode("','",$staff)."')"
			],
			__METHOD__
		);

		foreach ($res as $row) {
			if (!StaffReviewed::titusHelpfulnessCheck($row)) continue;
			if (StaffReviewed::sensitiveArticle($row->ti_page_id)) continue;
			$article_ids[] = $row->ti_page_id;
		}

		return $article_ids;
	}

}

$maintClass = 'StaffReviewedArticles';
require_once RUN_MAINTENANCE_IF_MAIN;
