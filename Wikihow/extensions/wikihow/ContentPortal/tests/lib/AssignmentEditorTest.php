<?
namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentEditorTest extends PHPUnit_Framework_TestCase {

	public $article;
	public $editorUser;
	public $proofReadUser;
	public $assign;

	function setup() {
		$this->editorUser = Role::edit()->users[0];
		$this->proofReadUser = Role::proofRead()->users[0];

		$this->article = Helpers::getFakeArticle();

		$this->assign = Assignment::build($this->article)
			->create(Helpers::getWriter())->done()
			->create($this->proofReadUser)->done()
			->create($this->editorUser);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testSetup() {
		$this->assertEquals($this->assign->currentAssignment->role->id, Role::edit()->id, 'should have proof reading as the role');
		$this->assertEquals($this->article->state->id, Role::edit()->id, 'should have proof reading as the role');
	}

	function testSteps() {
		$this->assertEquals(count($this->assign->allAssignments), 3, 'should now have three assignments at this point');
		$this->assertNull($this->assign->prevStep, 'should be null cant revert');
		$this->assertEquals($this->assign->currentStep->key, Role::EDIT_KEY, 'current step should be edit');
		$this->assertEquals($this->assign->nextStep->key, Role::REVIEW_KEY, 'next step should be review');
	}

	function testAssignments() {
		$this->assertEquals($this->assign->currentAssignment->user_id, $this->editorUser->id, 'should be assigned to user');
		$this->assertEquals($this->assign->currentAssignment->role->id, Role::edit()->id, 'should have proof reading as the role');
		$this->assertNull($this->assign->prevAssignment, 'prev should be writing');
		$this->assertNull($this->assign->nextAssignment, 'should be no next assignment, we are in writing');
	}

	function testUsers() {
		$this->assertNull($this->assign->prevUser, 'prev user should be null as you cant send back');
		$this->assertEquals($this->assign->currentUser->id, $this->editorUser->id, 'should be correct');
		$this->assertNull($this->assign->nextUser, 'should be no user for next step defined');
	}

	function testComplete() {
		$this->assign->done();
		$this->assertFalse($this->article->isUnassigned(), 'should be assigned automatically');
		$this->assertEquals($this->article->assigned_user->username, 'Dr. Carrie', 'should be assigned automatically');
		$this->assertEquals($this->article->state->key, Role::REVIEW_KEY, 'should now be in review');
	}
}