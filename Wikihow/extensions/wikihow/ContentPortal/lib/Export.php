<?
namespace ContentPortal;
use ActiveRecord\DateTime;
use MVC\Debugger;
use __;

class Export {
	const DATE_FORMAT = "Y-m-d";

	static function getExportTypes() {

		return __::chain(Role::allButAdmin())->map(function ($role) {
			$range = UserArticle::all([
				'select'     => 'completed_at, complete',
				'order'      => 'completed_at',
				'conditions' => [
					'role_id'  => $role->id,
					'complete' => true
				]
			]);

			return [
				'key'   => $role->key,
				'pastTense' => $role->past_tense,
				'presentTense' => $role->present_tense,
				'role'  => $role,
				'start' => empty($range) ? self::today() : __::first($range)->completed_at->format(self::DATE_FORMAT),
				'end'   => empty($range) ? self::today() : __::last($range)->completed_at->format(self::DATE_FORMAT)
			];
		})->value();
	}

	static function lastDump() {
		$dir = Config::getInstance()->dumpDir;
		$files = glob("$dir*.csv");
		return empty($files) ? false : __::max($files, function ($file) {
			return filemtime($file);
		});
	}

	static function today() {
		return (new DateTime())->format(self::DATE_FORMAT);
	}

}
