<?php

/**
 * @group QAUtil
 * @group wikihow
 * @group QA
 */
class QAUtilTest extends MediaWikiTestCase {

	var $badWordContent = "This has the word 'fuck' in it. Fuck.  What a bad word it is.";
	var $okWordContent = "This has no bad words in it.";
	var $maybeWordContent = "This kinda has the word f uck in it.";
	var $multiWordContent = "nut sack";

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}


	public function testBadWords() {
		$this->assertTrue(true, "sanity check");
		$this->assertTrue(QAUtil::hasBadWord($this->badWordContent), "content with bad word");
		$this->assertFalse(QAUtil::hasBadWord($this->okWordContent), "content without bad word");
		$this->assertFalse(QAUtil::hasBadWord($this->maybeWordContent), "content with maybe a bad word");
		$this->assertFalse(QAUtil::hasBadWord($this->multiWordContent), "content with a multi-token bad word");
	}
}
