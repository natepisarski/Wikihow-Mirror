<?
namespace ContentPortal;
use MVC\Output;
use MVC\ControllerTestClass;

class ExportsControllerTest extends ControllerTestClass {

	function setup() {

	}

	function testRenderIndex() {
		$this->get('index')
			->assertTemplate('index');
	}
}