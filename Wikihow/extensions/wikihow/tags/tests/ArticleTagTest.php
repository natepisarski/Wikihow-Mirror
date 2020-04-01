<?php

/**
 * @group ArticleTag
 * @group wikihow
 * @group tags
 */
class ArticleTagTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testModifyTags() {
		$tagName1 = 'testtag1';
		$this->modifyTags($tagName1);

		$tagName2 = 'testtag2';
		$this->modifyTags($tagName2);

		// make sure old article list is still there after new one is saved
		$tag = new ArticleTag($tagName1);
		$list = $tag->getArticleList();
		$this->assertEquals(count($list), 1);

		$tag = new ArticleTag($tagName2);
		$list = $tag->getArticleList();
		$this->assertEquals(count($list), 1);
	}

	public function testDeleteTags() {
		$tagName = 'deletetag';
		$this->modifyTags($tagName);

		$tag = new ArticleTag($tagName);
		$tag->deleteTag();
		$tag = new ArticleTag($tagName);
		$list = $tag->getArticleList();
		$this->assertArrayEquals([], $list);
	}

	private function modifyTags($tagName) {

		$t1 = Title::newFromText('Write');
		$t1id = $t1->getArticleId();
		$t2 = Title::newFromText('Surf');
		$t2id = $t2->getArticleId();
		$t3 = Title::newFromText('Kiss');
		$t3id = $t3->getArticleId();

		$pages = [
			['title' => $t1],
			['title' => $t2],
		];

		// make sure tag doesn't exist before starting
		$tag = new ArticleTag($tagName);
		$tag->deleteTag();

		// re-initialize tag object
		$tag = new ArticleTag($tagName);

		$res = $tag->modifyTagList($pages);

		// make sure object is as expected after initial set
		$list = $tag->getArticleList();
		$expected = [$t1id, $t2id];
		$this->assertArrayEquals($expected, $list);

		// make sure that new tag is set as expected
		$tag = new ArticleTag($tagName);
		$list = $tag->getArticleList();
		$this->assertEquals(count($list), 2);
		$this->assertArrayEquals($expected, $list);

		// make sure list is as expected after populated again
		$tag = new ArticleTag($tagName);
		$list = $tag->getArticleList();
		$this->assertArrayEquals($expected, $list);

		// make sure list is as expected after adding new article
		$pages[] = ['title' => $t3];
		$expected2 = $expected;
		$expected2[] = $t3id;
		$res = $tag->modifyTagList($pages);
		$list = $tag->getArticleList();
		$this->assertArrayEquals($expected2, $list);

		// make sure new list has new element
		$tag = new ArticleTag($tagName);
		$list = $tag->getArticleList();
		$this->assertArrayEquals($expected2, $list);

		// delete everything but last element
		unset($pages[0]);
		unset($pages[1]);
		$expected3 = [$t3id];
		$tag = new ArticleTag($tagName);
		$res = $tag->modifyTagList($pages);
		$list = $tag->getArticleList();
		$this->assertArrayEquals($expected3, $list);

		// make sure new list has just last element
		$tag = new ArticleTag($tagName);
		$list = $tag->getArticleList();
		$this->assertArrayEquals($expected3, $list);
	}

	public function testProbability() {
		$prob1 = 30;
		$prob2 = 70;
		$tagName = 'prob_tag';

		$tag = new ArticleTag($tagName, $prob1);
		$tag->deleteTag();

		$tag = new ArticleTag($tagName, $prob1);
		$t1 = Title::newFromText('Hug');
		$t1id = $t1->getArticleId();

		$pages = [
			['title' => $t1],
		];
		$res = $tag->modifyTagList($pages);
		$list = $tag->getArticleList();

		// make sure list is what we're expecting
		$expected = [$t1id];
		$this->assertArrayEquals($expected, $list);

		// check probability is set properly in same object
		$outProb = $tag->getProbability();
		$this->assertEquals($prob1, $outProb);

		// check probability is set properly in new object instance
		$tag = new ArticleTag($tagName);
		$outProb = $tag->getProbability();
		$this->assertEquals($prob1, $outProb);

		// set a new probability and check that in same object
		$tag = new ArticleTag($tagName);
		$tag->updateProbability($prob2);
		$outProb = $tag->getProbability();
		$this->assertEquals($prob2, $outProb);

		// check new probability in new object
		$tag = new ArticleTag($tagName);
		$outProb = $tag->getProbability();
		$this->assertEquals($prob2, $outProb);

		// cleanup
		$tag->deleteTag();
	}

}
