<?
namespace MVC;
require_once __DIR__ . "/CLI.php";

use ActiveRecord\Table;
use ActiveRecord\Cache;
use __;

class Migration extends Model {
	static $table_name = "cf_migrations";
}

class Migrate extends CLI {
	use Traits\Utils;

	public $defaultMethod = "checkMigrations";
	public $version = null;
	public $groups = [];

	public function rollback() {
		$this->getVersion();
		Migration::last()->delete();
		self::trace('Rolling back to previous version...', 'Green');
		$this->getVersion();
	}

	public function getVersion() {
		$max = Migration::last();
		$this->version = $max->version ? $max->version : 0;
		self::trace("Current Version " . $this->version, 'Cyan');
	}

	public function checkMigrations() {
		self::trace("Checking for migrations...", 'Yellow');
		$this->getVersion();
		self::trace("Desired Version " . $this->maxVersion(), 'Cyan');

		if (self::maxVersion() > $this->version) {
			$this->runMigrations();
			self::trace("Current Version " . Migration::last()->version, 'Cyan');
		} else {
			self::trace("No Migrations needed. You are all set!", 'Green');
		}
	}

	public function updateVersion($version, $methods) {
		Migration::create([
			'version' => $version,
			'methods_ran' => implode(',', $methods)
		]);
	}

	public function addTable($table) {
		Migration::run(
			"CREATE TABLE IF NOT EXISTS `$table` "
			. "(`id` int(11) unsigned NOT NULL AUTO_INCREMENT, "
			. "`created_at` datetime DEFAULT NULL, "
			. "`updated_at` datetime DEFAULT NULL, "
			. "PRIMARY KEY (`id`)"
			. ") ENGINE=InnoDB DEFAULT CHARSET=utf8;"
		);

		self::trace("Creating table $table", 'Yellow');
		Table::clear_cache();
	}

	public function runMigrations() {
		foreach($this->groups as $group) {
			if ($group[0] > $this->version) {
				self::trace("Migrating to version {$group[0]}", 'yellow');
				$methods = [];
				foreach($group as $methodName) {
					if (!is_numeric($methodName)) {
						self::trace("Running function {$methodName}", 'Green');
						$this->$methodName();
						array_push($methods, $methodName);
						$configClass = self::namespaceClass('AppConfig');
					}
				}
				$this->updateVersion($group[0], $methods);
			}
		}
	}

	public function maxVersion() {
		return max(array_map(function ($val) {
			return $val[0];
		}, $this->groups));
	}
}
