<?php
namespace ContentPortal;
use ActiveRecord\Cache;
class Category extends AppModel {
	static $table_name = "cf_categories";
	static $has_many = ["articles", "users"];
	static $validates_presence_of = [['title']];
	static $validates_uniqueness_of = [['title']];
	static $all = null;


	function isUsed() {
		return $this->users || $this->articles;
	}

	static function findOrCreate($title) {
		$title = ucwords($title);
		$existing = Category::find_by_title($title);
		return $existing ? $existing : Category::create(['title' => $title]);
	}

	// CALLBACKS

	function before_validate() {
		$this->title = ucwords($this->title);
	}

	function logStr() {
		return "{$this->title}::{$this->id}";
	}

	function after_create() {
		parent::after_create();
		self::$all = null;
		Event::log("Category __{{category.title}}__ was created by __{{currentUser.username}}__.", Event::GREEN, ['category' => $this]);
	}

	function after_update() {
		parent::after_update();
		Event::log("Category __{{category.title}}__ was modified by {{currentUser.username}}__.", Event::GREEN, ['category' => $this]);
	}

	function after_destroy() {
		parent::after_destroy();
		Event::log("Category __{{category.title}}__ was deleted by __{{currentUser.username}}__", Event::RED, ['category' => $this]);
	}
}
