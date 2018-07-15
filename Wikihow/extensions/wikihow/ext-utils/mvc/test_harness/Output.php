<?
namespace MVC;
class Output {
	static $instance = null;
	static $html = "";
	static $redirect;
	static $error = false;
	static $notFound = false;

	static function getInstance() {
		self::$instance = is_null(self::$instance) ? new Output() : self::$instance;
		return self::$instance;
	}

	static function reset() {
		self::$redirect = null;
		self::$error = false;
		self::$notFound = false;
		self::$html = '';
	}

	function setStatusCode($code) {
		if ($code == 404) {
			self::$notFound = true;
		} else if ($code == 500) {
			self::$error = true;
		}
	}

	function redirect($path) {
		$path = str_replace(url(''), '', $path);
		self::$redirect = parse_url($path);
	}

	function addHtml($html) {
		self::$html .= $html;
	}

	function setArticleBodyOnly() {}

	static function showErrorPage() {
		self::$error = true;
	}
	static function setRobotPolicy($params) {}

	static function contains($string) {
		return strpos(self::$html, $string) !== false ? true : false;
	}

	static function wasRedirect() {
		return is_null(self::$redirect) ? false : true;
	}
}
