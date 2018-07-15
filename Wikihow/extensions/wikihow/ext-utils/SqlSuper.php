<?php
if ( !defined('MEDIAWIKI') ) die();

class SqlSuper {

	// should be only accessed via db('read') to avoid confusion
	protected $dbr;
	protected $dbw;
	static $queries = [];

	function __construct() {
		$this->dbr = wfGetDB(DB_SLAVE);
		$this->dbw = wfGetDB(DB_MASTER);
	}

	public function db($operation) {
		return strtolower($operation) == "read" ? $this->dbr : $this->dbw;
	}

	public function select() {
		$result = call_user_func_array(array($this->dbr, 'select'), func_get_args());
		self::trace($this->dbr->lastQuery());
		return $this->fetchObjects($result);
	}

	public function selectFirst() {
		$result = call_user_func_array(array($this->dbr, 'select'), func_get_args());
		self::trace($this->dbr->lastQuery());
		$rows = $this->fetchObjects($result);
		return $rows[0];
	}

	public function delete() {
		$result = call_user_func_array(array($this->dbw, 'delete'), func_get_args());
		self::trace($this->dbw->lastQuery());
		return $result;
	}

	public function update() {
		$result = call_user_func_array(array($this->dbw, 'update'), func_get_args());
		self::trace($this->dbw->lastQuery());
		return $result;
	}

	public function insert() {
		$result = call_user_func_array(array($this->dbw, 'insert'), func_get_args());
		self::trace($this->dbw->lastQuery());
		return $result;
	}

	public function upsert() {
		$result = call_user_func_array(array($this->dbw, 'upsert'), func_get_args());
		self::trace($this->dbw->lastQuery());
		return $result;
	}

	public static function toMwTime($time=null) {
		return wfTimestamp(TS_MW, $time);
	}

	// fetch result as array of rows from db result
	public function fetchObjects($result, $single=false) {
		$rows = array();
		while ($row = $this->dbr->fetchObject($result)) {
			array_push($rows, $row);
		}
		$this->dbr->freeResult($result);
		return $single ? $rows[0] : $rows;
	}

	public static function pluckField($rows, $field, $concat=false) {
		$fields = array();

		foreach ($rows as $row):
			array_push($fields, $row->$field);
		endforeach;

		return $concat ? implode($fields, ',') : $fields;
	}

	// returns WHERE NOT IN () statement and escape if not numeric values
	public function whereNotIn($conditions, $rows, $field=NULL, $renameField=NULL, $table=NULL, $escape=false) {
		$values = $field ? self::pluckField($rows, $field) : $rows;

		if (!empty($rows)) {
			$values = $escape ? $this->escapeArray($values) : $values;
			$concat = implode($values, ',');
			$field = $renameField ? $renameField : $field;
			$clause = $table ? "$table.$field NOT IN ($concat)" : "$field NOT IN ($concat)";
			array_push($conditions, $clause);
		}
		return $conditions;
	}

	public function escapeArray($arr) {
		$clean = array();
		if (!$arr) return array();

		foreach($arr as $str):
			$escaped = $this->dbr->strencode($str);
			array_push($clean, '"' . $escaped . '"');
		endforeach;

		return $clean;
	}

	public static function prefix($rows, $prefix) {
		$clean = array();
		foreach($rows as $row) {
			$prefixedRow = array();

			foreach($row as $key => $value) {
				if (strpos($key, $prefix) == false) {
					$prefixedRow[$prefix.$key] = $value;
				} else {
					$prefixedRow[$key] = $value;
				}
			}
			array_push($clean, $prefixedRow);
		}
		return $clean;
	}

	public static function convertEmptyToNull($rows) {
		$clean = array();
		foreach($rows as $row) {
			foreach($row as $key => $value) {
				if ($value == '') {
					$row[$key] = null;
				}
			}
			array_push($clean, $row);
		}
		return $clean;
	}

	public static function addTimeStamp($rows, $field) {
		$stamp = self::toMwTime();
		return self::setField($rows, $field, $stamp);
	}

	public static function setField($rows, $field, $value) {
		foreach($rows as $index => $row) {
			$rows[$index][$field] = $value;
		}
		return $rows;
	}

	public static function trace($query) {
		array_push(self::$queries, $query);
	}
}
