<?php
namespace MVC\Traits;
use MVC\Errors;

trait Utils {

	public static function includeIfExists($path) {
		if (file_exists($path)) {
			include_once $path;
			return true;
		}
		return false;
	}

	public static function namespaceClass($className) {
		return APP_NS . "\\" . $className;
	}

	public static function checkConstants() {
		foreach(['APP_NS', 'APP_DIR', 'APP_HTTP_PATH'] as $key) {
			if (!defined($key)) Errors::trigger("You must define $key in Constants.php");
		}
	}

	public static function getConfig() {
		$class = self::namespaceClass('Config');
		return class_exists($class) ? $class::getInstance() : false;
	}

	public static function includeDir($dir) {
		if (is_dir($dir)) {
			foreach (glob("$dir/*.php") as $filename) {
				include_once $filename;
			}
			return true;
		}
		return false;
	}

	public static function loadRouter() {
		$class = self::namespaceClass('Router');
		return class_exists($class) ? $class::getInstance() : false;
	}

}
