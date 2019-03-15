<?php

/**
 * @group wikihow
 * @group GoodRevision
 */
class GoodRevisionTest extends MediaWikiTestCase {

	/**
	 * Test GoodRevision in a simple way
	 * @covers GoodRevision::newFromTitle
	 * @covers GoodRevision::latestGood
	 */
	public function testSettingGoodRevision() {
		// delete test article if it already exists
		$title = Title::newFromURL('GoodRevision-New-Article');
		if ($title && $title->exists()) {
			$page = WikiPage::factory($title);
			$page->doDeleteArticleReal('delete before testing');
		}

		$title = Title::makeTitle(NS_MAIN, 'GoodRevision-New-Article');

		$wikitext = "Intro\n==Steps==\n#Step 1\n";
		$content = ContentHandler::makeContent( $wikitext, $title );
		$summary = "First revision";
		$flags = 0;
		$page = WikiPage::factory($title);
		$ret = $page->doEditContent( $content, $summary, $flags);

		// Recreate the Title object so that it has the article ID and internally
		// believes the title exists
		$title = Title::newFromURL('GoodRevision-New-Article');
		$goodRev = GoodRevision::newFromTitle($title);

		$this->assertNotEquals( $goodRev, null,
			"Creates a good revision object for a new page and checks that it's set." );

		$this->assertEquals( $page->mLatest, $goodRev->latestGood(),
			"Ensures that good revision of an article is created when an article is created." );

		$wikitext = "Intro\n==Steps==\n#Step 1\n#Step 2\n";
		$content = ContentHandler::makeContent( $wikitext, $title );
		$summary = "Second revision";
		$flags = $flags | EDIT_UPDATE;

		$page->doEditContent( $content, $summary, $flags);
		$goodRev = GoodRevision::newFromTitle($title);

		$this->assertNotEquals( $page->mLatest, $goodRev->latestGood(),
			"Ensures that good revision of an article is not moved forward with an update to existing article." );
	}

}
