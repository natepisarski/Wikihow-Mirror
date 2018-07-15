<?
namespace ContentPortal;
use __;

class Role extends AppModel {

	static $all;

	const WRITE_KEY             = 'write';
	const PROOF_READ_KEY        = 'proof_read';
	const EDIT_KEY              = 'edit';
	const ADMIN_KEY             = 'admin';
	const REVIEW_KEY            = 'review';
	const NEEDS_REVISION_KEY    = 'needs_verifier_revision';
	const VERIFY_KEY            = 'verify';
	const COMPLETE_KEY          = 'complete';
	const COMPLETE_VERIFIED_KEY = 'complete-verified';
	const BLOCKING_QUESTION_KEY = 'question';
	const RESERVE_ARTICLE_KEY   = 'reserve';

	static $table_name = "cf_roles";
	static $has_many = [
		['user_roles'],
		['users', 'through' => 'user_roles', 'conditions' => 'disabled = 0'],
		['articles', 'through' => 'user_roles']
	];
	static $belongs_to = [
		['revert_step', 'class' => "Role", 'foreign_key' => 'prev_step_id']
	];

	function nextStep() {
		return $this->next_step_id ? __::find(self::allFromCache(), ['id' => $this->next_step_id]) : null;
	}

	function prevStep() {
		return $this->prev_step_id ? __::find(self::allFromCache(), ['id' => $this->prev_step_id]) : null;
	}

	static function write()            { return __::find(self::allFromCache(), ['key' => self::WRITE_KEY             ]); }
	static function proofRead()        { return __::find(self::allFromCache(), ['key' => self::PROOF_READ_KEY        ]); }
	static function edit()             { return __::find(self::allFromCache(), ['key' => self::EDIT_KEY              ]); }
	static function review()           { return __::find(self::allFromCache(), ['key' => self::REVIEW_KEY            ]); }
	static function complete()         { return __::find(self::allFromCache(), ['key' => self::COMPLETE_KEY          ]); }
	static function needsRevision()    { return __::find(self::allFromCache(), ['key' => self::NEEDS_REVISION_KEY    ]); }
	static function verify()           { return __::find(self::allFromCache(), ['key' => self::VERIFY_KEY            ]); }
	static function admin()            { return __::find(self::allFromCache(), ['key' => self::ADMIN_KEY             ]); }
	static function verifiedComplete() { return __::find(self::allFromCache(), ['key' => self::COMPLETE_VERIFIED_KEY ]); }
	static function blockingQuestion() { return __::find(self::allFromCache(), ['key' => self::BLOCKING_QUESTION_KEY ]); }
	static function reserveArticle()   { return __::find(self::allFromCache(), ['key' => self::RESERVE_ARTICLE_KEY   ]); }

	static function findByTitle($title) {
		return __::find(self::allFromCache(), ['title' => ucfirst($title)]);
	}

	static function findByAnything($string) {
		$string = ucwords($string);
		return __::chain(self::allFromCache())->filter(function ($role) use ($string) {
			return $role->key == strtolower($string) || $role->title == $string || $role->present_tense == $string || $role->past_tense == $string;
		})->first()->value();
	}

	static function findByKey($key) {
		return self::allFromCache()->find(['key' => $key]);
	}

	static function getArticleRoles($state_id) {
		if ($state_id == self::verifiedComplete()->id) {
			return self::allForVerified();
		}
		else {
			return self::allButAdmin();
		}
	}

	static function allButAdmin() {
		return __::reject(self::all(['order' => 'step']), function ($role) {
			return $role->key == self::ADMIN_KEY;
		});
	}

	static function allForVerified() {
		return self::all(['conditions' => ['key' => [self::COMPLETE_KEY, self::VERIFY_KEY, self::COMPLETE_VERIFIED_KEY]]]);
	}

	static function publicRoles() {
		return self::all(['conditions' => ['public' => true], 'order' => 'step ASC']);
	}

	static function allKeys() {
		return __::pluck(self::publicRoles(), 'key');
	}

	static function findById($id) {
		return __::find(self::allFromCache(), ['id' => $id]);
	}

	function enabledUsers() {
		return __::select($this->users, ['disabled' => 0]);
	}

	function logStr() {
		return "{$this->title}::{$this->id}";
	}

	function canAssign() {
		return in_array($this->key, [self::COMPLETE_KEY, self::COMPLETE_VERIFIED_KEY]) ? false : true;
	}
}
