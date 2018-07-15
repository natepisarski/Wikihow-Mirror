<?php
namespace MVC;

$composerAutoload = __DIR__ . "/vendor/autoload.php";
if (file_exists($composerAutoload)) {
    require $composerAutoload;
} else {
	die("You need to run composer in " . __DIR__ . " for MVC library to work. $composerAutoload doesn't exist yet.");
}

if (!class_exists('UnlistedSpecialPage')) {
	require_once realpath(__DIR__ . "/../../../../") . "/includes/specialpage/SpecialPage.php";
	require_once realpath(__DIR__ . "/../../../../") . "/includes/specialpage/UnlistedSpecialPage.php";
}

use UnlistedSpecialPage;
use Exception;
use MVC\Errors;

class Router extends UnlistedSpecialPage {
	use Traits\Utils;

	static $instance = null;
	public $groupWhiteList = ['*'];
	public $rootPath = "";
	public $ctrl;
	public $mvcDir = __DIR__;
	public $config;

	public function __construct() {
		global $wgHooks, $wgIsDevServer;

		if (!defined('CLI') && session_status() == PHP_SESSION_NONE) session_start();
		self::checkConstants();


		if (!defined('ENV')) {
			define('HOST', parse_url($_SERVER['HTTP_HOST'])['path']);
			define('ENV', (HOST == 'localhost') ? 'development' : ($wgIsDevServer ? 'staging' : 'production'));
		}

		$this->config = self::getConfig();
		Errors::initialize();
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');

		parent::__construct(APP_NS);
		static::$instance = $this;

		self::includeDir(__DIR__ . "/helpers");
		self::includeDir(APP_DIR . "/helpers");

		if (!defined('CLI') && defined('DISABLED') && DISABLED) {
			include __DIR__ . "/templates/maintenance.html";
			die();
		}
	}

	static function getInstance() {
		static::$instance = static::$instance ? static::$instance : new static();
		return static::$instance;
	}

	public function execute($subPage) {
		$this->setHeaders();
		$subPage = rtrim($subPage,"/");

		if (!$this->userAllowed()){
			$this->ctrl = new Controller();
			$this->ctrl->render404();
			return;
		}

		$segments = explode('/', $subPage);

		// go to root url if there is no subPage;
		if (empty($segments[0])) {

			$segments = explode('/', $this->rootPath);
			if (empty($segments[0])) {
				// Errors::trigger('you must override $this->rootPath in your router!');
			}
		}

		$controller = self::namespaceClass(ucfirst($segments[0]) . "Controller");
		$action = count($segments) > 1 ? $segments[1] : "index";
		// get around reserved keyword new
		$this->currentUrl = "{$segments[0]}/$action";
		$action = $action == "new" ? "_new" : $action;

		$_GET['action'] = $action;
		$_GET['controller'] = $segments[0];

		if (class_exists($controller)) {

			try {
				$this->ctrl = new $controller($action);
				if ($this->ctrl->continue) { $this->ctrl->$action(); }

				if ($this->ctrl->continue) {
					$this->ctrl->render($this->currentUrl, $this->ctrl->layout);
				}
			} catch (Exception $e) {
				Errors::handleException($e);
			}

		} else {
			$this->ctrl = new Controller(null);
			$this->ctrl->render404();
		}
	}

	public function userAllowed() {
		global $wgUser;
		if (is_null($wgUser)) return false;

		$userGroups = $wgUser->getGroups();
		array_push($userGroups, '*');
		$allowed = array_intersect($this->groupWhiteList, $userGroups);

		if ($wgUser->isBlocked() || empty($allowed)) {
			return false;
		}
		return true;
	}

}
