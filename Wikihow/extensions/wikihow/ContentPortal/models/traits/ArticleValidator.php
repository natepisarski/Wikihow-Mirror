<?php

namespace ContentPortal;

use Title;
use GuidedEditorHelper;
use Misc;

class ArticleValidator {

	public $whTitle;

	public function __construct(Article $article) {
		$this->article = $article;
		if (is_null($this->article->state_id)) $this->article->state_id = Role::write()->id;

		$this->article->title = trim($this->article->title);
		if (empty($this->article->title)) return;

		$this->article->title = urldecode($this->article->title);
		$this->findTitle();
		if (is_null($this->whTitle)) $this->buildTitle();
	}

	public function validate() {
		if (!$this->article->is_wrm) {
			if ($this->whTitle && !$this->whTitle->exists()) {
				$this->article->errors->add('title', "I could not find that WikiHow article. Does it exist?");
				return;
			}
		}
	}

	public function buildTitle() {
		$this->cleanTitle();
		$this->whTitle = Title::newFromText($this->article->title);
		$this->updateFields();
	}

	public function findTitle() {
		$this->whTitle = Misc::getTitleFromText($this->article->title);
		$this->updateFields();
	}

	public function updateFields() {
		if (is_null($this->whTitle)) return;

		$this->article->wh_article_id  = $this->whTitle->exists() ? $this->whTitle->getArticleID() : null;
		$this->article->title          = $this->whTitle->getText();
		$this->article->wh_article_url = URL_PREFIX . $this->whTitle->getPartialUrl();
		$this->article->is_redirect    = $this->whTitle->isRedirect();
	}

	public function cleanTitle() {
		// remove the url
		$segs = explode('/', $this->article->title);
		$this->article->title = end($segs);
		// strip the dashes
		$this->article->title = str_replace("-", " ", $this->article->title);
		$this->article->title = GuidedEditorHelper::formatTitle($this->article->title);
	}

	public function updateUrl() {
		$this->article->wh_article_url = URL_PREFIX . $this->whTitle->getPartialUrl();
	}

	// public function syncWithUrl($url) {
	// 	$segs = explode('/', $url);
	// 	$title = end($segs);
	// 	$this->updateFromTitle(Title::newFromText($title));
	// }

	// public function syncWithId($id=null) {
	// 	$id = is_null($id) ? $this->wh_article_id : $id;
	// 	if (is_null($id)) return;
	// 	$this->updateFromTitle(Title::newFromId($id));
	// }

	// public function updateFromTitle(Title $title) {
	// 	$vals = [
	// 		'title'          => $title->getText(),
	// 		'wh_article_url' => URL_PREFIX . $title->getPartialUrl()
	// 	];

	// 	if ($title->exists()) {
	// 		$vals['wh_article_id'] = $title->getArticleId();
	// 	}

	// 	$this->update_attributes($vals);
	// }


}
