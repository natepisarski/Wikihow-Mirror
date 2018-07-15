<?
namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentCompleteTest extends PHPUnit_Framework_TestCase {

	public $article;
	public $reviewUser;
	public $editorUser;
	public $assign;

	function setup() {
		$this->article = Helpers::getFakeArticle();

		$this->assign = Assignment::build($this->article)
			->create(Helpers::getWriter())->done()
			->create(Helpers::getProofReader())->done()
			->create(Helpers::getEditor())->done()
			->create(Helpers::getReviewer())->done();
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testSetup() {
		$this->assertTrue($this->article->isUnassigned(), 'should be assigned to carrie');
		$this->assertEquals($this->article->state->key, Role::COMPLETE_KEY, 'should have review as the role');
	}

	function testSteps() {
		$this->assertEquals(count($this->assign->allAssignments), 4, 'should now have 3 assignments at this point');
		$this->assertEquals($this->assign->currentStep->key, Role::COMPLETE_KEY, 'current step should be in complete');
	}

	function testAssignments() {
		$this->assertNull($this->assign->prevAssignment, 'should be no prev assignment');
		$this->assertNull($this->assign->nextAssignment, 'should be no next assignment');
	}

}