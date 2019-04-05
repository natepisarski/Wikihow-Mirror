<?php

/**
 * nightly script to add users to the TopAnswerers tables
 **/

require_once __DIR__ . '/../Maintenance.php';

class UpdateTopAnswerers extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Nightly script to add users to the TopAnswerers tables.";
	}

	public function execute() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			[
				TopAnswerers::TABLE_ANSWERER_APP_RATINGS,
				TopAnswerers::TABLE_TOP_ANSWERERS
			],
			[
				'qaar_user_id',
				'ta_user_id'
			],
			[],
			__METHOD__,
			[ 'GROUP BY' => 'qaar_user_id' ],
			[ TopAnswerers::TABLE_TOP_ANSWERERS => ['LEFT JOIN', 'ta_user_id = qaar_user_id'] ]
		);

		foreach ($res as $row) {
			$user_id = $row->qaar_user_id;
			if (empty($row->ta_user_id)) {
				if (!TopAnswerers::topAnswererMaterial($user_id)) continue;
				$this->addUser($user_id);
			}
			else {
				$this->recalculateTopCats($user_id);
			}
		}
	}

	private function addUser($user_id) {
		$ta = new TopAnswerers();

		//only for new ones
		if ($ta->loadByUserId($user_id)) return false;

		$ta->userId = $user_id;
		$ta->source = TopAnswerers::SOURCE_AUTO;
		$ta->save();
	}

	private function recalculateTopCats($user_id) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select(
			QADB::TABLE_ARTICLES_QUESTIONS,
			['qa_article_id'],
			[
				'qa_submitter_user_id' => $user_id,
				'qa_inactive' => 0
			],
			__METHOD__
		);

		$categories_answered_by_user = [];
		foreach ($res as $row) {
			$categories_answered_by_user[] = TopAnswerers::getCat($row->qa_article_id);
		}

		$cat_counts = array_count_values($categories_answered_by_user);
		$unique_cats = array_unique($categories_answered_by_user);

		foreach ($cat_counts as $category => $count) {
			TopAnswerers::addCat($user_id, $category, $count);
		}

		TopAnswerers::deleteOldCats($user_id, $unique_cats);
	}
}

$maintClass = 'UpdateTopAnswerers';
require_once RUN_MAINTENANCE_IF_MAIN;
