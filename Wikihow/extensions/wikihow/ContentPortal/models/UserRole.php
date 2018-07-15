<?
namespace ContentPortal;

class UserRole extends AppModel {
	static $table_name = "cf_user_roles";
	static $belongs_to = [['user'], ['role']];

	function after_create() {
		parent::after_create();
		Event::log("User {$this->user->username} was assigned the role {$this->role->title} by *user*", Event::BLUE);
	}

	function logStr() {
		return "{$this->user->logStr()} / {$this->role->logStr()}";
	}
}