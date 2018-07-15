<?
namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentProofReadTest extends PHPUnit_Framework_TestCase {

	public $article;
	public $writeUser;
	public $proofUser;
	public $assign;

	function setup() {
		$this->writeUser = Helpers::getWriter();
		$this->proofUser = Helpers::getProofReader();
		$this->article = Helpers::getFakeArticle();
		$this->assign = Assignment::build($this->article)->create($this->writeUser)->done()->create($this->proofUser);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testSetup() {
		$this->assertEquals($this->assign->currentAssignment->role->id, Role::proofRead()->id, 'should have proof reading as the role');
		$this->assertEquals($this->article->state->id, Role::proofRead()->id, 'should have proof reading as the role');
	}

	function testSteps() {
		$this->assertEquals(count($this->assign->allAssignments), 2, 'should now have two assignments at this point');
		$this->assertEquals($this->assign->prevStep->id, Role::write()->id, 'should be no writing');
		$this->assertEquals($this->assign->currentStep->id, Role::proofRead()->id, 'current step should be in writing');
		$this->assertEquals($this->assign->nextStep->id, Role::edit()->id, 'next step should be editing');
	}

	function testAssignments() {
		$this->assertEquals($this->assign->currentAssignment->user_id, $this->proofUser->id, 'should be assigned to user');
		$this->assertEquals($this->assign->currentAssignment->role->id, Role::proofRead()->id, 'should have proof reading as the role');
		$this->assertEquals($this->assign->prevAssignment->role_id, Role::write()->id, 'prev should be writing');
		$this->assertNull($this->assign->nextAssignment, 'should be no next assignment, we are in writing');
	}

	function testUsers() {
		$this->assertEquals($this->assign->prevUser->id, $this->writeUser->id, 'prev user should be writer');
		$this->assertEquals($this->assign->currentUser->id, $this->proofUser->id, 'should be our writer user');
		$this->assertNull($this->assign->nextUser, 'should be no user for next step defined');
	}

	function testReject() {
		$this->assign->reject();
		$this->assertEquals($this->article->state->id, Role::write()->id, 'Should be back in writing after rejection');
		$this->assertEquals($this->article->assigned_id, $this->writeUser->id, 'should be assigned to writer');
		$this->assign->done()->done();;
		$this->assertEquals($this->article->state->id, Role::edit()->id, 'Should now be in editing');
		$this->assertTrue($this->article->isUnassigned(), 'Should be unassigned');
	}

}