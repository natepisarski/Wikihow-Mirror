<?
namespace ContentPortal;
use MVC\Output;
use __;
use MVC\ControllerTestClass;

class BulkControllerTest extends ControllerTestClass {

	function setup() {
		Helpers::setCurrentUser(Helpers::getAdmin());
	}

	function testRestrictions() {
		Helpers::setCurrentUser(Helpers::getWriter());
		$this->get('new')->assert404();
		$this->get('create')->assert404();
		$this->get('done')->assert404();
	}

	function testRedirect() {
		$this->get('index')->assertWasRedirectTo('new');
	}

	function testNew() {
		$this->get('new')->assertTemplate('new');
	}

	function testCreate() {
		$urls = __::pluck(Article::all(['limit' => 10]), 'wh_article_url');

		$this->post('create', ['data' => implode("\n", $urls)])
			->assertHasVar('articles', 'should have parsed the urls into articles')
			->assertTemplate('new');
	}

	function testDone() {
		$article = Helpers::getFakeArticle();

		$this->post('done', ['article_ids' => [$article->id], 'all_articles_ids' => [$article->id]])
			->assertHasVar('articles', 'should have parsed the urls into articles')
			->assertTemplate('new');

		$this->assertEquals($article->reload()->state_id, Role::proofRead()->id);
	}
}