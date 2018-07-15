<?

namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentTest extends PHPUnit_Framework_TestCase {

	public $article;

	function setup() {
		$this->article = Helpers::getFakeArticle();
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testHandlingUnsavedArticle() {
		$assign = Assignment::build(new Article());
		$this->assertNull($assign->currentStep, 'Should be null and not fail.');
	}

	function testMessageOnCreate() {
		$assign = Assignment::build($this->article)->create(Helpers::getAdmin(), "hi there, how about a message");
		$this->assertEquals($this->article->lastNote()->message, 'hi there, how about a message', "message should persist on create");
	}

	function testMessageOnUpdate() {
		$assign = Assignment::build($this->article)->create(Helpers::getAdmin(), "hi there");
		$this->assertEquals($this->article->lastNote()->message, 'hi there', "message should persist on create");

		$id = $assign->currentAssignment->id;
		$assign->create(Helpers::getAdmin(), 'new message...');

		$this->assertEquals($this->article->lastNote()->message, 'new message...', 'message should append');
		$this->assertEquals(count($this->article->notesForState()), 2, 'should now have 2 messages on the same state');

		$this->assertEquals($assign->currentAssignment->id, $id, 'should not have deleted the original assignment');
		$this->assertEquals(count($assign->allAssignments), 1, 'should not duplicate the assignment if only updating the message');
	}

	function testDeleteOnUserChange() {
		$admin = Helpers::getAdmin();
		$writer = Helpers::getWriter();
		$assign = Assignment::build($this->article)->create($admin, "hi there");
		$this->assertEquals($assign->currentUser->id, $admin->id, "should be assigned to the admin");

		$id = $assign->currentAssignment->id;
		$assign->create($writer, 'new message...');

		$this->assertEquals($assign->currentUser->id, $writer->id, 'should be assigned to writer now.');

		$this->assertEquals($assign->currentAssignment->id, $id, 'should have updated rather than delteing the assignment');
		$this->assertEquals(count($assign->allAssignments), 1, 'should not duplicate the assignment if only updating the message');
	}


}