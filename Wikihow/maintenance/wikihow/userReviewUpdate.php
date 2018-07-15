<?php

/**
 * Used to update the helpfulness data for the articles
 **/

require_once __DIR__ . '/../Maintenance.php';

class UserReviewUpdate extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Update eligibility for articles";
	}

	public function execute()
	{
		$starttime = microtime(true);
		$dbw = wfGetDB(DB_MASTER);

		$res = $dbw->select(UserReview::TABLE_SUBMITTED,
			array('us_article_id', 'us_eligible'),
			array("us_status != " . UserReviewTool::STATUS_DELETED),
			__FILE__,
			array("GROUP BY" => "us_article_id"));

		$count = 0;
		foreach ($res as $row) {
			UserReview::updateEligibilityField($row->us_article_id);
			$count++;
		}

		echo "Updated {$count} article id's in " . (microtime(true)-$starttime) . " seconds.\n";
	}
}

$maintClass = 'UserReviewUpdate';
require_once RUN_MAINTENANCE_IF_MAIN;