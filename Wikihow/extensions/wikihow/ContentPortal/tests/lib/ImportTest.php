<?php
namespace ContentPortal;
use PHPUnit_Framework_TestCase;
use Title;

class ImportTest extends PHPUnit_Framework_TestCase {

	function setup() {
		Title::$exists = true;
		$this->importer = new Import(Helpers::loadFixture('fake_csv'));
		$this->importer->build();
	}

	function testHeaderParse() {
		$this->assertEquals(
			["article_id", "url", "is_wrm", "category", "state", "url_to_user"],
			$this->importer->header
		);
		$this->assertEquals('i_am_key', $this->importer->formatKey('I am_ - key )(^(%^^&%'));
	}

	function testParsing() {
		$groups = $this->importer->articleGroups();
		$this->assertTrue($this->importer->isValid());
		$this->assertEquals(1, count($groups['with_errors']));
		$this->assertEquals(3, count($groups['wrm_with_id']));
		$this->assertEquals(1, count($groups['existing']));
	}

	function testAssignments() {
		$articles = $this->importer->articleGroups()['wrm_with_id'];
		$this->assertEquals('Dr. Carrie', $articles[0]->assigned_user->username);
		$this->assertNull($articles[1]->assigned_user);

		$this->assertEquals('Dperrymorrow', $articles[2]->assigned_user->username);
	}

}
