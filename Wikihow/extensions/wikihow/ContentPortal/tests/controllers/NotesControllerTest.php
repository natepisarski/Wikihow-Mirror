<?
namespace ContentPortal;
use MVC\Output;
use MVC\ControllerTestClass;

class NotesControllerTest extends ControllerTestClass {
	function setup() {
		parent::setup();
		Note::delete_all(['conditions' => ['type' => Note::BLOCKING]]);
		$this->editor = Helpers::getEditor();
		Helpers::setCurrentUser($this->editor);
		$this->article = Helpers::getFakeArticle(['state_id' => Role::edit()->id]);
		Assignment::build($this->article)->create($this->editor);
	}

	function testNew() {
		$this->get('new', ['article_id' => $this->article->id])->assertHasVar('article');
	}

	function testCreate() {
		$post = [
			'user_id'    => currentUser()->id,
			'message'    => 'my question',
			'article_id' => $this->article->id,
			'type'       => Note::BLOCKING
		];

		$this->post('create', ['note' => $post], ['article_id' => $this->article->id])
			->assertWasRedirect();

		$this->article->reload();
		$this->assertEquals($this->article->state->key, Role::BLOCKING_QUESTION_KEY);
		$this->assertNull($this->article->assigned_id);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

}