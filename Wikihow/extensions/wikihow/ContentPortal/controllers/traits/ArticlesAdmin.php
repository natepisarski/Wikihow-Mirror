<?php
namespace ContentPortal;
use MVC\Paginator;
use Title;
use __;
trait ArticlesAdmin {

	function index() {
		$this->fullScreen = true;

		$conditions = [];
		$this->categories = Category::all(['order' => 'title ASC']);
		$this->roles = Role::allButAdmin();

		$this->category = $this->findCategory();
		$this->state = $this->findState();

		if ($this->category) $conditions['category_id'] = $this->category->id;

		$this->adminUsers = Role::Admin()->users;
		$this->users = User::all(['include' => ['active_articles', 'roles', 'user_roles']]);

		if ($this->state) {
			$this->users = $this->state->users;
			$conditions['state_id'] = $this->state->id;
		} elseif ($this->params('state') == 'unassigned') {
			// unassigned
			$conditions['assigned_id'] = null;
		}

		Paginator::$perPage = PER_PAGE;
		Paginator::$total = Article::count($conditions);

		$this->articles = Article::all([
			'conditions' => $conditions,
			'limit'      => Paginator::$perPage,
			'order'      => Paginator::getSort('updated_at DESC'),
			'offset'     => Paginator::getOffset(),
			'include'    => [
				'state', 'category', 'user_articles', 'roles',
				'events', 'users', 'info_notes',
				'assigned_user' => ['active_articles']
			]
		]);

		$this->redirectCount = count(Article::redirects());
		$this->deleteCount = count(Article::deletes());
		$this->articles = Paginator::sort($this->articles);
	}

	function adminDashboard() {
		$this->roles = $this->currentUser->rolesWithAssignments();
		$this->adminUsers = Role::Admin()->users;
		$this->users = User::all(['include' => ['active_articles', 'roles', 'user_roles']]);

		$roleId = $this->params('role', $this->roles ? $this->roles[0]->id : null);
		$this->includeActionRow = true;

		$conditions = ['assigned_id' => $this->currentUser->id, 'state_id' => $roleId];

		Paginator::$perPage = 100;
		Paginator::$total = Article::count($conditions);
		Paginator::setDefault('lastTouched', 'ASC', false);

		$this->articles = $this->activeArticles = Article::all([
			'conditions' => $conditions,
			'include'    => ['state', 'category', 'assigned_user', 'events', 'info_notes'],
			'order'      => Paginator::getSort(),
			'offset'     => Paginator::getOffset(),
			'limit'      => Paginator::$perPage
		]);

		$this->articles = Paginator::sort($this->articles);
		$this->render('articles/admin_dashboard');
	}

	function suggest() {
		$articles = $this->findLikeArticles($this->params('title_search'));
		$this->renderJSON(__::pluck($articles, 'title'));
	}

	function search() {
		if ($this->params('title_search') !== "") {
			$this->adminUsers = Role::findByTitle('Admin')->users;
			$this->users = User::all(['include' => ['active_articles', 'roles', 'user_roles']]);
			$this->articles = $this->findLikeArticles($this->params('title_search'));
		}
		$this->render('articles/search', false);
	}

	function assign() {
		$this->article = Article::find($this->params('article'));

		if ($this->article->state_id == $this->params('role')) {
			$user = User::find($this->params('user'));
			Assignment::build($this->article)->create($user);
		} else {
			$this->articleIsStale = true;
		}

		$this->users = User::all([
			'conditions' => ['category_id' => $this->article->category_id],
			'include' => ['active_articles', 'user_roles']
		]);
		$this->adminUsers = Role::findByTitle('Admin')->users;
		$this->render("articles/_article_tr", false);
	}

	function edit() {
		$this->categories = Category::all();
		$this->article = Article::find($_GET['id']);
	}

	// function mark_complete() {
	// 	$this->article = Article::find($this->params('id'));
	// 	Assignment::build($this->article)->delete();
	// 	$this->article->state_id = Role::complete()->id;
	// 	$this->article->save();

