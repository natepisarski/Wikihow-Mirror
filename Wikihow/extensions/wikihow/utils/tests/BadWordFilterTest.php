<?php

/**
 * @group BadWordFilter
 * @group wikihow
 * @group Utils
 */
class BadWordFilterTest extends MediaWikiTestCase {

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
		$this->assertTrue(BadWordFilter::hasBadWord($this->badWordContent), "content with bad word");
		$this->assertFalse(BadWordFilter::hasBadWord($this->okWordContent), "content without bad word");
		$this->assertFalse(BadWordFilter::hasBadWord($this->maybeWordContent), "content with maybe a bad word");
		$this->assertFalse(BadWordFilter::hasBadWord($this->multiWordContent), "content with a multi-token bad word");
	}
}
