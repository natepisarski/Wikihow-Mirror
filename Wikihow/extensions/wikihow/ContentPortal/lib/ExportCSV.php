<?php
namespace ContentPortal;
use Mustache_Engine;

abstract class ExportCSV {
	static $dateFormat = "%m/%d/%Y";
	static $sqlDateFormat = "%Y%m%d%H%i%S";
	static $model;

	static function getSql($file, $vars=[]) {
		$file = file_get_contents(__DIR__ . "/queries/$file.sql");
		$query = (new Mustache_Engine)->render($file, $vars);
		$class = static::$model;
		return $class::table()->conn->query($query)->fetchAll();
	}

}
