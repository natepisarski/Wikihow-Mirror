<?php
namespace ContentPortal;

class UserArticle extends AppModel {
	static $table_name = "cf_user_articles";
	static $belongs_to = [
		['user'], ['article'], ['role']
	];

	function approvedBy() {
		if ($this->approved) {
			$nextStep = $this->role->nextStep();
			$nextStepAssoc = UserArticle::find(['article_id' => $this->article_id, 'role_id' => $nextStep->id]);
			return $nextStepAssoc ? $nextStepAssoc->user : null;
		}

		return null;
	}

	static function usernameForRole($key, $article) {
		$roleId = Role::findByKey($key)->id;
		$assoc = __::find($article->user_articles, ['role_id' => $roleId]);
		return $assoc ? $assoc->user->username : null;
	}

	static function userForRole($key, $article) {
		$roleId = Role::findByKey($key)->id;
		$assoc = __::find($article->user_articles, ['role_id' => $roleId]);
		return $assoc ? $assoc->user : null;
	}

	static function dateRoleCompleted($key, $article, $format) {
		$assoc = __::find($article->user_articles, [
			'role_id' => Role::findByKey($key)->id,
			'complete' => true
		]);
		return $assoc ? $assoc->completed_at->format($format) : null;
	}

	static function unassignIfRoleAbsent($user) {
		foreach($user->active_articles as $article) {
			if (!$user->hasRoleId($article->state_id) && !$user->hasRoleKey(Role::ADMIN_KEY)) {
				Assignment::build($article)->delete();
			}
		}
	}

	/**
	 * clearVerifyData()
	 *
	 * removes:
	 * - verifier
	 * - verified data
	 * - previous Verifier Feedback person
	 */
	static function clearVerifyData($article) {
		if ($article) {
			$con = ['conditions' => [
				'article_id' => $article->id,
				'role_id' => [
					Role::verify()->id,
					Role::needsRevision()->id
				]
			]];
			UserArticle::delete_all($con);
		}
	}

	static function clearCompleteData($article) {
		if ($article) {
			$con = ['conditions' => [
				'article_id' => $article->id,
				'role_id' => [
					Role::complete()->id,
					Role::review()->id,
					Role::edit()->id
				]
			]];
			UserArticle::delete_all($con);
		}
	}

	// CALLBACKS

	function after_update() {
		parent::after_update();
		if ($this->role->key == Role::NEEDS_REVISION_KEY && $this->complete) {
			Document::create([
				'article_id' => $this->article_id,
				'type' => Document::VERIFY
			]);
		}
		return true;
	}

	function logStr() {
		return "{$this->article->logStr()} / {$this->role->logStr()} / {$this->user->logStr()}";
	}
}
