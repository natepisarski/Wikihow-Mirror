<?php
namespace ContentPortal;
Helpers::cleanupAll();

use PHPUnit_Framework_TestCase;
class ArticleTest extends PHPUnit_Framework_TestCase {
	public $article;

	function setup() {
		$this->article = Helpers::getFakeArticle();
	}

	static function tearDownAfterClass() {
		Helpers::cleanupAll();
	}

	// function testTokenCreation() {
	// 	$this->assertNotNull($this->article->token, 'should create a token on creation');
	// }

	function testHasManyNotes() {
		$note = Note::create(['article_id' => $this->article->id]);
		$this->assertEquals(count($this->article->notes), 1, 'should have notes attribute');
	}

	function testIsBlocked() {
		$this->article->state_id = Role::blockingQuestion()->id;
		$this->assertTrue($this->article->isBlocked());
	}

	function testAssignmentAccessors() {
		$this->article->assigned_id = Helpers::getAdmin();
		$this->assertFalse($this->article->isUnassigned(), 'should detect if assigned');
		$this->article->assigned_id = null;
		$this->assertTrue($this->article->isUnassigned(), 'should detect if assigned');
	}

	function testBelongsTo() {
		$admin = Helpers::getAdmin();
		$this->assertFalse($this->article->belongsTo($admin), 'should not belong to the user');
		Assignment::build($this->article)->create($admin);
		$this->assertTrue($this->article->belongsTo($admin), 'should belong to the user');
	}

	function testMessageAccessor() {
		Assignment::build($this->article)->create(Helpers::getAdmin(), 'my message');
		$this->assertEquals($this->article->lastNote()->message, 'my message');
	}

	function testCleansUpOnDelete() {
		Note::create(['article_id' => $this->article->id]);
		$this->assertEquals(count($this->article->notes), 1, 'should have a note');
		$this->article->delete();

		$this->assertEmpty(Note::all(['article_id' => $this->article->id]), 'should be removed on delete');
		$this->assertEmpty(UserArticle::all(['article_id' => $this->article->id]), 'should be removed on delete');
		$this->assertEmpty(Document::all(['article_id' => $this->article->id]), 'should be removed on delete');
		$this->assertEmpty(Event::all(['article_id' => $this->article->id]), 'should be removed on delete');
	}
}
