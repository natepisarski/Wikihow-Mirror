<?
namespace ContentPortal;
use MVC\ControllerTestClass;

class ArticlesControllerTest extends ControllerTestClass {

	function setup() {
		parent::setup();
		Helpers::setCurrentUser(Helpers::getWriter());
		$this->article = Helpers::getFakeArticle();
		$this->articleAlt = Helpers::getFakeArticle();

		Assignment::build($this->articleAlt)->create(Helpers::getProofReader(), 'my message');
		Assignment::build($this->article)->create(Helpers::getCurrentUser(), 'my message');
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testShow() {
		$this->assertTrue($this->article->belongsTo(Helpers::getCurrentUser()), 'should belong to the user we have');
		$this->get('show', $this->article)
			->assertHasVar('article', 'should have set an article');
	}

	function testDashboard() {
		$this->get('dashboard')->assertOnPage('Your Work', 'should render the page');
	}

	function testDone() {
		$this->get('done', $this->article)
			->assertHasVar('article', 'should have set an article')
			->assertWasRedirect('should have redirected');

		$this->assertFalse($this->article->reload()->belongsTo(Helpers::getCurrentUser()), 'should no longer belong to user');
	}

	function testGuard() {
		$this->assertFalse($this->articleAlt->belongsTo(Helpers::getCurrentUser()), 'article should not belong to user');
		$this->assertFalse(Helpers::getCurrentUser()->isAdmin(), 'should not be admin');

		$this->get('show', $this->articleAlt)->assert404()
			->get('articles/done', $this->articleAlt)->assert404()
			->get('articles/reject', $this->articleAlt)->assert404();
	}

	function testRejectForm() {
		$user = Helpers::getCurrentUser();
		Assignment::build($this->article)->done()->create($user);
		Note::create([
			'article_id' => $this->article->id, 'type' => Note::BLOCKING, 'message' => "hey there"
		]);

		Assignment::build($this->article)->create($user);
		$this->assertTrue($this->article->isBlocked());

		$this->get('reject_form', $this->article)->assertHasVar('article');
	}

	function testRejectFormWhenBlocking() {
		$user = Helpers::getCurrentUser();
		Assignment::build($this->article)->done()->create($user);
		$this->get('reject_form', $this->article)->assertHasVar('article');
	}

	function testReject() {
		$user = Helpers::getCurrentUser();
		$article = Helpers::getFakeArticle(['state_id' => Role::proofRead()->id]);
		Assignment::build($article)->create($user);

		$this->assertTrue($article->belongsTo($user), 'should belong to the user we just got');

		$this->post('reject', $article->attributes())
			->assertWasRedirect('should redirect after reject')
			->assertFalse($article->reload()->belongsTo($user), 'should no longer belong to the user');
	}

	function testRejectWithAjax() {
		$user = Helpers::getCurrentUser();
		$article = Helpers::getFakeArticle(['state_id' => Role::proofRead()->id]);
		Assignment::build($article)->create($user);

		$this->assertTrue($article->belongsTo($user), 'should belong to the user we just got');
		$post = $article->attributes();
		$post['ajax'] = true;

		$this->post('reject', $post, ['ajax' => true])
			->assertTemplate('articles/_article_tr')
			->assertFalse($article->reload()->belongsTo($user), 'should no longer belong to the user');
	}

	// we have to have get, because not all states require a message
	function testRejectWithGet() {
		$user = Helpers::getCurrentUser();
		$article = Helpers::getFakeArticle(['state_id' => Role::proofRead()->id]);
		Assignment::build($article)->create($user);

		$this->get('reject', $article)->assertWasRedirect();
	}

	function testInfo() {
		$article = Helpers::getFakeArticle();
		$this->get('info', $article)->assert404();

		Helpers::setCurrentUser(Helpers::getAdmin());

		$this->get('info', $article)
			->assertHasVar('article', 'should have article')
			->assertTemplate('_article_info', 'Renders the info template');
	}

	// all the admin routes should redirect for normal user
	function testProtection() {
		$this->get('new')->assert404()
			->post('create')->assert404()
			->get('edit')->assert404()
			->get('index')->assert404()
			->post('update')->assert404()
			->get('delete')->assert404();
	}
}
