<?php
namespace ContentPortal;
use __;

class Import {
	use ImportValidator;

	public $errors          = [];
	public $header          = [];
	public $data            = [];
	public $validArticles   = [];
	public $invalidArticles = [];

	function __construct($files) {
		$this->file = isset($files['csv']) ? $files['csv'] : null;
		Event::$silent = true;
		$this->validateFile();
	}

	function articleGroups() {
		return [
			'with_errors' => $this->invalidArticles,
			'wrm'         => __::filter($this->validArticles, ['is_wrm' => true, 'wh_article_id' => null]),
			'existing'    => __::filter($this->validArticles, ['is_wrm' => false]),
			'wrm_with_id' => __::chain($this->validArticles)->filter(['is_wrm' => true])->filter(function ($article) {
				return is_numeric($article->wh_article_id);
			})->value()
		];
	}

	function associate($item) {
		$row = [];
		foreach($this->header as $index => $key) {
			$row[$key] = $item[$index];
		}
		return $row;
	}

	function build() {
		$this->data   = array_map('str_getcsv', file($this->file['tmp_name']));
		$this->header = array_map([$this, 'formatKey'], array_shift($this->data));
		$this->data   = array_map([$this, 'associate'], $this->data);

		foreach($this->data as $item) {
			$role = Role::findByAnything($item['state']);
			$article = new Article();
			$article->validateTitle = true;

			$article->is_wrm        = isset($item['is_wrm']) && strtolower($item['is_wrm']) == 'true' ? true : false;
			$article->title         = isset($item['url']) ? $item['url'] : $item['article_id'];
			$article->state_id      = $role ? $role->id : Role::write()->id;
			$article->category_id   = $this->findOrCreateCategory($item['category'])->id;

			$user = $this->findUser($item['url_to_user'], $article);

			$valid = $article->is_valid();
			$article->assigned_id = $user ? $user->id : null;

			if ($item['url_to_user'] && is_null($user)) {
				$article->errors->add('assigned_id', "I could not find the user {$item['url_to_user']}");
			}

			//add notes on import
			$article->import_notes = isset($item['notes']) ? $item['notes'] : null;

			$target = $article->is_valid() ? 'validArticles' : 'invalidArticles';
			array_push($this->{$target}, $article);
		}
	}

}
