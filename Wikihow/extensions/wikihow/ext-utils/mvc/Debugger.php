<?
namespace MVC;
use Mustache_Engine;
use __;

class Debugger {

	static $queries = [];
	static $objects = [];
	static $disabled = false;

	public static function traceQuery($query) {
		if (self::$disabled) return;
		if (defined('CLI') && CLI::$logSql) CLI::trace($query, 'light_magenta');
		array_push(self::$queries, $query);
	}

	public static function traceObj($obj, $die=false) {
		$tmpl = file_get_contents(__DIR__ . '/templates/debug_trace.mustache');

		$caller = debug_backtrace()[1];
		$file = __::last(explode(APP_DIR, $caller['file']));

		$html = (new Mustache_Engine)->render($tmpl, [
			'obj' => print_r($obj, true),
			'file' => $file,
			'line' => $caller['line']
		]);

		if ($die) die($html);
		return $html;
	}

	public static function render() {
		self::debug(NULL, false);
	}

	public static function debugObj($obj) {
		array_push(self::$objects, $obj);
	}

	public static function debug() {
		$tmpl = file_get_contents(__DIR__ . '/templates/debug_console.mustache');
		$html = (new Mustache_Engine)->render($tmpl, [
			'objects' => json_encode(self::$objects),
			'queries' => self::$queries,
			'queryCount' => count(self::$queries),
			'get' => json_encode($_GET),
			'post' => json_encode($_POST)
		]);

		Router::getInstance()->getOutput()->addHtml($html);
	}
}
