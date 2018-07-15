<?
namespace ContentPortal;
use MVC\Output;
use MVC\ControllerTestClass;

class AppControllerTest extends ControllerTestClass {

	function testIsAdmin() {
		$ctrl = new AppController('foo');
		$ctrl->adminOnly = ['foo'];
		$this->assertTrue($ctrl->isAdminPage());
	}
}
