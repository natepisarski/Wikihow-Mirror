<?
namespace ContentPortal;

class NotesController extends AppController {

	function dismiss() {
		$note = Note::find($this->params('id'));
		$note->update_attribute('viewed', true);
		$this->renderJSON(['success' => true]);
	}

	function _new() {
		$this->article = Article::find(params('article_id'));
		$this->layout = false;
	}

	// for asking a question and kicking it out of your box till answered
	function create() {
		$this->article = Article::find(params('note[article_id]'));
		$note = Note::create($this->params('note'));
		setFlash("Your question has been asked.", 'success');
		$this->redirectTo('/');
	}

}