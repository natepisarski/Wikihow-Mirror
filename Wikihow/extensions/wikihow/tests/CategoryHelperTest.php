<?php

/**
 * @group wikihow
 * @group CategoryHelper
 */
class CategoryHelperTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

    /**
     * @dataProvider titleCatProvider
     */
    public function testAdd( $pageId, $category, $expected )
    {
        $title = Title::newFromId( $pageId );
		$this->assertEquals( $expected, CategoryHelper::isTitleInCategory( $title, $category ) );
        //$this->assertEquals($expected, $pageId + $b);
    }

    public function titleCatProvider() {
        return [
            'edge case of 0 for cat name' => [2053, 0, false],
            'correct FA cat' => [2053, 'Featured Articles', true],
            'correct category' => [2053, 'Relationships', true],
            'wrong category' => [2053, 'Computers And Electronics', false],
            'wrong casing in catgory' => [1134164, 'Computers And Electronics', false],
            'normal computer cat article' => [1134164, 'Computers and Electronics', true],
            'dashes in cat name' => [1134164, 'Computers-and-Electronics', false],
        ];
    }

    /**
     * @dataProvider titleCatMaskProvider
     */
    public function testGetTitleCategoryMask( $pageId, $expected ) {
        $title = Title::newFromId( $pageId );
		$this->assertEquals( $expected, CategoryHelper::getTitleCategoryMask( $title ) );
    }

    public function titleCatMaskProvider() {
        return [
            'no page' => [0,  0],
            'kiss' => [2053,  73728],
            'non main namspace' => [1358314,  0],
            'regular article' => [877802,  65664],
            'regular article' => [1848901,  73728],
            'regular article' => [2032391,  327936],
            'regular article' => [7770712,  198656],
            'regular article' => [300763,  66048],
            'regular article' => [997733,  65796],
            'regular article' => [554870,  66304],
        ];
    }

    /**
     * @dataProvider topLevelCatProvider
     */
    public function testGetTitleTopLevelCategories( $pageId, $expectedCategoryNames ) {
        $title = Title::newFromId( $pageId );
        $cats = CategoryHelper::getTitleTopLevelCategories( $title );
        $expected = array();
        foreach( $expectedCategoryNames as $cat ) {
			$expected[] = Title::makeTitle(NS_CATEGORY, $cat);
        }
        foreach( $cats as $cat ) {
            //decho("cat", $cat);
        }
		$this->assertEquals( $expected, $cats );
    }

    /**
     * @dataProvider topLevelCatProviderNoWikihow
     */
    public function testGetTopLevelCategories( $pageId, $expectedCategoryNames ) {
        $title = Title::newFromId( $pageId );
        $cats = CategoryHelper::getTopLevelCategories( $title );
        $expected = array();
        foreach( $expectedCategoryNames as $cat ) {
			$expected[] = Title::makeTitle(NS_CATEGORY, $cat);
        }
        foreach( $cats as $cat ) {
            //decho("cat", $cat);
        }
		$this->assertEquals( $expected, $cats );
    }
}

