<?php

/*
Marking articles as sensitive for "Not Real" and "- Hide staff reviewed"
This is the one-time fix for ones that are already in those sensitive categories
*/

require_once __DIR__ . '/../../Maintenance.php';

class removeSensitiveArticlesFromStaffReviewed extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "One-time fix script for staff reviewed articles that are already sensitive.";
	}

	public function execute() {
		$db = wfGetDB(DB_MASTER);
		$res = $db->select(
			'sensitive_article',
			'sa_page_id',
			[ 'sa_reason_id IN (7,10)' ],
			__METHOD__
		);

		foreach ($res as $row) {
			StaffReviewed::removeStaffReviewedArticleFromArticleTag($row->sa_page_id);
		}
	}
}

$maintClass = 'removeSensitiveArticlesFromStaffReviewed';
require_once RUN_MAINTENANCE_IF_MAIN;
