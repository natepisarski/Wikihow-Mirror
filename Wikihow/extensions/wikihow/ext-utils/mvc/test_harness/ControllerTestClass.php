<?php
namespace MVC;
use PHPUnit_Framework_TestCase;
use MVC\Output;
use Exception;
use ActiveRecord\Inflector;

class ControllerTestClass extends PHPUnit_Framework_TestCase {

	public $baseUrl = null;

	function setup() {
		Output::reset();
	}

	function baseUrl() {
		if (is_null($this->baseUrl)) {
			$arr = explode('\\', Inflector::instance()->underscorify(get_class($this)));
			$arr = explode('_Controller', end($arr));
			$this->baseUrl = strtolower($arr[0]);
		}
		return $this->baseUrl;
	}

	function get($action, $get=[]) {
		Output::reset();
		$_GET = $this->processGet($get);
		Router::getInstance()->execute($this->baseUrl() . '/' . $action);
		return $this;
	}

	function post($action, $post=[], $get=[]) {
		Output::reset();
		$_GET = $this->processGet($get);
		$_POST = $post;
		Router::getInstance()->execute($this->baseUrl() . '/' . $action);
		return $this;
	}

	function getOutput() {
		global $wgMimeType;
		$out = Output::$html;
		return $wgMimeType == "application/json" ? json_decode($out) : $out;
	}

	function controller() {
		return Router::getInstance()->ctrl;
	}

	private function processGet($get) {
		return is_array($get) ? $get : ['id' => $get->id];
	}

	function assertOnPage($string, $message="should be on page") {
		$this->stopIfError(__METHOD__);
		$this->assertTrue(Output::contains($string), $message);
		return $this;
	}

	function assertNotOnPage($string, $message="should not be on page") {
		$this->stopIfError(__METHOD__);
		$this->assertFalse(Output::contains($string), $message);
		return $this;
	}

	function getVars() {
		return Router::getInstance()->ctrl->viewVars;
	}

	function getJSON() {
		return json_decode(Output::$html);
	}

	function assertHasVar($key, $message='should have var', $showKeys=false) {
		$this->stopIfError(__METHOD__);
		if ($showKeys) debug(array_keys(Router::getInstance()->ctrl->viewVars));

		$this->assertTrue(
			array_key_exists($key, Router::getInstance()->ctrl->viewVars),
			$message . " | $key"
		);
		return $this;
	}

	function assertWasRedirect($message='should have been a redirect') {
		$this->stopIfError(__METHOD__);
		$this->assertTrue(Output::wasRedirect(), $message);
		return $this;
	}

	function assert500($message='should have been a 500') {
		$this->assertTrue(Output::$error, $message);
		return $this;
	}

	function assert404($message='should have been a 404') {
		$this->assertTrue(Output::$notFound, $message);
		return $this;
	}

	function assertWasNotRedirect($message='was not a redirect') {
		$this->stopIfError(__METHOD__);
		$this->assertFalse(Output::wasRedirect(), $message);
		return $this;
	}

	function assertWasRedirectTo($path, $message='was a redirect to') {
		$this->stopIfError(__METHOD__);

		$path = $this->baseUrl() . '/' .$path;
		$path = str_replace('/index', '', $path);

		$redirect = Output::$redirect ? Output::$redirect['path'] : '';
		$redirect = str_replace('/index', '', $redirect);

		$this->assertEquals($redirect, $path, $message);
		return $this;
	}

	function assertTemplate($template, $message='template should be') {
		$this->stopIfError(__METHOD__);
		$template = count(explode('/', $template)) == 1 ? $this->baseUrl() . "/" . $template : $template;
		$rendered = Router::getInstance()->ctrl->template;
		$this->assertEquals($rendered, $template, $message);
		return $this;
	}

	function stopIfError($method) {
		if (Output::$error) {
			throw new Exception($method . " could not continue, Controller rendered an error on {$_GET['controller']}/{$_GET['action']}", 1);
		} else if (Output::$notFound) {
			throw new Exception($method . " could not continue, request was a 404 on {$_GET['controller']}/{$_GET['action']}", 1);
		}
	}

	public function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		return Controller::getInstance()->viewVars[$name];
	}

}
