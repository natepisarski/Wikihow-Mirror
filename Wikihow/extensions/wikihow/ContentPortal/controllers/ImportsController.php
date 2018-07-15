<?
namespace ContentPortal;

class ImportsController extends AppController {

	public $adminOnly = ['*'];
	public $postRoutes = ['create', 'process'];

	function _new () {}

	function process() {
		$this->importer = new Import($_FILES);

		if ($this->importer->isValid()) {
			$this->importer->build();
		} else {
			$this->errors = $this->importer->errors;
			$this->render('imports/new');
		}
	}

	function create() {

		$titles = [];
		$errors = [];

		foreach($this->params('articles') as $article) {
			$record = new Article($article);
			$record->validateTitle = true;

			if ($record->is_valid() && $record->save()) {
				array_push($titles, $record->reload()->title);
				// only assign article if it has an assignee
				if ($record->assigned_id) {
					$import_notes = isset($article['import_notes']) ? $article['import_notes'] : '';
					Assignment::build($record)->create($record->assigned_user, $import_notes);
				}
			} else {
				$errors[$record->title] = $record->errors->on('title');
			}
		}

		$this->renderJSON(['success' => $titles, 'errors' => $errors]);
	}

}
