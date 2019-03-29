<?php
namespace MVC;
use Mustache_Engine;
use __;
use MWExceptionHandler;

class Errors {
	use Traits\Utils;

	static $config;

	static $fatalErrors = [
		E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR
	];

	public static function initialize() {
		self::$config = self::getConfig();
		set_error_handler('\\MVC\\Errors::handleError');
	}

	public static function trigger($errorMsg) {
		trigger_error($errorMsg, E_USER_ERROR);
	}

	public static function handleException($e) {
		$error = __::find($e->getTrace(), function ($trace) {
			if (!array_key_exists('file', $trace) ) return false;
			return
				strpos($trace['file'], 'vendor') == false &&
				strpos($trace['file'], __DIR__) !== false ||
				strpos($trace['file'], sys_get_temp_dir()) !== false ||
				strpos($trace['file'], APP_DIR) !== false;
		});

		self::handleError(E_ERROR, $e->getMessage(), $error['file'], $error['line']);
	}

	public static function handleError($errno, $errstr, $file, $line) {
		if (!self::shouldDisplay($errno, $file)) return;

		$seg = explode("\n", file_get_contents($file));
		$start = $line - 4 < 0 ? 0 : $line - 4;
		$vars = [
			'errstr' => $errstr,
			'start' => $start,
			'line' => $line,
			'lines' => array_slice($seg, $start, 7),
			'id' => uniqid(),
			'file' => __::last(explode(APP_DIR, $file)),
			'mark' => $line - $start
		];

		Logger::logError("$errno | $errstr" . PHP_EOL . "$file::$line" . PHP_EOL);

		// are we in CLI mode?
		if (defined('CLI')) {
			// only fatal on CLI
			if (!self::isFatal($errno)) return true;

			CLI::trace(PHP_EOL);
			CLI::trace("$errno | $errstr", ['red', 'bold']);
			CLI::trace("$file::$line", ['yellow']);

			foreach($vars['lines'] as $index => $line) {
				$color = ($index + 1) == $vars['mark'] ? ['bg_yellow', 'black'] : 'cyan';
				CLI::trace((sprintf("%08d", $index + 1) + $vars['start']) . "| $line", $color);
			}

		} else if (self::$config->showErrors) {
			$tmpl =  file_get_contents(__DIR__ . '/templates/error.mustache');
			echo (new Mustache_Engine)->render($tmpl, $vars);

		} else {
			if (self::isFatal($errno)) {
				http_response_code(500);
				include('templates/500.html');
			}
		}

		if (self::isFatal($errno)) exit(1);
		return true;
	}

	public static function isFatal($errno) {
		return in_array($errno, self::$fatalErrors);
	}

	public static function shouldDisplay($errno, $file) {
		if (!in_array($errno, self::$config->errors)) return false;
		// only want fatal on CLI
		if (!self::isFatal($errno) && defined('CLI')) return false;
		// we want haml errors
		if (strpos($file, 'haml') !== false) return true;
		// we want haml errors
		if (strpos($file, 'MtHaml') !== false) return true;
		// dont want vendor errors
		if (strpos($file, 'vendor') !== false) return false;
		// we want mvc errors
		if (strpos($file, __DIR__) !== false) return true;
		// we want app errors
		if (strpos($file, APP_DIR) !== false) return true;
	}

}
