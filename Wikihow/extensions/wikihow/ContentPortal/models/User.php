<?php
namespace ContentPortal;
use __;
use ActiveRecord\DateTime;
use User as WhUser;

class User extends AppModel {

	const ADMIN_DASHBOARD = 'admin';
	const NORMAL_DASHBOARD = 'normal';

	public $whUser;
	static $table_name = "cf_users";
	static $all;

	static $has_many = [
		['user_roles'], ['user_articles', 'order' => 'updated_at ASC'], ['events'],
		['articles', 'through' => 'user_articles', 'order' => 'title ASC'],
		['active_articles', 'class' => 'Article', 'foreign_key' => 'assigned_id'],
		['roles', 'through' => 'user_roles', 'order' => 'step ASC', 'readonly' => true],
		['public_roles', 'class' => "Role", 'through' => 'user_roles', 'order' => 'step ASC', 'conditions' => 'public = 1', 'readonly' => true],
	];

	static $belongs_to = [['category']];
	static $validates_presence_of = [['username'],['category_id']];
	static $validates_uniqueness_of = [['username']];

	function is_current() {
		return currentUser()->id == $this->id;
	}

	function articlesByRole(Role $role) {
		return __::filter($this->articles, function ($article) use ($role) {
			return $article->state_id == $role->id && $article->assigned_id == $this->id;
		});
	}

	function hasRoleKey($key) {
		$keys = __::pluck($this->roles, 'key');
		return in_array($key, $keys);
	}

	function hasRoleId($roleId) {
		$roleIds = __::pluck($this->user_roles, 'role_id');
		return in_array($roleId, $roleIds);
	}

	function rolesWithAssignments() {
		$articles = Article::all(['conditions' => ['assigned_id' => $this->id], 'include' => ['state']]);
		if (empty($articles)) return [];
		$ids = __::chain($articles)->pluck('state_id')->unique()->value();

		return Role::all([
			'conditions' => "id IN (" . implode(',', $ids) . ")",
			'order' => 'step'
		]);
	}

	function whUser() {
		return WhUser::newFromId($this->wh_user_id);
	}

	function validate() {
		if ($this->isNew()) {
			$whUser = WhUser::newFromName($this->username);

			if ($whUser && $whUser->getId() !== 0) {
				$this->wh_user_id = $whUser->getId();
				return true;
			} else {
				$this->errors->add('username', "I could not find a WikiHow user with that username.");
			}
		}
	}

	function isFisher() {
		if (!Config::getInstance()->fishIntegration) return false;
		$fish = new Fishing($this);
		return $fish->userAllowed;
	}

	function kudos() {
		return Note::all(['type' => Note::KUDOS, 'recipient_id' => $this->id, 'viewed' => false]);
	}

	function isWhUser($field, $value) {
		$this->messages['isWhUser'] = "I could not find a WikiHow user by the $field \"$value\"";
		return $this->whUserExists($value);
	}

	function isAdmin() {
		return $this->hasRoleKey(Role::ADMIN_KEY);
	}

	function avatar() {
		return avatar($this);
	}

	function busy() {
		return count($this->active_articles);
	}

	static function convertFromUrl($val) {
		if (strpos($val, 'User:') !== false) {
			return explode('User:', $val)[1];
		}
		return $val;
	}

	function recentEvents() {
		return Event::all([
			"conditions" => ["logged_user_id" => $this->id],
			"limit" => 25,
			"order" => "created_at DESC"
		]);
	}

	function rejectedArticles() {
		return Article::all([
			'conditions' => ['rejected' => true, 'assigned_id' => $this->id],
			'include' => ['user_articles', 'state'],
			'limit' => 25,
			'order' => 'updated_at DESC'
		]);
	}

	// instance methods
	function approvedArticles() {
		return UserArticle::all([
			'conditions' => ['approved' => true, 'user_id' => $this->id],
			'include' => ['article' => ['user_articles'], 'role'],
			'limit' => 25,
			'order' => 'updated_at DESC'
		]);
	}

	function disable() {
		$this->update_attributes(['disabled' => true]);

		foreach($this->active_articles as $article) {
			$article->update_attributes(['assigned_id' => null]);
			(new Assignment($article))->delete();
		}

		Event::log("User __{{user.username}}__ was disabled by __{{currentUser.username}}__", Event::RED, ['user' => $this]);
		return true;
	}

	// CALLBACKS

	function before_validation_on_create() {
		$this->username = self::convertFromUrl($this->username);
	}

	function after_create() {
		parent::after_create();
		Event::log("User __{{user.username}}__ was created by __{{currentUser.username}}__", Event::GREEN, ['user' => $this]);
	}

	function before_destroy() {
		Logger::log('USER DESTROYED! -- '.$this->id);
		$con = ['conditions' => ['user_id' => $this->id]];
		UserRole::delete_all($con);
		Event::delete_all($con);
		return true;
	}

	function logStr() {
		return "{$this->username}::{$this->id}";
	}
}
