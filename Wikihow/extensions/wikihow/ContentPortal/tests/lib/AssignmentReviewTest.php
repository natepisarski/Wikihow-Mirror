<?php
namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentReviewTest extends PHPUnit_Framework_TestCase {

	public $article;
	public $reviewUser;
	public $editorUser;
	public $assign;

	function setup() {
		$this->editorUser = Helpers::getEditor();
		$this->reviewUser = Helpers::getReviewer();
		$this->article = Helpers::getFakeArticle();

		$this->assign = Assignment::build($this->article)
			->create(Helpers::getWriter())->done()
			->create(Helpers::getProofReader())->done()
			->create($this->editorUser)->done();

		$this->article->update_attribute('state_id', Role::review()->id);
		$this->assign->create($this->reviewUser);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testSetup() {
		$this->assertEquals($this->article->assigned_user->id, $this->reviewUser->id, 'should be assigned to our review user');
		$this->assertEquals($this->article->state->key, Role::REVIEW_KEY, 'should have review as the role');
	}

	function testSteps() {
		$this->assertEquals(count($this->assign->allAssignments), 4, 'should now have 4 assignments at this point');
		$this->assertEquals($this->assign->prevStep->key, Role::EDIT_KEY, 'should be editing as the previous step');
		$this->assertEquals($this->assign->currentStep->key, Role::REVIEW_KEY, 'current step should be in review');
		$this->assertEquals($this->assign->nextStep->key, Role::COMPLETE_KEY, 'next step should be verify');
	}

	function testAssignments() {
		$this->assertEquals($this->assign->currentAssignment->user_id, $this->reviewUser->id, 'should be assigned to user');
		$this->assertEquals($this->assign->currentAssignment->role->key, Role::REVIEW_KEY, 'should have review as the role');
		$this->assertEquals($this->assign->prevAssignment->role->key, Role::EDIT_KEY, 'prev should be edit');
		$this->assertNull($this->assign->nextAssignment, 'should be no next assignment');
	}

	function testUsers() {
		$this->assertEquals($this->assign->prevUser->id, $this->editorUser->id, 'prev user should be editor');
		$this->assertEquals($this->assign->currentUser->id, $this->reviewUser->id, 'should be our review user');
		$this->assertNull($this->assign->nextUser, 'should be no user for next step defined');
	}

	function testReject() {
		$this->assign->reject('this is my message');
		$this->assertEquals($this->article->state->key, Role::EDIT_KEY, 'Should be back in editing after rejection');
		$this->assertEquals($this->article->assigned_id, $this->editorUser->id, 'should be assigned to editor');;

		$this->assertEquals(count($this->article->notesForState()), 1, 'Should now have a message attached to the assignment');
		$this->assign->done();

		$this->assertEquals($this->article->state->key, Role::REVIEW_KEY, 'Should now be in complete / verified');
		$this->assertFalse($this->article->isUnassigned(), 'Should not be unassigned');
	}

}
