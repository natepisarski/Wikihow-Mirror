<?php
global $IP;
require_once "$IP/extensions/wikihow/EasyTemplate.php";

use MVC\Output;

class TestObj {
	public $field = null;
	public $field2 = null;
	function __construct($field, $field2) {
		$this->field = $field;
		$this->field2 = $field2;
	}

	function retrieve($args) {
		return $this->$args[0];
	}
}

function wfProfileIn($method) {}
function wfProfileOut($method) {}

class UnlistedSpecialPage {
	function __construct() { }
	function getOutput() { return Output::getInstance(); }
	static function setHeaders() {}
}
