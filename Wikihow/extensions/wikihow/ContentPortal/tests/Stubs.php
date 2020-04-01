<?php
use ContentPortal\Helpers;

class Title {
	public $text;
	public static $exists = true;
	public function __construct($title) { $this->text = $title; }
	public static function newFromText($title) { return new self($title); }
	public static function newFromId($id) { return new self; }
	public function getText() {
		$segs = explode('/', $this->text);
		return str_replace('-', ' ', end($segs));
	}
	public function isRedirect() { return false; }
	public function getArticleID() { return self::$exists ? rand() : 0; }
	public function getPartialUrl() { return urlencode($this->text); }
	public function getEditUrl() { return "http://www.wikihow/{$this->text}"; }
	public function exists() { return self::$exists; }
}
class GuidedEditorHelper {
	public static function formatTitle($title) { return $title; }
}
class Misc {
	public static function getTitleFromText($title) { return new Title($title); }
}
class User {
	public $id;
	function __construct($id=null) { $this->id = $id; }
	function getGroups() { return []; }
	function isBlocked() { return false; }
	function getId() { return $this->id; }
	function isLoggedIn() { return false; }
	static function newFromName($name) { return new User(); }
}
class Avatar { static function getAvatarURL() { return ''; }}
