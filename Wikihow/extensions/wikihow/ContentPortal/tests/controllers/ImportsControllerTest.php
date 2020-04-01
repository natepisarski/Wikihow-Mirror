<?php
namespace ContentPortal;
use MVC\Output;
use MVC\ControllerTestClass;

class ImportsControllerTest extends ControllerTestClass {

	function setup() {
		parent::setup();
		Helpers::setCurrentUser(Helpers::getAdmin());
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testAdminOnly() {
		Helpers::setCurrentUser(Helpers::getWriter());
		$this->get('new')->assert404()
			->post('create', ['key'])->assert404()
			->post('process', ['key'])->assert404();
	}

	function testPostOnly() {
		$this->get('create')->assert404()
			->get('process')->assert404();
	}

	function testNew() {
		$this->get('new')->assertTemplate('new');
	}

	function testValidateCsv() {
		$this->post('process', ['key'])
			->assertEquals($this->errors['file'][0], 'There was no file uploaded.');

		$_FILES = Helpers::loadFixture('fake_csv');
		$_FILES['csv']['type'] = "text/plain";

		$this->post('process', ['key'])
			->assertEquals($this->errors['file'][0], 'Upload must be a CSV file');
	}

	function testProcessCsv() {
		$_FILES = Helpers::loadFixture('fake_csv');
		$this->post('process', ['key'])->assertTemplate('process');
		$this->assertEquals($this->importer->validArticles[0]->title, 'Test CSV Import 1');
	}

	function testCreate() {
		$stub = new Article(Helpers::getArticleStub());
		$this->post('create', ['articles' => [$stub->attributes()]]);
		$this->assertEquals(Article::last()->title, $stub->title);
	}

	function testAssignment() {
		$stub = new Article(Helpers::getArticleStub());
		$stub->assigned_id = Helpers::getAdmin()->id;

		$this->post('create', ['articles' => [$stub->attributes()]]);
		$article = Article::last();

		$assignments = $article->user_articles;

		$this->assertEquals($article->title, $stub->title);
		$this->assertEquals(1, count($assignments));
		$this->assertEquals($article->state_id, $assignments[0]->role_id);
		$this->assertEquals($article->assigned_id, $assignments[0]->user_id);
	}

}
