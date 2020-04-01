<?php
namespace ContentPortal;
use __;

class UserCSV extends ExportCSV {

	static $model = 'ContentPortal\User';

	static $csvFields = [
		"wh_user_id",
		"username",
		"roles",
		"category",
		"assignments",
		"completed_tasks",
		"is_established",
		"is_disabled",
		"last_seen"
	];

	static function userStats($fields) {
		$result = self::getSql('UsersAll', ['date_format' => self::$dateFormat]);
		return __::chain($result)->unique('id')->pluck($fields)->value();
	}

}