	// 	foreach($this->article->verifyDocs() as $doc) {
	// 		$doc->outdated = true;
	// 		$doc->save();
	// 	}

	// 	setFlash("{$this->article->title} has been moved to Complete.", 'Success');
	// 	$this->redirectTo('articles/index');
	// }

	function update() {
		$this->article = Article::find($this->params('id'));
		$this->article->validateTitle = true;

		$orgUserId = $this->article->assigned_id;
		$stateSwitch = $this->article->state_id != $this->params('article[state_id]');

		if (!$this->article->state->is_on_hold && $this->article->state_id != $this->params('article[state_id]')) {
			setFlash('The article has changed since you loaded the page. Please try your update again.', 'danger');

		} else {

			// destroy the current assignment if we are switching states...
			if ($stateSwitch) {
				Assignment::build($this->article)->delete();

				//invalidate verification docs if we're switching the state to "Verify"
				if ($this->params('article[state_id]') == Role::verify()->id) {
					$this->article->invalidateVerifiyDocs();
				}

				//are we clearing verify data?
				if ($this->checkClearVerifyData()) UserArticle::clearVerifyData($this->article);
			}

			if ($this->article->update_attributes($this->params('article'))) {
				Event::log("__{{article.title}}__ was updated by __{{currentUser.username}}__.");

				if ($this->params('article[assigned_id]')) {
					Assignment::build($this->article)->create($this->article->assigned_user, $this->params('note'));

					setFlash('Your changes were saved', 'success');
					$this->redirectTo('articles', ["category" => $this->article->category->id, 'state' => $this->article->state_id]);
					return;
				}

			} else {
				$this->errors = $this->article->errors->get_raw_errors();
			}

		}

		$this->categories = Category::all();
		$this->render('articles/edit');
	}

	function create() {
		$this->formDest = url('articles/create');
		$this->article = new Article($_POST['article']);
		$this->article->validateTitle = true;

		$this->categories = Category::all();

		if ($this->article->save()) {
			Assignment::build($this->article)->create($this->article->assigned_user, $this->params('note'));
			setFlash('Your article has been added', 'success');
			$this->redirectTo('articles', ['category' => $this->article->category_id, 'state' => $this->article->state_id]);

		} else {
			$this->errors = $this->article->errors->get_raw_errors();
			$this->render('articles/new');
		}
	}

	function _new() {
		$this->categories = Category::all();
		$this->article = new Article(['state_id' => Role::find_by_title('Writer')->id]);
	}

	function delete() {
		$this->article = Article::find($_GET['id']);
		$this->article->delete();
		setFlash("Article {$this->article->title} has been deleted", 'success');
		$this->redirectTo('articles');
	}

	// private methods

	private function findState() {
		$state = $this->params('state');
		if ($state && !in_array($state, ['unassigned', 'any'])) {
			return Role::find($state);
		}
		return null;
	}

	private function findLikeArticles($query) {
		$article = new Article(['title' => $query]);
		$article->is_valid();

		if (is_numeric($query)) {
			$conditions = ['wh_article_id' => $query];
		} elseif (strpos($query, '://')) {
			$conditions = ['wh_article_url' => $article->wh_article_url];
		} else {
			$conditions = ['`title` LIKE ?', "%{$article->title}%"];
		}

		return Article::all([
			'conditions' => $conditions,
			'order' => 'title DESC'
		]);
	}

	private function findCategory() {
		$cat = $this->params('category');
		return ($cat && $cat != 'all') ? Category::find($cat) : null;
	}

	/**
	 * checkClearVerifyData()
	 * returns true/false
	 * see if we want to remove all verify data on a state change
	 */
	private function checkClearVerifyData() {
		//Verified Complete -> anything else
		if ($this->article->state_id == Role::verifiedComplete()->id) return true;

		//Verify -> Complete
		if ($this->article->state_id == Role::verify()->id
			&& $this->params('article[state_id]') == Role::complete()->id) return true;

		return false;
	}
}
