<?
namespace MVC;
class Logger {
	use traits\Utils;

	static $enabled = true;

	static function log($msg) {
		if (!self::$enabled) return;

		$file = self::getConfig()->logDir  . self::logFile();
		file_put_contents($file, date("m/d/y h:i:s A") . " | " . $msg . PHP_EOL, FILE_APPEND);
	}

	static function logFile() {
		return ENV . "-log-" . date("j-n-y") . ".txt";
	}

	static function errorLogFile() {
		return ENV . "-errors-log-" . date("j-n-y") . ".txt";
	}

	static function pause() { self::$enabled = false; }
	static function resume() { self::$enabled = true; }

	static function clear() {
		unlink(self::getConfig()->logDir  . self::fileName()) or die("\ncount not delete log file");
	}

	static function getErrorMsg($error) {
		return date("m/d/y h:i:s A") . " | {$_SERVER['REQUEST_URI']}" . PHP_EOL . print_r($error, true);
	}

	static function logError($error) {
		error_log(
			self::getErrorMsg($error), 3,
			self::getConfig()->logDir . self::errorLogFile()
		);
	}
}
