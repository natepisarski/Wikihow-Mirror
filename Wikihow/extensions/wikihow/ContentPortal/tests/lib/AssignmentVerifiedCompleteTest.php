<?php
namespace ContentPortal;
use PHPUnit_Framework_TestCase;

class AssignmentVerifiedCompleteTest extends PHPUnit_Framework_TestCase {

	public $article;
	public $reviewUser;
	public $editorUser;
	public $assign;

	function setup() {
		$this->editorUser = Helpers::getWriter();
		$this->reviewUser = Helpers::getReviewer();
		$this->article = Helpers::getFakeArticle();

		$this->assign = Assignment::build($this->article)
			->create(Helpers::getWriter())->done()
			->create(Helpers::getProofReader())->done()
			->create(Helpers::getEditor())->done();

		Helpers::forceState($this->article, Role::REVIEW_KEY);
		$this->assign->create(Helpers::getReviewer())->done();
		Helpers::forceState($this->article, Role::VERIFY_KEY);
		$this->assign->create(Helpers::getVerifier())->done();
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testSetup() {
		$this->assertTrue($this->article->isUnassigned(), 'should be unassigned');
		$this->assertEquals($this->article->state->key, Role::COMPLETE_VERIFIED_KEY, 'should have review as the role');
	}

	function testSteps() {
		$this->assertEquals(count($this->assign->allAssignments), 5, 'should now have 5 assignments at this point');
		$this->assertEquals($this->assign->currentStep->key, Role::COMPLETE_VERIFIED_KEY, 'current step should be in complete');
	}

	function testAssignments() {
		$this->assertEquals($this->assign->prevAssignment->role->key, Role::VERIFY_KEY, 'prev should not be null');
		$this->assertNull($this->assign->nextAssignment, 'should be no next assignment');
	}

}
