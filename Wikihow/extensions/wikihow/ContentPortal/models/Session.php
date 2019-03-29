<?php
namespace ContentPortal;

class Session extends AppModel {
	public $skipLog = true;
	static $table_name = "cf_sessions";
	static $belongs_to = ["user"];

	static function getSessionId() {
		return isset($_COOKIE['wiki_shared_session']) ? $_COOKIE['wiki_shared_session'] : null;
	}

	static function findBySession() {
		return self::getSessionId() ? self::find(['wiki_shared_session' => self::getSessionId()]) : null;
	}

	static function build(User $user) {
		$sess = new Session(['user_id' => $user->id]);
		$sess->save();
		return $sess;
	}

	static function destroy() {
		$sess = self::findBySession();
		if ($sess) $sess->delete();
	}

	// CALLBACKS

	function before_create() {
		self::table()->delete(['wiki_shared_session' => self::getSessionId()]);
		$this->wiki_shared_session = self::getSessionId();
	}
}
