<?php

/**
 * one-time generation script for TopAnswerers tables
 **/

require_once __DIR__ . '/../../Maintenance.php';

class TopAnswerersGenesis extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "One-time generation script for TopAnswerers tables.";
	}

	public function execute() {
		$count = 0;
		$users = [];

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select(
			QADB::TABLE_ARTICLES_QUESTIONS,
			[
				'qa_submitter_user_id',
				'the_count' => 'count(*)'
			],
			[
				"qa_submitter_user_id > 0",
				'qa_inactive' => 0
			],
			__METHOD__,
			[
				'GROUP BY' => 'qa_submitter_user_id'
			]
		);

		foreach ($res as $row) {
			$users[$row->qa_submitter_user_id] = $row->the_count;
		}

		foreach ($users as $user_id => $answer_count) {
			//no auto-adding...yet
			// if ($answer_count > 100) self::addUser($user_id);

			self::processCats($dbw, $user_id);
			self::upTheStats($dbw, $user_id, $answer_count);
		}
	}

	private static function addUser($user_id) {
		$ta = new TopAnswerers();

		//only for new ones
		if ($ta->loadByUserId($user_id)) return false;

		$ta->userId = $user_id;
		$ta->source = TopAnswerers::SOURCE_AUTO;
		$res = $ta->save();

		return $res;
	}

	private static function processCats($db, $user_id) {
		$res = $db->select(
			QADB::TABLE_ARTICLES_QUESTIONS,
			[
				'qa_id',
				'qa_article_id'
			],
			[
				'qa_submitter_user_id' => $user_id
			],
			__METHOD__
		);

		foreach ($res as $row) {
			self::addCat($db, $row->qa_article_id, $user_id);
		}
	}

	private static function addCat($db, $aid, $user_id) {
		$cat = TopAnswerers::getCat($aid);

		if ($cat) {
			$res = $db->upsert(
				TopAnswerers::TABLE_ANSWERER_CATEGORIES,
				[
					'qac_user_id' => $user_id,
					'qac_category' => $cat,
					'qac_count' => 1
				],
				[
					'qac_user_id',
					'qac_category'
				],
				[
					'qac_user_id = VALUES(qac_user_id)',
					'qac_category = VALUES(qac_category)',
					'qac_count = VALUES(qac_count)+qac_count'
				],
				__METHOD__
			);
		}
	}

	private static function upTheStats($db, $user_id, $answer_count) {
		$res = $db->upsert(
			TopAnswerers::TABLE_ANSWERER_STATS,
			[
				'qas_user_id' 			=> $user_id,
				'qas_answers_count'	=> $answer_count
			],
			['qas_user_id'],
			[
				'qas_user_id = VALUES(qas_user_id)',
				'qas_answers_count = VALUES(qas_answers_count)+1'
			],
			__METHOD__
		);
	}
}

$maintClass = 'TopAnswerersGenesis';
require_once RUN_MAINTENANCE_IF_MAIN;
