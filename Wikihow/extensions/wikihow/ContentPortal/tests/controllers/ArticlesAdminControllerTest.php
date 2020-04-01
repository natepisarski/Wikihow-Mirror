<?php
namespace ContentPortal;
use MVC\Output;
use __;
use MVC\ControllerTestClass;

class ArticlesAdminControllerTest extends ControllerTestClass {

	public $baseUrl = 'articles';

	function setup() {
		Helpers::setCurrentUser(Helpers::getAdmin());
		$this->article = Helpers::getFakeArticle();
		Assignment::build($this->article)->create(currentUser(), 'my message');
		parent::setup();
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testNew() {
		$this->get('new')
			->assertHasVar('article', 'should have set an article')
			->assertOnPage('New Article', 'should have new article in the output');
	}

	function testCreate() {
		$article = Helpers::getArticleStub();
		$article['assigned_id'] = currentUser()->id;

		$this->post('create', ['article' => $article])
			->assertWasRedirect('should have been a redirect')
			->assertWasRedirectTo('index', 'should have redirected to index');
	}

	function testSuggest() {
		$article = Helpers::getFakeArticle();
		$this->get('suggest', ['title_search' => $article->title]);
		$this->assertEquals([$article->title], $this->getOutput());
	}

	function testSearch() {
		$article = Helpers::getFakeArticle();
		$this->get('search', ['title_search' => $article->title])
			->assertTemplate('search')
			->assertOnPage($article->title);

	}

	function testSearchWithBlank() {
		$article = Helpers::getFakeArticle();
		$this->get('search', ['title_search' => ''])
			->assertTemplate('search')
			->assertOnPage('no articles found');
	}

	function testCreateValidation() {
		$article = Helpers::getArticleStub();
		$article['title'] = '';

		$this->post('create', ['article' => $article])
			->assertWasNotRedirect('should not have been a redirect')
			->assertTemplate('new', 'should have re rendered new template')
			->assertOnPage('errors', 'should have errors');
	}

	function testIndex() {
		$this->get('index')
			->assertTemplate('index', 'should have rendered index');
	}

	function testEdit() {
		$this->get('edit', $this->article)
			->assertTemplate('edit', 'should have rendered index');
	}

	function testUpdate() {
		$this->post('update', ['article' => $this->article->attributes()], $this->article)
			->assertWasRedirect('should have redirected to index');
	}

	function testRedirect() {
		$this->article->update_attribute('is_redirect', true);
		$this->get('index', [
			'sort'     => 'is_redirect',
			'sort_dir' => 'DESC'
		])->assertTemplate('index')->assertOnPage($this->article->title);
	}

	function testDelete() {
		$this->get('delete', $this->article)
			->assertWasRedirect('should have redirected to index')
			->assertFalse(Article::exists($this->article->id), 'article should be gone');
	}

}
