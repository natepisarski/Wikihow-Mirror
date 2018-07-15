<?
namespace KB;
use MVC\Controller;

class KbController extends Controller {
	public $model;

	public function index() {
		$this->render('KbView', false);
	}

	public function findArticle() {
		$this->model = new KbModel($_GET['article']);
		$title = $this->model->findArticle($_GET['article']);
		if ($title && $title->exists()) {
			$this->article = array(
				'id' => $title->getArticleID(),
				'title' => $title->mTextform,
				'url' => $title->getEditUrl()
			);
		} else {
			$this->addError('Could not find and article "' . $_GET['article'] . '"');
		}
		$this->renderJSON($this->viewVars);
	}

	public function skip() {
		$this->model = new KbModel($_POST['articleId']);
		$this->model->skipSub($_POST['articleId'], $_POST['kbcId']);
		$this->renderJSON(['success' => true]);
	}

	public function getQue() {
		$this->model = new KbModel($_GET['article']);
		$data = $this->model->findSubmissionsByIds($_GET['kbc_ids']);
		if (empty($data)) {
			$this->addError('There are no saved items for this article');
		}

		$this->viewVars['submissions'] = $data;
		$this->renderJSON($this->viewVars);
	}

	public function getSubmissions() {
		$this->model = new KbModel($_GET['article']);
		$data = $this->model->findSubmissions($_GET['articleId']);

		if (empty($data)) {
			$this->addError('There are no more submissions for this article.');
		}

		$this->viewVars['query'] = $this->model->queries;
		$this->viewVars['submissions'] = $data;
		$this->renderJSON($this->viewVars);
	}
	
}
