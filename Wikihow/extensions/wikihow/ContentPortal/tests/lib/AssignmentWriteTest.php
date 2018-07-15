<?
namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentWriteTest extends PHPUnit_Framework_TestCase {

	public $article;
	public $user;
	public $assign;

	function setup() {
		$this->article = Helpers::getFakeArticle();
		$this->user = Helpers::getWriter();
		$this->assign = Assignment::build($this->article)->create($this->user);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testSetup() {
		$this->assertNotNull($this->article->id, 'should have an id');
		$this->assertEquals($this->article->state_id, Role::write()->id, 'should start off in the writing state');
	}

	function testAssignWriter() {
		$this->assertEquals($this->assign->currentAssignment->user_id, $this->user->id, 'should have created an assignment');
		$this->assertEquals($this->assign->currentAssignment->role->id, Role::write()->id, 'should have writing as the role');
	}

	function testStatesWriting() {
		$this->assertEquals(count($this->assign->allAssignments), 1, 'should only have one assignment at this point');
		
		$this->assertEquals($this->assign->nextStep->id, Role::proofRead()->id, 'next step should be proof reading');
		$this->assertEquals($this->assign->currentStep->id, Role::write()->id, 'current step should be in writing');
		$this->assertNull($this->assign->prevStep, 'should be no prev step, we are in writing');

		$this->assertNull($this->assign->prevAssignment, 'should be no prev assignment, we are in writing');
		$this->assertNull($this->assign->nextAssignment, 'should be no next assignment, we are in writing');

		$this->assertNull($this->assign->prevUser, 'should be no prev user, we are in writing');
		$this->assertEquals($this->assign->currentUser->id, $this->user->id, 'should be our user');
		$this->assertNull($this->assign->nextUser, 'should be no user for next step defined');
	}

	function testFinishWriting() {
		$this->assign->done();
		$this->article->reload();
		$this->assertTrue($this->article->isUnassigned(), 'should now be unassinged');
		$this->assertEquals($this->article->state->id, Role::proofRead()->id, 'should now be in proof reading');
		$this->assertTrue($this->article->isUnassigned(), 'should be unassigned');
	}
}
