<?php

/**
 * @covers GuidedTourHooks
 */
class GuidedTourHooksTest extends MediaWikiTestCase {
	public function testGetTourNames() {
		$this->assertSame(
			[],
			GuidedTourHooks::getTourNames( null ),
			'Returns empty array for null parameter'
		);

		$this->assertSame(
			[ 'test' ],
			GuidedTourHooks::getTourNames( FormatJson::encode( [
				'version' => 1,
				'tours' => [
					'test' => [
						'step' => 3,
					],
				],
			] ) ),
			'Valid JSON cookie with a single tour is parsed correctly'
		);

		$this->assertSame(
			[ 'firsttour', 'secondtour', 'thirdtour' ],
			GuidedTourHooks::getTourNames( FormatJson::encode( [
				'version' => 1,
				'tours' => [
					'firsttour' => [
						'step' => 4,
					],
					'secondtour' => [
						'step' => 2,
					],
					'thirdtour' => [
						'step' => 3,
						'firstArticleId' => 38333
					],
				],
			] ) ),
			'Valid JSON cookie with multiple tours is parsed correctly'
		);

		$this->assertSame(
			[],
			GuidedTourHooks::getTourNames( '{"bad": "cookie"}' ),
			'Valid JSON with missing tours field returns empty array'
		);

		$this->assertSame(
			[],
			GuidedTourHooks::getTourNames( '<invalid: JSON>' ),
			'Invalid JSON returns empty array'
		);
	}
}
