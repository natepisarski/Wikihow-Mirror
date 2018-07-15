<?
namespace ContentPortal;
use __;
use PHPUnit_Framework_TestCase;
class NoteTest extends PHPUnit_Framework_TestCase {

	public $article;

	public $info;
	public $kudos;
	public $blocking;
	public $admin;
	public $writer;

	function setup() {
		$this->article = Helpers::getFakeArticle();
		$this->admin = Helpers::getAdmin();
		$this->writer = Helpers::getWriter();
		Assignment::build($this->article)
			->create($this->writer)
			->done()
			->create($this->admin);

		Note::delete_all(['conditions' => ["type" => Note::KUDOS]]);
		Note::delete_all(['conditions' => ["type" => Note::BLOCKING]]);

		$this->info = Note::create([
			'user_id' => $this->admin->id,
			'article_id' => $this->article->id,
			'role_id' => Role::edit()->id
		]);

		$this->kudos = Note::create([
			'user_id'      => $this->admin->id,
			'article_id'   => $this->article->id,
			'recipient_id' => $this->writer->id,
			'type'         => Note::KUDOS
		]);

		$this->blocking = Note::create([
			'user_id'      => $this->admin->id,
			'article_id'   => $this->article->id,
			'type'         => Note::BLOCKING
		]);

		$this->article->reload();
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	function testNoteValues() {
		$this->assertEquals($this->info->article->id, $this->article->id);
		$this->assertEquals($this->info->role->id, Role::edit()->id);
		$this->assertEquals($this->info->type, Note::INFO, 'should default to type of info');
	}

	function testAssociatedUsers() {
		$this->assertEquals($this->info->sender->id, $this->admin->id);
	}

	function testKudos() {
		$this->assertEquals($this->kudos->recipient->id, $this->writer->id);
		$this->assertEquals($this->kudos->sender->id, $this->admin->id);
		$this->assertEquals(count($this->writer->kudos()), 1);
		$this->assertEquals($this->writer->kudos()[0]->id, $this->kudos->id);
	}

	function testBlocking() {
		$note = Note::create([
			'note_id' => $this->blocking->id,
			'article_id' => $this->article->id,
		]);

		$this->assertEquals($this->blocking->prev_assignment->role->key, Role::PROOF_READ_KEY);
		$this->assertEquals($this->blocking->sender->id, $this->admin->id);
		$this->assertEquals(count($this->blocking->notes), 1);
		$this->assertEquals($this->article->state->key, Role::BLOCKING_QUESTION_KEY);
		$this->blocking->delete();
	}

}