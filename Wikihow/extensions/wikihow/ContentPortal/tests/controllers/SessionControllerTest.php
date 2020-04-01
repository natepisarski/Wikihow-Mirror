<?php
namespace ContentPortal;
use MVC\Output;
use MVC\ControllerTestClass;

class SessionControllerTest extends ControllerTestClass {

	function testNew() {
		Helpers::logout();
		$this->get('new')
			->assertTemplate('new');
	}

	// function testCreate() {
	// 	Helpers::logout();

		// $this->post('session/create', ['token-form' => true, 'token' => 'fpppp'])
		// 	->assertTemplate('session/new');

		// $admin = Helpers::getAdmin();

		// $this->post('session/create', ['token-form' => true, 'token' => $admin->token])
		// 	->assertWasRedirect()
		// 	->assertEquals(Helpers::getCurrentUser()->id, $admin->id);
	// }

}
