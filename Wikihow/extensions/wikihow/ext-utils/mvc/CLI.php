<?php
namespace MVC;
define('SERVER_ROOT', realpath(__DIR__ . "/../../../../"));
define('CLI', true);

global $wgIsTitusServer, $wgIsProduction, $wgIsDevServer;

require_once SERVER_ROOT . "/extensions/wikihow/Misc.body.php";
require_once SERVER_ROOT . "/maintenance/Maintenance.php";
require_once SERVER_ROOT . "/extensions/wikihow/Misc.php";
// require_once SERVER_ROOT . '/wikihow_override.php';
require_once SERVER_ROOT . "/LocalKeys.php";

require __DIR__ . "/vendor/autoload.php";

use Exception;
use Maintenance;
use mnshankar\CSV\CSV;
use Colors\Color;
use __;

class CLI extends Maintenance {
	use Traits\Utils;

	public $config;
	public $router;
	public $defaultMethod = null;
	public $options = [];
	public $longOpts = [];

	public static $logSql = true;

	public function __construct() {
		$this->getOptions();
		$this->bootstrap();
		parent::__construct();
	}

	public function getOptions() {
		if (!defined('ENV')) {
			global $argv;
			array_push($this->longOpts, "env::", 'method::', 'cronjob::');
			$this->options = getopt('', $this->longOpts);

			if (!isset($this->options['cronjob'])) {
				self::trace('Available options... ' . json_encode($this->longOpts), 'Yellow');
				self::trace('Options passed...    ' . json_encode($this->options), 'Cyan');
			}
			define('ENV', $this->options['env']);
		}
	}

	public function bootstrap() {
		if (!in_array(ENV, ['staging', 'test', 'production', 'development'])) {
			$this->throwError("You must pass an --env=staging variable as development, staging, or production");
		}

		self::checkConstants();

		$this->config = self::getConfig();
		$this->router = self::loadRouter();

		if (in_array(ENV, ['test', 'development']) || isset($this->options['cronjob'])) return;

		self::trace("Would you like to backup the database before you continue? 'yes' or 'no': ", ['black', 'bg_yellow']);
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);

		if (trim($line) == 'yes'){
			$this->backupDb();
		}

		self::trace("Running in environment " . ENV, 'Green');
	}

	public function backupDb() {
		self::trace("Ok, backing up {$this->config->db['database']}", 'Green');

		$date = date("Y-m-d-H-i-s");
		$bkupStr = "{$this->config->mysqldump} --single-transaction {$this->config->db['database']} > {$this->config->backupDir}cf_$date.sql";
		self::trace($bkupStr, 'Cyan');
		self::trace("creating bkup file at {$this->config->backupDir}cf_$date.sql" . shell_exec($bkupStr), 'Green');
	}

	public function clearLog() {
		Logger::clear();
	}

	public function execute() {
		$method = array_key_exists('method', $this->options) ? $this->options['method'] : $this->defaultMethod;
		if (!$method) {
			$this->throwError('You must define a defualtMethod in your subclass of CLI, or use a method param');
		} elseif(!method_exists($this, $method)) {
			$this->throwError("there is no method $method in this script.");
		}

		self::trace("Firing $method", 'Green');

		try {
			$this->$method();
		} catch (Exception $e) {
			Errors::handleException($e);
		}
	}

	public function throwError($errorMsg) {
		self::trace($errorMsg, 'Red');
		die();
	}

	public static function toCSV($array) {
		self::trace((new CSV)->fromArray($array)->toString(), 'green');
	}

	public static function trace($str, $colors='Blue') {
		$colors = is_array($colors) ? $colors : [$colors];
		$output = (is_array($str) || is_object($str)) ? print_r($str, true) : $str;

		$color = new Color();
		$output = $color($output);

		foreach ($colors as $color) {
			$output = $output->{strtolower($color)}();
		}
		echo $output .  PHP_EOL;
	}

}
