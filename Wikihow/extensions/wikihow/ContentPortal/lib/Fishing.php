<?php
namespace ContentPortal;

use WAPDB;
use __;
use EditfishArtist;
use WAPArticleTagDB;
use EditfishArticle;
use MVC\Logger;

class Fishing {

	const ARTICLE_TABLE = 'editfish_articles';
	const TAG_TABLE = 'editfish_tags';
	const LOOKUP_TABLE = 'editfish_article_tags';
	const ARTICLE_LIMIT = 7;
	const MSG_ALREADY_RESERVED = "Article is already in the portal and cannot be reserved.";
	const MSG_HIT_LIMIT = "You have reached the limit of articles you can reserve.";
	public $artist;
	public $db;
	public $config;
	public $userAllowed;
	public $errors = [];

	public function __construct(User $user) {
		$this->user = $user;
		$this->db = WAPDB::getInstance(WAPDB::DB_EDITFISH);
		$this->artist = EditfishArtist::newFromName($user->username, WAPDB::DB_EDITFISH);

		$allNames = __::pluck(__::pluck($this->allUsers(), 'u'), 'mName');
		$this->userAllowed = in_array($this->artist->u->mName, $allNames);
	}

	public function assignArticle($params) {
		if ($this->inPortal($params['wh_article_id'])) {
			array_push($this->errors, self::MSG_ALREADY_RESERVED);
			Logger::log("Portalfish | {$params['articleId']} was already in the portal | " . currentUser()->id . "::" . currentUser()->username);
			return false;
		} elseif ($this->hasHitLimit()) {
			array_push($this->errors, self::MSG_HIT_LIMIT);
			return false;
		}

		$this->db->reserveArticle($params['wh_article_id'], 'en', $this->artist);
		$this->cloneArticle($params);
		return true;
	}

	public function hasHitLimit() {
		if ($this->user->isAdmin()) return false;
		return count($this->user->articlesByRole(Role::edit())) >= self::ARTICLE_LIMIT;
	}

	public function cloneArticle($params) {
		if ($this->inPortal($params['wh_article_id'])) return;
		$article = new Article([
			'title' => $params['wh_article_id'],
			'state_id' => Role::edit()->id,
			'category_id' => Category::findOrCreate($params['tag'])->id
		]);

		$article->save();

		if ($params['notes']) {
			Note::create([
				'message' => $params['notes'],
				'article_id' => $article->id,
				'role_id' => Role::reserveArticle()->id,
				'type' => Note::INFO
			]);
		}

		Assignment::build($article)->create($this->user);
	}

	public function findWapArticle($id) {
		return __::first($this->db->getArticles([$id]));
	}

	public function inPortal($articleId) {
		return Article::exists(['wh_article_id' => $articleId]);
	}

	public function assignedArticles() {
		return $this->artist->getAssignedArticles(0, 1000);
	}

	public function getTags() {
		return $this->artist->getTags();
	}

	public function getAllTags() {
		return $this->db->getArticleTagDB()->getAllTags();
	}

	public function availableArticles(String $tag, $offset=0, $limit=100000, $order=null) {
		return $this->db->getArticlesByTagName($tag, $offset, $limit, WAPArticleTagDB::ARTICLE_UNASSIGNED, null, $order);
	}

	public function releaseArticles($ids) {
		$this->db->releaseArticles($ids, 'en', $this->artist);
		foreach($ids as $id) {
			$article = Article::find_by_wh_article_id($id);
			if ($article) Assignment::build($article)->delete();
		}
	}

	// private methods
	private function allUsers() {
		return $this->db->getUsers();
	}
}
