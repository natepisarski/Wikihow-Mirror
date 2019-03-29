<?php
namespace ContentPortal;
use MVC\Paginator;
use __;
class ArticlesController extends AppController {
	use ArticlesAdmin;

	public $adminOnly = [
		'index', 'assign', '_new', 'edit', 'update', 'create', 'delete', 'suggest', 'search', 'redirects', 'mark_complete'
	];

	public $postRoutes = ['create', 'update'];

	public $guard = [
		'reject', 'info', 'done', 'doc', 'as_json', 'show', 'saveForLater', 'unassign', 'reject_form', 'approve_form'
	];

	function beforeRender() {
		parent::beforeRender();

		if ($this->continue && in_array($_GET['action'], $this->guard)) {
			$this->article = Article::find($this->params('id'));

			if (!$this->article || (!$this->article->belongsTo(currentUser()) && !currentUser()->isAdmin()))
				$this->render404();
		}
	}

	function info() {
		$layout = $this->isAJAX() ? false : 'application';
		$this->render("articles/_article_info", $layout);
	}

	function show() {
		$this->fullScreen = true;
		$this->events = $this->article->events;
	}

	function done() {
		setFlash("Article {$this->article->title} has been marked as {$this->article->state->past_tense}.", 'success');
		Assignment::build($this->article)->done($this->params('message'));
		$this->redirectTo('articles/dashboard');
	}

	function approve_form() {
		$this->layout = null;
	}

	function reject_form() {
		$this->layout = null;
	}

	function reject() {
		$assign = Assignment::build($this->article);

		if ($this->params('auto_assign')) $assign->create($this->currentUser);
		$assign->reject($this->params('message'));

		$this->article->reload();
		setFlash("Article {$this->article->title} has been sent back to {$this->article->state->present_tense}.", 'success');

		if ($this->isAjax()) {
			$this->users = User::all();
			$this->adminUsers = Role::admin()->users;
			$this->render('articles/_article_tr', false);
		} else{
			$this->redirectTo('articles/dashboard');
		}
	}

	function as_json() {
		$this->renderJSON($this->article->to_json(['include' => ['state', 'user']]));
	}

	function doc() {
		if ($this->article->is_test) {
			$this->renderText('This article is a test, and Google Docs are Disabled In Testing');
			return;
		}

		if ($this->article->isInState(Role::VERIFY_KEY) || $this->article->hasVerifyDoc()) {
			if ($this->article->wh_article_id) {
				$this->redirectTo($this->article->lastVerifyDoc()->doc_url);
			} else {
				$this->title = "The article \"{$this->article->title}\" does not exist on WikiHow, and cannot be verified.";
				$this->errors = [];
				$this->render('shared/errors', 'iframe');
			}

		} else {
			$this->redirectTo($this->article->writingDoc()->doc_url);
		}
	}

	function saveForLater() {
		$this->article->touch();
		Event::log("__{{currentUser.username}}__ saved a draft for article __{{article.title}}__.", Event::BLUE);
		$this->redirectTo('articles/dashboard');
	}

	function dashboard () {
		$this->fullScreen = true;
		$this->answerQuestionsAjax = false;

		if ($this->currentUser->isAdmin() && $this->currentUser->dashboard_type == User::ADMIN_DASHBOARD) {
			$this->adminDashboard();
		} else {
			$this->activeArticles = Article::all([
				'conditions' => ['assigned_id' => currentUser()->id],
				'include' => ['state', 'category', 'assigned_user'],
				'order' => 'updated_at ASC'
			]);

			$roleIds = __::chain($this->activeArticles)->pluck('state_id')->unique()->value();
			$this->roles = empty($roleIds) ? [] : Role::all(['conditions' => ['id IN (?)', $roleIds], 'order' => 'step ASC']);
		}
	}

	function unassign() {
		// $this->article->update_attributes(['assigned_id' => null]);
		$assign = Assignment::build($this->article);
		$assign->delete();
		if ($assign->currentStep->key == Role::VERIFY_KEY) $assign->create(User::find_by_username(CARRIE));

		setFlash("You have removed yourself from {$this->article->title}", 'success');
		$this->redirectTo('articles/dashboard');
	}

}
