<?php

/**
 * @group wikihow
 * @group SpecialTechFeedback
 */
class SpecialTechFeedbackTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	public function dataAllowedText() {
		return array(
			// ( $incomingParams, $resultingParams )
			array( '', false ),
			array( 'oneword', false ),
			array( 'two wordsbutshort', false ),
			array( 'two wordsbutshort', false ),
			array( 'two wordsandreallylong', false ),
			array( 'three words short', false ),
			array( 'three words andlongabc', true ),
			array( 'three words andlongabc but shit a bad word', false ),
			array( 'three words andlong but a repeatttt', false ),
			array( 'is this just a normal comment???', false ),
			array( 'a normal comment finally!', true ),
		);
	}

	/**
	 * @dataProvider dataAllowedText
	 * @covers SpecialTechFeedback::isTextAllowed
	 */
	public function testTextAllowed( $paramsIn, $paramsOut ) {
		$this->assertEquals( $paramsOut, SpecialTechFeedback::isTextAllowed( $paramsIn ) );
	}

}
