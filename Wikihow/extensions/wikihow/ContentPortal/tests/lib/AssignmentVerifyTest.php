<?php
namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentVerifyTest extends PHPUnit_Framework_TestCase {

	public $article;
	public $verifyUser;
	public $reviewUser;
	public $assign;

	function setup() {
		$this->verifyUser = Helpers::getVerifier();
		$this->reviewUser = Helpers::getReviewer();
		$this->article = Helpers::getFakeArticle();

		$this->assign = Assignment::build($this->article)
			->create(Helpers::getWriter())->done()
			->create(Helpers::getProofReader())->done()
			->create(Helpers::getEditor())->done();
		Helpers::forceState($this->article, Role::REVIEW_KEY);
		$this->assign->create($this->reviewUser)->done();

		$this->article->update_attribute('state_id', Role::verify()->id);
		$this->assign->create($this->verifyUser);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testSetup() {
		$this->assertEquals($this->assign->currentAssignment->role->key, Role::VERIFY_KEY, 'should have verify as the role');
		$this->assertEquals($this->article->state->key, Role::VERIFY_KEY, 'should have verify as the role');
	}

	function testSteps() {
		$this->assertEquals(count($this->assign->allAssignments), 5, 'should now have 5 assignments at this point');
		$this->assertEquals($this->assign->prevStep->key, Role::NEEDS_REVISION_KEY, 'should be in needs revision');
		$this->assertEquals($this->assign->currentStep->key, Role::VERIFY_KEY, 'current step should be correct');
		$this->assertEquals($this->assign->nextStep->key, Role::COMPLETE_VERIFIED_KEY, 'next step is complete / verified');
	}

	function testAssignments() {
		$this->assertEquals($this->assign->currentAssignment->user_id, $this->verifyUser->id, 'should be assigned to user');
		$this->assertEquals($this->assign->currentAssignment->role->key, Role::VERIFY_KEY, 'should have verify as the role');
		$this->assertNull	($this->assign->prevAssignment, 'prev should be null');
		$this->assertNull($this->assign->nextAssignment, 'should be no next assignment, we are at the last step');
	}

	function testUsers() {
		$this->assertNull($this->assign->prevUser, 'prev user should be null');
		$this->assertEquals($this->assign->currentUser->id, $this->verifyUser->id, 'should be correct');
		$this->assertNull($this->assign->nextUser, 'should be no user for next step defined');
	}

	function testReject() {
		$this->assign->reject('MY MESSAGE');
		$this->assertEquals($this->article->state->key, Role::NEEDS_REVISION_KEY, 'should be in needs revision now');
		$this->assertNull($this->article->assigned_user, 'Should not be assigned to anyone...');
		$this->assertEquals($this->article->notesForState()[0]->message, 'MY MESSAGE', 'Should have a message');
	}

	function testComplete() {
		$this->assign->done();
		$this->assertEquals($this->article->state->key, Role::COMPLETE_VERIFIED_KEY, 'should be back COMPLETE');
		$this->assertNull($this->article->assigned_id, 'is assigned to no one');
	}
}
