<?php
/*
CREATE TABLE `qa_flagged_answers` (
	`qfa_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`qfa_aq_id` INT(10) NOT NULL DEFAULT 0,
	`qfa_flag_user_id` INT(10) NOT NULL DEFAULT 0,
	`qfa_reason` VARBINARY(64) NOT NULL DEFAULT '',
	`qfa_details` VARBINARY(1024) NOT NULL DEFAULT '',
	`qfa_expert` TINYINT(4) NOT NULL DEFAULT 0,
	`qfa_created_at` VARBINARY(14) NOT NULL DEFAULT '',
	`qfa_active` TINYINT(4) NOT NULL DEFAULT 1,
	KEY (`qfa_aq_id`),
	KEY (`qfa_created_at`)
);
*/

class FlaggedAnswers {
	var $id, $aqId, $flagUserId, $reason, $details, $expert, $createdAt, $active;

	const TABLE = 'qa_flagged_answers';

	public function __construct() {
		$this->id 				= 0;
		$this->aqId 			= 0;
		$this->flagUserId	= 0;
		$this->reason 		= '';
		$this->details 		= '';
		$this->expert 		= 0;
		$this->createdAt 	= wfTimeStampNow();
		$this->active 		= 1;
	}

	protected function loadFromDbRow($row) {
		$this->id 				= $row->qfa_id;
		$this->aqId 			= $row->qfa_aq_id;
		$this->flagUserId	= $row->qfa_flag_user_id;
		$this->reason 		= $row->qfa_reason;
		$this->details 		= $row->qfa_details;
		$this->expert 		= $row->qfa_expert;
		$this->createdAt 	= $row->qfa_created_at;
		$this->active 		= $row->qfa_active;
	}

	public function loadById($qfa_id) {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			self::TABLE,
			'*',
			['qfa_id' => $qfa_id],
			__METHOD__
		);

		$row = $res->fetchObject();

		if ($row) {
			$this->loadFromDbRow($row);
			return true;
		}
		else {
			return false;
		}
	}

	public function save() {
		$dbw = wfGetDB(DB_MASTER);

		$res = $dbw->upsert(
			self::TABLE,
			[
				'qfa_id' 						=> $this->id,
				'qfa_aq_id' 				=> $this->aqId,
				'qfa_flag_user_id' 	=> $this->flagUserId,
				'qfa_reason' 				=> $this->reason,
				'qfa_details'				=> $this->details,
				'qfa_expert'				=> $this->expert,
				'qfa_created_at'		=> $this->createdAt,
				'qfa_active'				=> $this->active,
			],
			['qfa_id'],
			[
				'qfa_aq_id = VALUES(qfa_aq_id)',
				'qfa_flag_user_id = VALUES(qfa_flag_user_id)',
				'qfa_reason = VALUES(qfa_reason)',
				'qfa_details = VALUES(qfa_details)',
				'qfa_expert = VALUES(qfa_expert)',
				'qfa_active = VALUES(qfa_active)'
			],
			__METHOD__
		);

		if (empty($this->id) && $res == true) {
			$this->id = $dbw->insertId();
		}

		return $res;
	}

	public function exists() {
		return $this->id > 0;
	}

	public static function remaining($include_experts = false) {
		$dbr = wfGetDB(DB_REPLICA);

		$count = $dbr->selectField(
			self::TABLE,
			'count(*)',
			[
				'qfa_active' => 1,
				'qfa_expert' => $include_experts ? 1 : 0
			],
			__METHOD__
		);

		return $count;
	}

	public static function deactivateFlaggedAnswerById($qfa_id) {
		return self::deactivateFlaggedAnswer(['qfa_id' => $qfa_id]);
	}

	private static function deactivateFlaggedAnswerByAqId($aqid) {
		return self::deactivateFlaggedAnswer(['qfa_aq_id' => $aqid]);
	}

	private static function deactivateFlaggedAnswer($where) {
		if (empty($where['qfa_id']) && empty($where['qfa_aq_id'])) return;

		$dbw = wfGetDB(DB_MASTER);

		$res = $dbw->update(
			self::TABLE,
			['qfa_active' => 0],
			$where,
			__METHOD__
		);

		return $res;
	}

	public static function addFFA($aq_id, $reason, $expert) {
		if (empty($aq_id) || empty($reason)) return;

		$fa = new FlaggedAnswers();
		$fa->aqId = $aq_id;
		$fa->flagUserId = RequestContext::getMain()->getUser()->getId();
		$fa->reason = $reason;
		$fa->expert = (bool)$expert;
		$res = $fa->save();

		return [
			'saved' => $res,
			'qfa_id' => $fa->id
		];
	}

	public static function addDetails($qfa_id, $details) {
		if (empty($qfa_id) || empty($details)) return;

		$fa = new FlaggedAnswers();
		$fa->loadById($qfa_id);
		$fa->details = $details;
		$fa->save();
	}

	public static function onInsertArticleQuestion($aid, $aqid, $isNew) {
		//removing since it's been edited
		if (!$isNew) self::deactivateFlaggedAnswerByAqId($aqid);
		return true;
	}

	public static function onDeleteArticleQuestion($aid, $aqid) {
		self::deactivateFlaggedAnswerByAqId($aqid);
		return true;
	}
}
