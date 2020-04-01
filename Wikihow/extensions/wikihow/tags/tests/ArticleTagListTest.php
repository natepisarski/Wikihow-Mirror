<?php

/**
 * @group ArticleTagList
 * @group wikihow
 * @group tags
 */
class ArticleTagListTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetTags() {

		$t1 = Title::newFromText('Write');
		$t1id = $t1->getArticleId();
		$t2 = Title::newFromText('Surf');
		$t2id = $t2->getArticleId();
		$t3 = Title::newFromText('Kiss');
		$t3id = $t3->getArticleId();
		$t4 = Title::newFromText('Rap');

		$pages = [ ['title' => $t1] ];

		$tagName1 = 'testtag1';
		$tagName2 = 'testtag2';
		$tagName3 = 'testtag3';
		$filter = [$tagName1, $tagName2, $tagName3];

		$this->addTags($tagName3, $pages);

		$pages[] = ['title' => $t2];
		$this->addTags($tagName2, $pages);

		$pages[] = ['title' => $t3];
		$this->addTags($tagName1, $pages);

		$list = new ArticleTagList($t1id);
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$expected = [$tagName1, $tagName2, $tagName3];
		$this->assertArrayEquals($expected, $tags);

		// test caches
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$this->assertArrayEquals($expected, $tags);

		ArticleTagList::clearCache($t1id);
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$this->assertArrayEquals($expected, $tags);

		// test 2nd title
		$list = new ArticleTagList($t2id);
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$expected2 = [$tagName1, $tagName2];
		$this->assertArrayEquals($expected2, $tags);

		// test 3rd title
		$list = new ArticleTagList($t3id);
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$expected3 = [$tagName1];
		$this->assertArrayEquals($expected3, $tags);

		// test 1st title again, but using Title object
		$list = new ArticleTagList($t1);
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$this->assertArrayEquals($expected, $tags);

		// test ArticleTagList::hasTag
		$res = ArticleTagList::hasTag($tagName1, $t1id);
		$this->assertTrue($res);
		$res = ArticleTagList::hasTag('testmycompletelyrandomtag', $t1id);
		$this->assertFalse($res);
		$res = ArticleTagList::hasTag($tagName1, 17263716376); // article id that doesn't exist
		$this->assertFalse($res);
		$res = ArticleTagList::hasTag($tagName3, $t1id);
		$this->assertTrue($res);
		$res = ArticleTagList::hasTag($tagName3, $t2id);
		$this->assertFalse($res);

		// cleanup
		$this->tagCleanup($tagName1);
		$this->tagCleanup($tagName2);
		$this->tagCleanup($tagName3);

		// test cache clear
		ArticleTagList::clearCache($t1id);
		$list = new ArticleTagList($t1);
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$this->assertArrayEquals([], $tags);
	}

	public function testDeleteTag() {
		$t1 = Title::newFromText('Write');
		$t1id = $t1->getArticleId();
		$pages = [ ['title' => $t1] ];

		$tagName = 'testdeletetag';
		$filter = [$tagName];

		$this->addTags($tagName, $pages);

		$tag = new ArticleTag($tagName);
		$tag->deleteTag();

		$list = new ArticleTagList($t1id);
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$this->assertArrayEquals([], $tags);
	}

	public function testProbability() {
		$prob1 = 50;
		$tagName = 'testprobtag';
		$filter = [$tagName];

		$t1 = Title::newFromText('Hug');
		$t1id = $t1->getArticleId();
		$pages = [ ['title' => $t1] ];

		$tag = new ArticleTag($tagName, $prob1);
		$tag->deleteTag();

		$tag = new ArticleTag($tagName, $prob1);
		$res = $tag->modifyTagList($pages);

		$list = new ArticleTagList($t1);
		// make sure list is what we're expecting
		$tagsList = $list->getTags();
		$tags = array_intersect( $filter, array_keys($tagsList) );
		$this->assertEquals(count($tags), 1);
		$prob = $tagsList[$tagName]['prob'];
		$this->assertEquals($prob1, $prob);

		// cleanup
		$this->tagCleanup($tagName);
	}

	private function addTags($tagName, $pages) {

		// make sure tag doesn't exist before starting
		$tag = new ArticleTag($tagName);
		$tag->deleteTag();

		// re-initialize tag object
		$tag = new ArticleTag($tagName);

		$res = $tag->modifyTagList($pages);

		// make sure we have correct number of items
		$tag = new ArticleTag($tagName);
		$list = $tag->getArticleList();
		$this->assertEquals(count($pages), count($list));
	}

	private function tagCleanup($tagName) {
		$tag = new ArticleTag($tagName);
		$tag->deleteTag();
	}

}
