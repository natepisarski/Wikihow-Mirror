<?php
namespace ContentPortal;
use __;
use MVC\Paginator;

class ReservationsController extends AppController {

	public $postRoutes = ['create'];

	function beforeRender() {
		parent::beforeRender();
		if ($this->currentUser) {
			$this->fish = new Fishing($this->currentUser);
			if (!$this->fish->userAllowed) $this->render404();
		}
	}

	function index() {
		$this->tags = $this->currentUser->isAdmin() ? $this->fish->getAllTags() : $this->fish->getTags();
		$this->currentTag = params('tag', __::first($this->tags)['raw_tag']);

		Paginator::$perPage = PER_PAGE;
		Paginator::$total = count($this->fish->availableArticles($this->currentTag));

		$this->articles = $this->fish->availableArticles(
			$this->currentTag,
			Paginator::getOffset(),
			Paginator::$perPage,
			Paginator::getSort('ct_page_title ASC')
		);
		$this->assignedArticles = $this->fish->assignedArticles();
	}

	function destroy() {
		if (empty(params('article_ids'))) {
			setFlash('Please select some articles you would like to remove.', 'danger');
			$this->redirectTo('reservations/index', ['tag' => params('tag')]);
			return;
		}

		foreach(params('article_ids') as $id) {
			$this->fish->releaseArticles(params('article_ids'));
		}

		setFlash('The selected Articles have been released.', 'success');
		$this->redirectTo('reservations/index', ['tag' => params('tag')]);
	}

	function create() {
		try {
			$success = $this->fish->assignArticle(params());

			$this->renderJSON([
				'success' => $success,
				'errors' => $this->fish->errors,
				'title' => params('title')
			]);
		} catch (Error $e) {
			http_response_code(500);
			$this->renderJSON(['success' => false]);
		}
	}

}
