<?
namespace ContentPortal;
use PHPUnit_Framework_TestCase;
use Title;

class ArticleValidationTest extends PHPUnit_Framework_TestCase {
	public $article;

	public function setup() {
		$this->article = new Article([
			'title'       => "A test title", 
			'is_wrm'      => true,
			'category_id' => Category::last()->id
		]);
	}

	public function testRequiresCategroy() {
		$this->article->category_id = null;
		$this->assertFalse($this->article->is_valid(), "Should not be valid without a category");
	}

	public function testRequiresTitle() {
		$this->article->title = '';
		$this->assertFalse($this->article->is_valid(), 'not valid with a blank title');
		$this->assertNull($this->article->wh_article_url, 'url should be null if title is blank');
	}

	public function testRequiresTitleToBeUnique() {
		$this->article->title = Article::first()->title;
		$this->article->is_valid();
		$this->assertFalse($this->article->is_valid(), 'not valid if title not unique');
	}

	public function testTitleValidation() {
		$this->article->is_valid();

		$this->assertEquals($this->article->state_id, Role::write()->id, 'should default to writing if no state set');
		$this->assertNotNull($this->article->wh_article_url, 'should set the url at time of validation');
	}

	public function testInputUntouchedIfExisting() {
		$this->article->is_wrm = false;
		$this->article->title = "http://someurl.com/with-test-article";
		$this->article->is_valid();

		$this->assertEquals($this->article->title, "with test article", 'should not manipulate title if existing');
		$this->assertNotNull($this->article->wh_article_url, 'should set the url at time of validation');
		$this->assertNotNull($this->article->wh_article_id, 'should set the id because its not existing');
	}

	public function notValidIfNonExistent() {
		Title::$exists = true;
		$this->article->is_wrm = false;
		$this->article->title = "some new test article";
		$this->assertFalse($this->article->is_valid(), 'should not be valid if non-existing');
	}

	public function testCleansTheTitleIfNew() {
		Title::$exists = false;
		$this->article->is_wrm = true;
		$this->article->title = "http://someurl.com/with-test-article";
		
		$this->assertTrue($this->article->is_valid(), 'should be valid even if non-existing');
		$this->assertNull($this->article->wh_article_id, 'should not set the id if not existing');
		$this->assertEquals($this->article->title, "with test article", 'should not manipulate title if not existing');
	}
}
