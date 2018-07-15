<?
namespace ContentPortal;
use __;
use PHPUnit_Framework_TestCase;
class AssignmentBlockingTest extends PHPUnit_Framework_TestCase {

	function setup() {
		Note::delete_all(['conditions' => ['type' => Note::BLOCKING]]);

		$this->admin = Helpers::getAdmin();
		$this->editor = Helpers::getEditor();
		$this->article = Helpers::getFakeArticle(['state_id' => Role::edit()->id]);
		$this->assignment = Assignment::build($this->article)->create($this->editor);

		$this->note = Note::create([
			'article_id' => $this->article->id,
			'type'       => Note::BLOCKING,
			'user_id'    => $this->editor->id
		]);

		$this->article->reload();
		$this->assignment->fetch();
	}

	function testAssignmentVals() {
		$this->assertEquals($this->article->state->key, Role::BLOCKING_QUESTION_KEY);

		$this->assertEquals($this->assignment->currentStep->key, Role::BLOCKING_QUESTION_KEY);
		$this->assertNull($this->assignment->currentAssignment);

		$this->assertEquals($this->assignment->prevStep->key, Role::EDIT_KEY);
		$this->assertEquals($this->assignment->prevAssignment->user_id, $this->editor->id);
		$this->assertEquals($this->assignment->nextStep, null);
	}

	function testReassignment() {
		$this->assertTrue($this->article->isBlocked());
		$this->assignment->reject("answer to question");
		$this->assertEquals($this->article->state->key, Role::EDIT_KEY);
		$this->assertEquals(count($this->note->notes), 1);
		$this->assertEquals($this->note->notes[0]->message, 'answer to question');
		$this->assertEquals(__::last($this->article->notes)->role->key, Role::EDIT_KEY);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}
}