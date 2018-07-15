<?
namespace ContentPortal;
use PHPUnit_Framework_TestCase;
class RoleTest extends PHPUnit_Framework_TestCase {


	function setup() {

	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testWriteSetup() {
		$role = Role::write();
		$this->assertEquals($role->nextStep()->key, Role::PROOF_READ_KEY, 'next step should be proof read');
		$this->assertNull($role->prevStep(), 'should be no previous step');
		$this->assertTrue((boolean)$role->public, 'should be public');
	}

	function testProofReadSetup() {
		$role = Role::proofRead();
		$this->assertEquals($role->prevStep()->key, Role::WRITE_KEY, 'next step should be set correctly');
		$this->assertEquals($role->nextStep()->key, Role::EDIT_KEY, 'next step should be set correctly');
		$this->assertTrue((boolean)$role->public, 'should be public');
	}

	function testEditSetup() {
		$role = Role::edit();
		$this->assertNull($role->prevStep(), 'has no previous step you cant revert this');
		$this->assertEquals($role->nextStep()->key, Role::REVIEW_KEY, 'next step should be set correctly');
		$this->assertTrue((boolean)$role->public, 'should be public');
	}

	function testReviewSetup() {
		$role = Role::review();
		$this->assertEquals($role->prevStep()->key, Role::EDIT_KEY, 'has no previous step setup correctly');
		$this->assertEquals($role->nextStep()->key, Role::COMPLETE_KEY, 'next step should be set correctly');
		$this->assertTrue((boolean)$role->public, 'should be public');
	}

	function testVerifySetup() {
		$role = Role::verify();
		$this->assertEquals($role->prevStep()->key, Role::NEEDS_REVISION_KEY, 'has no previous step setup correctly');
		$this->assertEquals($role->nextStep()->key, Role::COMPLETE_VERIFIED_KEY, 'next step should be set correctly');
		$this->assertTrue((boolean)$role->public, 'should be public');
	}

	function testRevisionSetup() {
		$role = Role::needsRevision();
		$this->assertNull($role->prevStep(), 'has no previous step setup correctly');
		$this->assertEquals($role->nextStep()->key, Role::VERIFY_KEY, 'next step should be set correctly');
		$this->assertTrue((boolean)$role->public, 'should be public');
	}

}
