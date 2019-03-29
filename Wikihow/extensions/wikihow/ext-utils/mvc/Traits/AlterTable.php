<?php
namespace MVC\Traits;

use ActiveRecord\Table;
use ActiveRecord\Cache;
use MVC\CLI;
use __;

trait AlterTable {

	static function addIndex($field) {
		$table = static::$table_name;
		if (static::hasIndex($field)) {
			self::trace("Index for {$table} $field was already existing, skipping...", 'bg_orange');
			return $this;
		}

		static::run("ALTER TABLE {$table} ADD INDEX ($field);");
	}

	static function renameColumn($field, $newName, $type, $options) {
		$optionsStr = self::optionsToString($options);
		$table = static::$table_name;
		$query = "ALTER TABLE {$table} CHANGE COLUMN `$field` `$newName` $type $optionsStr;";
		static::run($query);
		Table::clear_cache();
	}

	static function run($sql, $values=null) {
		CLI::trace($sql, 'light_magenta');
		static::connection()->query($sql, $values);
		Table::clear_cache();
	}

	static function addColumn($field, $type, $options=[]) {
		$table = static::$table_name;
		$optionsStr = static::optionsToString($options);

		if (static::hasColumn($field)) {
			CLI::trace("$field was already in table {$table}", 'red');
			return;
		}

		$query = "ALTER TABLE {$table} ADD $field $type $optionsStr;";
		static::run($query);
		Table::clear_cache();
	}

	static function hasIndex($field) {
		$table = static::$table_name;
		$result = static::connection()->query("SHOW INDEX FROM {$table};")->fetchAll();
		$columns = __::pluck($result, 'column_name');
		return in_array($field, $columns);
	}

	static function hasColumn($field) {
		Table::clear_cache();
		$columns = array_keys(static::table()->columns);
		return in_array($field, $columns);
	}

	static function removeColumn($field) {
			$table = static::$table_name;

		if (!static::hasColumn($field)) {
			CLI::trace("$field was not in table {$table} and could not be removed", 'Red');
			return;
		}

		$query = "ALTER TABLE {$table} DROP COLUMN $field;";
		static::run($query);
		Table::clear_cache();
	}

	static function optionsToString($options=[]) {
		$str = "";
		foreach ($options as $key => $value) {
			$value = static::connection()->escape($value);
			$str .= "$key $value ";
		}
		return rtrim($str);
	}

}
