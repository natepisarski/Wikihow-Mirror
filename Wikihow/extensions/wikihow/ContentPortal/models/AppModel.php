<?
namespace ContentPortal;
use MVC\Model;
use ActiveRecord\DateTime;
use Mustache_Engine;
use MVC\Logger;
use __;

class AppModel extends Model {

	public $skipEvent = false;
	public $skipLog = false;
	static $all;
	const CSV_DATE_FORMAT = 'm/d/y';

	function briefAtts($only=[]) {
		$atts = [];
		foreach($only as $key) {
			$atts[$key] = $this->$key;
		}
		return $atts;
	}

	function touch() {
		$this->skipLog = true;
		$this->update_attribute('updated_at', new DateTime());
		$this->skipLog = false;
	}

	static function allFromCache() {
		if (!Config::getInstance()->cacheModels || is_null(static::$all)) {
			static::$all = self::all();
		}
		return static::$all;
	}

	static function findAllInCache($conditions) {
		return static::allFromCache()->where($conditions);
	}

	static function findInCache($conditions) {
		return static::findAllInCache($conditions)->first();
	}

	function getLogVars() {
		return [
			"currentUser" => Auth::findCurrentUser(),
			"identifier" => $this->logStr(),
			"table" => static::$table_name,
			"id" => $this->id
		];
	}

	function logCRUD($event) {
		if ($this->skipLog) return;
		$msg = (new Mustache_Engine)->render("$event {{table}}::{{id}} | {{currentUser.username}}::{{currentUser.id}} | {{identifier}}", $this->getLogVars());
		Logger::log($msg);
	}

	function logStr() { return "N/A"; }
	function after_create() { $this->logCRUD('create'); }
	function after_update() { $this->logCRUD('update'); }
	function after_destroy() { $this->logCRUD('delete'); }

}
