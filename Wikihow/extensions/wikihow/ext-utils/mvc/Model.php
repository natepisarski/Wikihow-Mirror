<?php
namespace MVC;
use ActiveRecord\Model as ArModel;
use MVC\Debugger;

class Model extends ArModel {
	use Traits\AlterTable;

	public static function find() {
		$result = call_user_func_array(['parent', 'find'], func_get_args());
		Debugger::traceQuery(self::table()->conn->last_query);
		return $result;
	}

	function isPersisted() {
		return !$this->isNew();
	}

	static function find_or_create($conditions) {
		$row = self::find($conditions);
		return is_null($row) ? self::create($conditions) : $row;
	}

	function isNew() {
		return $this->is_new_record();
	}

}
