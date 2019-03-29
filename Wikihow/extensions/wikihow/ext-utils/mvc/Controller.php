<?php
namespace MVC;

class Controller {
	use Traits\Rendering;
	static $instance = null;
	public $debug = true;
	public $viewVars = [];
	public $out;
	public $viewDir = "views/";
	public $inlineScripts = [];
	public $postRoutes = [];
	public $continue = true;
	public $layout = 'application';
	public $template;

	public function __construct($action=null) {
		$this->action = $action;
		$this->out = Router::getInstance()->getOutput();
		$this->out->setArticleBodyOnly(true);
		static::$instance = $this;
		$this->checkRequestType();
		$this->beforeRender();
	}

	public static function getInstance() {
		return static::$instance;
	}

	public function checkRequestType() {
		if (in_array($this->action, $this->postRoutes) && empty($_POST)) $this->render404();
	}

	public function redirectTo($path, $params=[]) {
		$url = (strpos($path, ':') === false) ? url($path, $params) : $path;
		$this->out->redirect($url, $responsecode = '302');
		$this->continue = false;
	}

	public function isAjax() {
		return $this->params('ajax') || isset($_SERVER) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	public function requestFormat() {
		return isset($_GET['format']) ? $_GET['format'] : 'html';
	}

	public function beforeRender() {}

	public function addError($message) {
		if (!isset($this->viewVars['errors'])) {
			$this->viewVars['errors'] = [];
		}

		array_push($this->viewVars['errors'], [
			'message' => $message
		]);
	}

	public function allParams() {
		$black = ['controller', 'action', 'title'];
		$params = $_POST;

		foreach($_GET as $key => $value) {
			if (!in_array($key, $black)) {
				$params[$key] = $value;
			}
		}
		return $params;
	}

	public function params($key=null, $default=null) {
		$params = $this->allParams();

		if (is_null($key)) {
			return $params;
		}
		// supporting 'params[key]' type accessing
		if (strpos($key, '[') !== false) {
			$segs = explode('[', $key);
			$key = [$segs[0] => str_replace(']', '', end($segs))];
		}

		if (is_array($key)) {
			foreach($key as $k => $v) {
				$params = array_key_exists($k, $params) ? $params[$k] : [];
				$key = $v;
			}
		}
		return array_key_exists($key, $params) ? $params[$key] : (is_null($default) ? null : $default);
	}

	public function __set($name, $value) {
		if (property_exists($this, $name)) {
			$this->$name = $value;
		}
		$this->viewVars[$name] = $value;
	}

	public function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		return $this->viewVars[$name];
	}

	public function __call($method, $params) {
		$this->render404();
	}
}
