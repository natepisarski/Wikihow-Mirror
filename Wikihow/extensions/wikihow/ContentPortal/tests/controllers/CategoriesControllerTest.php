<?
namespace ContentPortal;
use MVC\Output;
use MVC\ControllerTestClass;

class CategoriesControllerTest extends ControllerTestClass {

	static $catTitle = 'Fake Category For Test';

	function setup() {
		parent::setup();
		Helpers::setCurrentUser(Helpers::getAdmin());
	}

	static function tearDownAfterClass() {
		$cat = Category::find_by_title(self::$catTitle);
		if ($cat) $cat->delete();
	}

	static function makeCat() {
		return Category::find_or_create(['title' => self::$catTitle]);
	}

	function testProtectedWriter() {
		Helpers::setCurrentUser(Helpers::getWriter());

		$this->get('index')->assert404()
			->get('new')->assert404()
			->get('update')->assert404()
			->get('create')->assert404()
			->get('delete')->assert404();
	}

	function testIndex() {
		$this->get('index')->assertHasVar('categories');
	}

	function testNew() {
		$this->get('new')
			->assertHasVar('category')
			->assertOnPage('Create a Category');
	}

	function testValidationOnCreate() {
		$this->post('create', ['category' => ['title' => null]])
			->assertTemplate('new')
			->assertHasVar('errors', 'should have failed validation');
	}

	function testCreate() {
		$this->post('create', ['category' => ['title' => self::$catTitle]])
			->assertWasRedirect('should have redirected after creation of cat')
			->assertEquals(Category::last()->title, self::$catTitle);
	}

	function testEdit() {
		$cat = self::makeCat();

		$this->get('edit', ['id' => $cat->id])
			->assertHasVar('category')
			->assertOnPage(self::$catTitle);
	}

	function testValidationOnUpdate() {
		$cat = self::makeCat();
		$this->post('update', ['id' => $cat->id, 'category' => ['title' => null]])
			->assertTemplate('edit')
			->assertHasVar('errors', 'should have failed validation');
	}

	function testUpdate() {
		$cat = self::makeCat();
		$cat->title = Helpers::randomString();
		$this->post('update', ['category' => $cat->attributes()], $cat)
			->assertWasRedirect()
			->assertEquals(Category::last()->title, $cat->title);
	}

	function testDelete() {
		$cat = self::makeCat();
		$this->post('delete', ['id' => $cat->id])
			->assertWasRedirectTo('index')
			->assertFalse(Category::exists(['title' => self::$catTitle]));
	}

	function testDeleteWhenUsed() {
		$cat = self::makeCat();
		$article = $this->article = Helpers::getFakeArticle();

		$article->update_attribute('category_id', $cat->id);

		$this->post('delete', ['id' => $cat->id])
			->assertTrue(Category::exists($cat->id));
	}
}
