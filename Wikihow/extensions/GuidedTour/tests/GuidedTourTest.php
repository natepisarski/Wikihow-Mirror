<?php

class GuidedTourTest extends MediaWikiTestCase {
	public function testGetTourNames() {
		$this->assertSame(
			array(),
			GuidedTourHooks::getTourNames( null ),
			'Returns empty array for null parameter'
		);

		$this->assertSame(
			array( 'test' ),
			GuidedTourHooks::getTourNames( FormatJson::encode( array(
				'version' => 1,
				'tours' => array(
					'test' => array(
						'step' => 3,
					),
				),
			) ) ),
			'Valid JSON cookie with a single tour is parsed correctly'
		);

		$this->assertSame(
			array( 'firsttour', 'secondtour', 'thirdtour' ),
			GuidedTourHooks::getTourNames( FormatJson::encode( array(
				'version' => 1,
				'tours' => array(
					'firsttour' => array(
						'step' => 4,
					),
					'secondtour' => array(
						'step' => 2,
					),
					'thirdtour' => array(
						'step' => 3,
						'firstArticleId' => 38333
					),
				),
			) ) ),
			'Valid JSON cookie with multiple tours is parsed correctly'
		);

		$this->assertSame(
			array(),
			GuidedTourHooks::getTourNames( '{"bad": "cookie"}' ),
			'Valid JSON with missing tours field returns empty array'
		);

		$this->assertSame(
			array(),
			GuidedTourHooks::getTourNames( '<invalid: JSON>' ),
			'Invalid JSON returns empty array'
		);
	}
}
