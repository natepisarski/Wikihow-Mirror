<?php
namespace MVC;
use ActiveRecord\Connection;
use ActiveRecord\Config as ArConfig;
use BadFunctionCallException;

class Config {
	static $instance;
	public $configVars = [
		'backupDir' => '~/',
		'mysqldump' => '/usr/bin/mysqldump',

		'errors' => [
			E_ERROR,
			E_WARNING,
			E_PARSE,
			E_NOTICE,
			E_CORE_ERROR,
			E_CORE_WARNING,
			E_COMPILE_ERROR,
			E_COMPILE_WARNING,
			E_USER_ERROR,
			E_USER_WARNING,
			E_USER_NOTICE,
			E_STRICT,
			E_RECOVERABLE_ERROR,
			E_DEPRECATED,
			E_USER_DEPRECATED,
			E_ALL
		],

		'db' => [
			'development' => [
				'user' => '',
				'password' => '',
				'host' => '',
				'database' => ''
			]
		]
	];

	public function __construct() {
		Connection::$datetime_format = 'Y-m-d H:i:s';
		$db = $this->db;
		ArConfig::initialize(function ($cfg) use ($db) {
			$connectionString = "mysql://{$db['user']}:{$db['password']}@{$db['host']}/{$db['database']}";
			$cfg->set_connections(['default' => $connectionString]);
			$cfg->set_default_connection('default');
		});
	}

	static function getInstance() {
		$className = get_called_class();
		self::$instance = self::$instance ? self::$instance : new $className();
		return self::$instance;
	}

	public function __set($name, $value) {
		$this->configVars[$name] = $value;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->configVars)) {
			return $this->configVars[$name];
		}
		throw new BadFunctionCallException("In Config, the property $name has not been set.");
	}
}
