<?php
namespace ContentPortal;
use \Title;
use __;

class Bulk {

	public $urls;
	public $roles;
	public $articles = [];
	public $missingUrls = [];

	static function build() {
		return new Bulk();
	}

	function fromUrls(Array $urls) {
		$this->urls = __::chain($urls)->map(function ($url) {
			return empty(trim($url)) ? null : trim(urldecode($url));
		})->uniq()->reject('is_null')->value();

		$this->articles = __::chain($this->urls)->map(function ($url) {
			$article = new Article(['title' => $url]);
			$article->is_valid();
			$match = Article::find_by_title($article->title);
			if (is_null($match)) array_push($this->missingUrls, $url);
			return $match;
		})->reject('is_null')->value();

		$this->findRoles();
		return $this;
	}

	public function findRoles() {
		$roleIds =  __::chain($this->articles)->pluck('state_id')->uniq()->value();
		$this->roles = empty($roleIds) ? [] : Role::all(['conditions' => ['id in (?)', $roleIds], 'order' => 'step']);
		return $this;
	}

	public function massAssign(User $user) {
		foreach($this->articles as $article) {
			$assignment = new Assignment($article);
			$assignment->create($user);
		}
		return $this;
	}

	public function massMarkComplete() {
		foreach($this->articles as $article) {
			//clear verify data if we're switching from "verifying"
			if ($article->state_id == Role::verify()->id) UserArticle::clearVerifyData($article);

			$assignment = new Assignment($article);
			$assignment->delete();
			$assignment->done();

			$article->state_id = Role::complete()->id;
			$article->save();

		}

		return $this;
	}

	public function massSendToEditing() {
		foreach($this->articles as $article) {
			UserArticle::clearCompleteData($article);

			$assignment = new Assignment($article);
			$assignment->delete();
			$assignment->done();

			$article->state_id = Role::edit()->id;
			$article->is_wrm = false;
			$article->save();
		}

		return $this;
	}

	function fromIds(Array $ids) {
		$this->articles = Article::all(['conditions' => ['id in (?)', $ids], 'include' => 'state']);
		$this->findRoles();
		return $this;
	}

	function done() {
		foreach($this->articles as $article) {
			$assignment = new Assignment($article);
			if (!$assignment->currentAssignment) {
				$assignment->create(currentUser());
			}
			$assignment->done();
		}

		return $this;
	}
}
