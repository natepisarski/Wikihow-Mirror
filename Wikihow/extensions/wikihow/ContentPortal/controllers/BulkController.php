<?
namespace ContentPortal;

class BulkController extends AppController {

	public $adminOnly = ['*'];
	public $bulk;

	function _new() {
		$this->action = 'manage';
	}

	function index() {
		$this->redirectTo('bulk/new');
	}

	function create() {
		$this->action = 'manage';

		if ($this->params('data') == '') {
			setFlash("Please enter some URLs", 'danger');

		} else {
			$bulk = Bulk::build()->fromUrls(explode("\n", $this->params('data')));

			$this->articles = $bulk->articles;
			$this->roles = $bulk->roles;
			$this->urls = $bulk->urls;

			if (!empty($bulk->missingUrls)) $this->errors = ['Could not find' => $bulk->missingUrls];
		}

		$this->render('bulk/new');
	}

	function delete() {
		$this->action = 'destroy';

		if ($this->params('data') == '') {
			setFlash("Please enter some URLs", 'danger');

		} else {
			$bulk = Bulk::build()->fromUrls(explode("\n", $this->params('data')));
			$this->articles = $bulk->articles;
			$this->roles = $bulk->roles;
			$this->urls = $bulk->urls;

			if (!empty($bulk->missingUrls)) $this->errors = ['Could not find' => $bulk->missingUrls];
		}

		$this->render('bulk/new');
	}

	function destroy() {
		$this->action = 'destroy';
		$deleteCount = 0;

		foreach(params('article_ids') as $id) {
			$article = Article::find_by_id($id);
			if ($article) {
				$article->delete();
				$deleteCount ++;
			}
		}

		if ($deleteCount > 0) {
			setFlash("$deleteCount articles have been destroyed.", 'Success');
		}
		$all = Bulk::build()->fromIds($this->params('all_articles_ids'));
		$this->articles = $all->articles;

		$this->render('bulk/new');
	}

	function done() {
		$this->action = 'manage';

		$bulk = Bulk::build()->fromIds($this->params('article_ids'));

		if ($this->params('verify_to_complete')) {
			$bulk->massMarkComplete();
		}
		elseif ($this->params('complete_to_editing')) {
			$bulk->massSendToEditing();
		}
		else {
			$bulk->done();
		}

		$this->affectedArticles = $bulk->articles;

		if ($this->params('assigned_id')) $bulk->massAssign(User::find($this->params('assigned_id')));

		// done, now render the response
		$all = Bulk::build()->fromIds($this->params('all_articles_ids'));
		$this->articles = $all->articles;
		$this->roles = $all->roles;
		$this->render('bulk/new');
	}

}
