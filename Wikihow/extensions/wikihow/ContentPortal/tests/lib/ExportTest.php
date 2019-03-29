<?php
namespace ContentPortal;
use PHPUnit_Framework_TestCase;
use __;

class ExportTest extends PHPUnit_Framework_TestCase {

	function setup() {

	}

	function testDefineRanges() {
		$types = Export::getExportTypes();

		$roles = __::chain(Role::allButAdmin())->filter(['is_on_hold' => 0])->pluck('key')->value();
		$this->assertNotNull($types, 'Should have defined the ranges');
		$this->assertEquals(__::pluck($types, 'key'), $roles, 'Should a key for each type');
		$this->assertTrue(is_array($types), 'Should a associative array');
	}

}
