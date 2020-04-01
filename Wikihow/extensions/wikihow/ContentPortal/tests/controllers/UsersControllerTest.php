<?php
namespace ContentPortal;
use MVC\Output;
use MVC\ControllerTestClass;
use MVC\CLI;

class UsersControllerTest extends ControllerTestClass {

	function setup() {
		parent::setup();
		$this->admin = Helpers::getAdmin();
		Helpers::setCurrentUser($this->admin);
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testNew() {
		$this->get('new')
			->assertHasVar('user')
			->assertTemplate('new');
	}

	function testIndex() {
		$this->get('index')
			->assertHasVar('users')
			->assertHasVar('fullScreen')
			->assertTemplate('index');
	}

	function testEdit() {
		$this->get('edit', $this->admin)
			->assertHasVar('user')
			->assertTemplate('edit');

		$vars = $this->getVars();
		$this->assertEquals($vars['user']->id, $this->admin->id);
	}

	function testFilter() {
		foreach(Role::all() as $role) {
			$this->get('filter', ['role_id' => $role->id]);
			$data = $this->getJSON();

			$this->assertEquals($data->role->id, $role->id);

			foreach($data->users as $jsonUser) {
				$user = User::find($jsonUser->id);
				$this->assertTrue($user->hasRoleKey($role->key));
			}
		}

		$this->get('filter')->assert404();
	}

	function testUpdate() {
		$this->admin->send_mail = false;
		$this->admin->save(false);

		$params = ['username' => $this->admin->username, 'send_mail' => true];
		$this->post('update', ['user' => $params], $this->admin)
			->assertWasRedirectTo('index')
			->assertTrue((boolean) $this->admin->reload()->send_mail);

		// $this->post('update', ['user' => ['username' => '']], $this->admin)
		// 	->assertTemplate('edit')
		// 	->assertHasVar('errors');
	}
}
